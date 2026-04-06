<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidInventoryConfigurationException;
use App\Exceptions\InsufficientMaterialStockException;
use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\OrderTemplate;
use App\Models\Product;
use App\Models\RushFee;
use App\Services\InventoryStockService;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerOrderController extends Controller
{
    public function __construct(
        protected OrderPricingService $pricingService,
        protected InventoryStockService $stockService,
    ) {
    }

    /**
     * List grouped orders for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:all,waiting,approved,preparing,ready,completed,cancelled',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $status = $validated['status'] ?? 'all';
        $perPage = (int) ($validated['per_page'] ?? 12);

        $query = CustomerOrderGroup::query()
            ->where('user_id', Auth::id())
            ->with([
                'orders:id,customer_order_group_id,product_id,quantity,total_price,status,selected_options,special_instructions,created_at',
                'orders.product:id,name,cover_image_path',
            ])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $groups = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $groups->getCollection()->map(fn ($group) => $this->transformGroup($group))->values(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /**
     * Get a specific grouped order with full item details.
     */
    public function show(CustomerOrderGroup $orderGroup): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        if ((int) $orderGroup->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view this order.',
            ], 403);
        }

        $orderGroup->load([
            'orders.product:id,name,cover_image_path',
            'orders.rushFee:id,label',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    /**
     * Fetch order template with all related configurations for a product.
     * GET /api/customer-orders/product/{productId}/template
     *
     * Returns: template, options, pricings, discounts, min_order, layout_fee, rush_fees
     */
    public function getProductOrderTemplate(int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            $orderTemplate = $product->orderTemplate()
                ->with([
                    'options.optionTypes' => fn ($q) => $q->orderBy('position'),
                    'pricings',
                    'discounts',
                    'minOrder',
                    'layoutFee',
                ])
                ->firstOrFail();

            // Get all available rush fees for the client to choose from
            $rushFees = RushFee::with([
                'timeframes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
                ->orderBy('min_price')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'cover_image_path' => $product->cover_image_path,
                    ],
                    'template' => [
                        'id' => $orderTemplate->id,
                        'options' => $orderTemplate->options->map(fn ($opt) => [
                            'id' => $opt->id,
                            'label' => $opt->label,
                            'position' => $opt->position,
                            'option_types' => $opt->optionTypes->map(fn ($type) => [
                                'id' => $type->id,
                                'type_name' => $type->type_name,
                                'is_available' => $type->is_available,
                                'position' => $type->position,
                            ]),
                        ]),
                        'pricings' => $orderTemplate->pricings->map(fn ($p) => [
                            'combination_key' => $p->combination_key,
                            'price' => (float)$p->price,
                        ]),
                        'discounts' => $orderTemplate->discounts->map(fn ($d) => [
                            'min_quantity' => $d->min_quantity,
                            'price_reduction' => (float)$d->price_reduction,
                            'position' => $d->position,
                        ])->sortBy('min_quantity')->values(),
                        'min_order' => $orderTemplate->minOrder->min_quantity ?? 1,
                        'layout_fee' => $orderTemplate->layoutFee?->fee_amount ? (float)$orderTemplate->layoutFee->fee_amount : 0,
                    ],
                    'rush_fees' => $rushFees->map(fn ($rf) => [
                        'id' => $rf->id,
                        'label' => $rf->label,
                        'min_price' => (float)$rf->min_price,
                        'max_price' => (float)$rf->max_price,
                        'timeframes' => $rf->timeframes->map(fn ($tf) => [
                            'id' => $tf->id,
                            'label' => $tf->label,
                            'percentage' => (float)$tf->percentage,
                        ]),
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new customer order.
     * POST /api/customer-orders
     *
     * Requires authentication.
     * Validates all inputs and calculates final pricing.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Ensure user is authenticated
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // Validate input
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'order_template_id' => 'required|exists:order_templates,id',
                'selected_options' => 'required|array',
                'quantity' => 'required|integer|min:1',
                'rush_fee_id' => 'nullable|exists:rush_fees,id',
                'special_instructions' => 'nullable|string|max:1000',
                'general_drive_link' => 'nullable|string|max:2048',
            ]);

            $product = Product::findOrFail($validated['product_id']);
            $orderTemplate = OrderTemplate::with(['minOrder', 'options.optionTypes', 'pricings', 'discounts', 'layoutFee'])
                ->findOrFail($validated['order_template_id']);
            $quantity = $validated['quantity'];

            // Validate that product has this order template
            if (! $product->orderTemplate || (int) $product->orderTemplate->id !== (int) $orderTemplate->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order template for this product',
                ], 422);
            }

            // Validate minimum order quantity
            $minOrder = $orderTemplate->minOrder->min_quantity ?? 1;
            if ($quantity < $minOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum order quantity is {$minOrder}",
                ], 422);
            }

            $selectedOptions = $this->pricingService->normalizeSelectedOptions(
                $orderTemplate,
                $validated['selected_options']
            );

            // Calculate pricing
            $pricing = $this->pricingService->calculate(
                $orderTemplate,
                $selectedOptions,
                $quantity,
                $validated['rush_fee_id'] ?? null,
                $validated['special_instructions'] ?? null,
            );

            if (!isset($pricing['success']) || !$pricing['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $pricing['message'] ?? 'Failed to calculate pricing',
                ], 422);
            }

            // Store the order in a transaction
            [$group, $order] = DB::transaction(function () use ($validated, $pricing, $selectedOptions) {
                $requirements = $this->stockService->deductForOrderLines([
                    [
                        'product_id' => (int) $validated['product_id'],
                        'quantity' => (int) $validated['quantity'],
                        'selected_options' => $selectedOptions,
                    ],
                ]);

                $group = CustomerOrderGroup::create([
                    'user_id' => Auth::id(),
                    'status' => 'waiting',
                    'general_drive_link' => $validated['general_drive_link'] ?? null,
                    'subtotal_price' => $pricing['base_price'],
                    'discount_total' => $pricing['discount_amount'],
                    'rush_fee_total' => $pricing['rush_fee_amount'],
                    'layout_fee_total' => $pricing['layout_fee_amount'],
                    'total_price' => $pricing['total_price'],
                    'inventory_material_requirements' => $requirements,
                    'inventory_deducted_at' => now(),
                ]);

                $order = CustomerOrder::create([
                    'customer_order_group_id' => $group->id,
                    'user_id' => Auth::id(),
                    'product_id' => $validated['product_id'],
                    'order_template_id' => $validated['order_template_id'],
                    'rush_fee_id' => $validated['rush_fee_id'] ?? null,
                    'selected_options' => $selectedOptions,
                    'quantity' => $validated['quantity'],
                    'special_instructions' => $validated['special_instructions'] ?? null,
                    'base_price' => $pricing['base_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'rush_fee_amount' => $pricing['rush_fee_amount'],
                    'layout_fee_amount' => $pricing['layout_fee_amount'],
                    'total_price' => $pricing['total_price'],
                    'status' => 'waiting',
                ]);

                return [$group, $order];
            });

            // Verify order was actually saved to database
            if (!$order || !$order->id) {
                logger()->error('Order creation returned but no ID was assigned', [
                    'order' => $order,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Order created but failed to save. Please try again.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_group_id' => $group->id,
                    'order_id' => $order->id,
                    'total_price' => (float)$order->total_price,
                    'status' => $order->status,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->warning('Order validation failed', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (InsufficientMaterialStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'shortages' => $e->shortages,
            ], 422);
        } catch (InvalidInventoryConfigurationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'configuration_issues' => $e->issues,
            ], 422);
        } catch (\Exception $e) {
            logger()->error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalize group and item data into a UI-ready shape.
     */
    private function transformGroup(CustomerOrderGroup $group, bool $withFullOrderPayload = false): array
    {
        $orders = $group->orders->map(function (CustomerOrder $order) use ($withFullOrderPayload) {
            $base = [
                'id' => $order->id,
                'product_id' => $order->product_id,
                'product_name' => $order->product?->name,
                'product_cover' => $order->product?->cover_image_path,
                'quantity' => $order->quantity,
                'total_price' => (float) $order->total_price,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'created_at' => $order->created_at?->toISOString(),
            ];

            if (! $withFullOrderPayload) {
                return $base;
            }

            return array_merge($base, [
                'selected_options' => $order->selected_options,
                'formatted_options' => $order->formatted_options,
                'special_instructions' => $order->special_instructions,
                'base_price' => (float) $order->base_price,
                'discount_amount' => (float) $order->discount_amount,
                'rush_fee_amount' => (float) $order->rush_fee_amount,
                'layout_fee_amount' => (float) $order->layout_fee_amount,
            ]);
        })->values();

        return [
            'id' => $group->id,
            'status' => $group->status,
            'status_label' => $group->status_label,
            'general_drive_link' => $group->general_drive_link,
            'totals' => [
                'subtotal_price' => (float) $group->subtotal_price,
                'discount_total' => (float) $group->discount_total,
                'rush_fee_total' => (float) $group->rush_fee_total,
                'layout_fee_total' => (float) $group->layout_fee_total,
                'total_price' => (float) $group->total_price,
            ],
            'items_count' => $orders->count(),
            'orders' => $orders,
            'created_at' => $group->created_at?->toISOString(),
            'updated_at' => $group->updated_at?->toISOString(),
        ];
    }
}
