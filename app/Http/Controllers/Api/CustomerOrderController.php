<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidInventoryConfigurationException;
use App\Exceptions\InsufficientMaterialStockException;
use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\OrderTemplate;
use App\Models\Product;
use App\Rules\GoogleDriveUrl;
use App\Models\RushFee;
use App\Support\SchemaMismatchDetector;
use App\Services\InventoryStockService;
use App\Services\OrderPricingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    /**
     * Update grouped customer order details.
     * Only waiting groups owned by the current customer are editable.
     */
    public function updateDetails(Request $request, CustomerOrderGroup $orderGroup): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $authUserId = (int) Auth::id();

        if ((int) $orderGroup->user_id !== $authUserId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to edit this order.',
            ], 403);
        }

        if ($orderGroup->status !== 'waiting') {
            return $this->buildNotEditableResponse();
        }

        $this->normalizeGeneralDriveLink($request);

        $validated = $request->validate([
            'general_drive_link' => ['sometimes', 'nullable', 'string', 'max:2048', new GoogleDriveUrl()],
            'orders' => 'required|array|min:1',
            'orders.*.id' => 'required|integer',
            'orders.*.selected_options' => 'required|array|min:1',
            'orders.*.quantity' => 'required|integer|min:1',
            'orders.*.rush_fee_id' => 'nullable|exists:rush_fees,id',
            'orders.*.special_instructions' => 'nullable|string|max:1000',
        ]);

        $orderGroup->load([
            'orders.product:id,name',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
            'orders.orderTemplate.pricings:id,order_template_id,combination_key,price',
            'orders.orderTemplate.discounts:id,order_template_id,min_quantity,price_reduction',
            'orders.orderTemplate.layoutFee:id,order_template_id,fee_amount',
        ]);

        $groupOrders = $orderGroup->orders->keyBy(fn ($order) => (int) $order->id);
        $requestedOrders = collect($validated['orders']);

        $duplicateIds = $requestedOrders
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->duplicates();

        if ($duplicateIds->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate order entries were provided.',
            ], 422);
        }

        $unknownOrderIds = $requestedOrders
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id): bool => $groupOrders->has($id))
            ->values();

        if ($unknownOrderIds->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more orders do not belong to this group.',
            ], 422);
        }

        $preparedUpdates = [];

        foreach ($requestedOrders as $orderPayload) {
            $order = $groupOrders->get((int) $orderPayload['id']);
            $template = $order?->orderTemplate;

            if (! $order || ! $template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order template configuration is missing for one or more items.',
                ], 422);
            }

            $normalizedOptions = $this->pricingService->normalizeSelectedOptions(
                $template,
                $orderPayload['selected_options']
            );

            $quantity = (int) $orderPayload['quantity'];
            $minOrder = (int) ($template->minOrder?->min_quantity ?? 1);

            if ($quantity < $minOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum order quantity is {$minOrder} for {$order->product?->name}.",
                ], 422);
            }

            $pricing = $this->pricingService->calculate(
                $template,
                $normalizedOptions,
                $quantity,
                $orderPayload['rush_fee_id'] ?? null,
                $orderPayload['special_instructions'] ?? null,
            );

            if (! ($pricing['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $pricing['message'] ?? 'Unable to calculate updated order pricing.',
                ], 422);
            }

            $preparedUpdates[(int) $order->id] = [
                'selected_options' => $normalizedOptions,
                'quantity' => $quantity,
                'rush_fee_id' => $orderPayload['rush_fee_id'] ?? null,
                'special_instructions' => $orderPayload['special_instructions'] ?? null,
                'base_price' => $pricing['base_price'],
                'discount_amount' => $pricing['discount_amount'],
                'rush_fee_amount' => $pricing['rush_fee_amount'],
                'layout_fee_amount' => $pricing['layout_fee_amount'],
                'total_price' => $pricing['total_price'],
            ];
        }

        $finalOrderState = $orderGroup->orders->map(function ($order) use ($preparedUpdates): array {
            $update = $preparedUpdates[(int) $order->id] ?? null;

            return [
                'order_id' => (int) $order->id,
                'product_id' => (int) $order->product_id,
                'selected_options' => $update['selected_options'] ?? ($order->selected_options ?? []),
                'quantity' => (int) ($update['quantity'] ?? $order->quantity),
                'base_price' => (float) ($update['base_price'] ?? $order->base_price),
                'discount_amount' => (float) ($update['discount_amount'] ?? $order->discount_amount),
                'rush_fee_amount' => (float) ($update['rush_fee_amount'] ?? $order->rush_fee_amount),
                'layout_fee_amount' => (float) ($update['layout_fee_amount'] ?? $order->layout_fee_amount),
                'total_price' => (float) ($update['total_price'] ?? $order->total_price),
            ];
        })->values();

        $updatedOrderLines = $finalOrderState
            ->map(fn (array $line): array => [
                'product_id' => (int) $line['product_id'],
                'quantity' => (int) $line['quantity'],
                'selected_options' => $line['selected_options'],
            ])
            ->all();

        try {
            $newRequirements = $this->stockService->calculateRequirements($updatedOrderLines);
            [$deductionRequirements, $restoreRequirements] = $this->buildRequirementDelta(
                $orderGroup->inventory_material_requirements ?? [],
                $newRequirements
            );

            DB::transaction(function () use (
                $orderGroup,
                $groupOrders,
                $preparedUpdates,
                $finalOrderState,
                $newRequirements,
                $deductionRequirements,
                $restoreRequirements,
                $validated,
                $authUserId
            ) {
                $lockedGroup = CustomerOrderGroup::query()
                    ->whereKey($orderGroup->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedGroup || (int) $lockedGroup->user_id !== $authUserId) {
                    throw new \RuntimeException('CUSTOMER_ORDER_ACCESS_DENIED');
                }

                if ($lockedGroup->status !== 'waiting') {
                    throw new \RuntimeException('CUSTOMER_ORDER_NOT_EDITABLE');
                }

                if (! empty($deductionRequirements)) {
                    $this->stockService->deductFromRequirements($deductionRequirements);
                }

                if (! empty($restoreRequirements)) {
                    $this->stockService->restoreFromRequirements($restoreRequirements);
                }

                foreach ($preparedUpdates as $orderId => $payload) {
                    $groupOrders->get($orderId)?->update($payload);
                }

                $groupUpdates = [
                    'subtotal_price' => round((float) $finalOrderState->sum('base_price'), 2),
                    'discount_total' => round((float) $finalOrderState->sum('discount_amount'), 2),
                    'rush_fee_total' => round((float) $finalOrderState->sum('rush_fee_amount'), 2),
                    'layout_fee_total' => round((float) $finalOrderState->sum('layout_fee_amount'), 2),
                    'total_price' => round((float) $finalOrderState->sum('total_price'), 2),
                    'inventory_material_requirements' => $newRequirements,
                    'inventory_deducted_at' => $lockedGroup->inventory_deducted_at ?? now(),
                    'inventory_restored_at' => null,
                ];

                if (array_key_exists('general_drive_link', $validated)) {
                    $groupUpdates['general_drive_link'] = $validated['general_drive_link'];
                }

                $lockedGroup->update($groupUpdates);
            });
        } catch (InsufficientMaterialStockException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to apply changes because inventory stock is insufficient.',
                'shortages' => $e->shortages,
            ], 422);
        } catch (InvalidInventoryConfigurationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'configuration_issues' => $e->issues,
            ], 422);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'CUSTOMER_ORDER_NOT_EDITABLE') {
                return $this->buildNotEditableResponse();
            }

            if ($e->getMessage() === 'CUSTOMER_ORDER_ACCESS_DENIED') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to edit this order.',
                ], 403);
            }

            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Customer order details update failed', [
                'order_group_id' => $orderGroup->id,
                'user_id' => $authUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update order details. Please try again.',
            ], 500);
        }

        $orderGroup->refresh()->load([
            'orders.product:id,name,cover_image_path',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order details updated successfully.',
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    /**
     * Submit payment proof for an approved order awaiting payment.
     */
    public function submitPaymentProof(Request $request, CustomerOrderGroup $orderGroup): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        if ((int) $orderGroup->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to update this order.',
            ], 403);
        }

        if (! $orderGroup->canSubmitPaymentProof()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment proof can only be submitted for approved orders that are awaiting payment.',
                'error_code' => 'customer_order_payment_not_allowed',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:gcash,bpi,paymaya',
            'payment_reference_number' => ['required', 'regex:/^\d{10,14}$/'],
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $proofPath = null;

        try {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            $orderGroup->update([
                'payment_status' => 'waiting_payment_confirmation',
                'payment_method' => $validated['payment_method'],
                'payment_reference_number' => (string) $validated['payment_reference_number'],
                'payment_proof_path' => $proofPath,
                'payment_submitted_at' => now(),
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_confirmation_note' => null,
            ]);
        } catch (\Throwable $e) {
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }

            logger()->error('Customer payment proof submission failed', [
                'order_group_id' => $orderGroup->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit payment proof. Please try again.',
            ], 500);
        }

        $orderGroup->refresh()->load([
            'orders.product:id,name,cover_image_path',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment proof submitted. Please wait for owner confirmation.',
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    /**
     * Cancel a waiting order group owned by the authenticated customer.
     */
    public function cancel(CustomerOrderGroup $orderGroup): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        if ((int) $orderGroup->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to cancel this order.',
            ], 403);
        }

        if (! $orderGroup->canCustomerCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Only orders waiting for approval can be cancelled by customer.',
                'error_code' => 'customer_order_cancel_not_allowed',
            ], 422);
        }

        $shouldRestock = $orderGroup->shouldRestockOnCancellation('cancelled');

        DB::transaction(function () use ($orderGroup, $shouldRestock): void {
            $orderGroup->update([
                'status' => 'cancelled',
                'payment_status' => 'payment_cancelled',
                'cancellation_reason' => 'customer_cancelled',
            ]);

            $orderGroup->orders()->update([
                'status' => 'cancelled',
            ]);

            if ($shouldRestock) {
                $requirements = $orderGroup->inventory_material_requirements ?? [];
                $this->stockService->restoreFromRequirements($requirements);
                $orderGroup->update([
                    'inventory_restored_at' => now(),
                ]);
            }
        });

        $orderGroup->refresh()->load([
            'orders.product:id,name,cover_image_path',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    /**
     * Fetch order template with all related configurations for a product.
     * GET /api/customer-orders/product/{productId}/template
     *
     * Returns: template, options, pricings, discounts, min_order, layout_fee, rush_fees
     */
    public function getProductOrderTemplate(Request $request, int $productId): JsonResponse
    {
        try {
            $selectedOptionTypeIds = $this->resolveSelectedOptionTypeIdsFromQuery($request);

            $product = Product::find($productId);

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                    'error_code' => 'product_not_found',
                ], 404);
            }

            $orderTemplate = $product->orderTemplate()
                ->with([
                    'options.optionTypes' => fn ($q) => $q->orderBy('position'),
                    'pricings',
                    'discounts',
                    'minOrder',
                    'layoutFee',
                ])
                ->first();

            if (! $orderTemplate) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product is not yet configured for ordering.',
                    'error_code' => 'template_not_configured',
                ], 404);
            }

            // Get all available rush fees for the client to choose from
            $rushFees = RushFee::with([
                'timeframes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
                ->orderBy('min_price')
                ->get();

            $maxOrderQuantity = null;

            if (! empty($selectedOptionTypeIds)) {
                try {
                    $maxOrderQuantity = $this->stockService->calculateMaxOrderQuantityForOrderLine([
                        'product_id' => (int) $product->id,
                        'selected_option_type_ids' => $selectedOptionTypeIds,
                    ]);
                } catch (InvalidInventoryConfigurationException) {
                    $maxOrderQuantity = null;
                }
            }

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
                        'max_price' => $rf->max_price !== null ? (float) $rf->max_price : null,
                        'timeframes' => $rf->timeframes->map(fn ($tf) => [
                            'id' => $tf->id,
                            'label' => $tf->label,
                            'percentage' => (float)$tf->percentage,
                        ]),
                    ]),
                    'inventory' => [
                        'buffer_rule' => 'allow_at_threshold_block_below',
                        'max_order_quantity' => $maxOrderQuantity,
                        'selected_option_type_ids' => $selectedOptionTypeIds,
                    ],
                ],
            ]);
        } catch (QueryException $e) {
            if (SchemaMismatchDetector::isMissingOrderTemplateDeletedAt($e)) {
                return response()->json(
                    SchemaMismatchDetector::buildPayload('Failed to fetch order template due to database schema mismatch.'),
                    500
                );
            }

            logger()->error('Order template fetch query failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order template',
            ], 500);
        } catch (\Exception $e) {
            logger()->error('Order template fetch failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order template',
            ], 500);
        }
    }

    /**
     * @return array<int, int>
     */
    private function resolveSelectedOptionTypeIdsFromQuery(Request $request): array
    {
        $rawValue = $request->query('selected_option_type_ids', []);

        if (! is_array($rawValue)) {
            $rawValue = array_filter(array_map('trim', explode(',', (string) $rawValue)));
        }

        return collect($rawValue)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
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
            $this->normalizeGeneralDriveLink($request);

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
                'general_drive_link' => ['nullable', 'string', 'max:2048', new GoogleDriveUrl()],
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
                    'payment_status' => 'awaiting_payment',
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
                'order_template_id' => (int) $order->order_template_id,
                'rush_fee_id' => $order->rush_fee_id !== null ? (int) $order->rush_fee_id : null,
                'selected_options' => $order->selected_options,
                'formatted_options' => $order->formatted_options,
                'special_instructions' => $order->special_instructions,
                'base_price' => (float) $order->base_price,
                'discount_amount' => (float) $order->discount_amount,
                'rush_fee_amount' => (float) $order->rush_fee_amount,
                'layout_fee_amount' => (float) $order->layout_fee_amount,
                'min_order_quantity' => (int) ($order->orderTemplate?->minOrder?->min_quantity ?? 1),
                'option_schema' => $this->buildOptionSchema($order),
            ]);
        })->values();

        return [
            'id' => $group->id,
            'status' => $group->status,
            'status_label' => $group->status_label,
            'payment_status' => (string) $group->payment_status,
            'payment_status_label' => $group->payment_status_label ?? $group->payment_status,
            'payment_proof_url' => $group->payment_proof_path
                ? asset('storage/'.$group->payment_proof_path)
                : null,
            'cancellation_reason' => $group->cancellation_reason,
            'can_view_details' => true,
            'can_cancel' => $group->canCustomerCancel(),
            'can_pay_now' => $group->canSubmitPaymentProof(),
            'is_editable' => $group->status === 'waiting',
            'general_drive_link' => $group->general_drive_link,
            'payment_method' => $group->payment_method,
            'payment_reference_number' => $group->payment_reference_number,
            'payment_submitted_at' => $group->payment_submitted_at?->toISOString(),
            'payment_confirmed_at' => $group->payment_confirmed_at?->toISOString(),

            'totals' => [
                'subtotal_price' => (float) $group->subtotal_price,
                'discount_total' => (float) $group->discount_total,
                'rush_fee_total' => (float) $group->rush_fee_total,
                'layout_fee_total' => (float) $group->layout_fee_total,
                'total_price' => (float) $group->total_price,
            ],
            'items_count' => $orders->count(),
            'orders' => $orders,
            'rush_fee_options' => $withFullOrderPayload ? $this->buildRushFeeOptions() : [],
            'created_at' => $group->created_at?->toISOString(),
            'updated_at' => $group->updated_at?->toISOString(),
        ];
    }
    private function buildNotEditableResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Only orders waiting for approval can be edited.',
            'error_code' => 'customer_order_not_editable',
        ], 422);
    }

    /**
     * @param array<int, array<string, mixed>> $previousRequirements
     * @param array<int, array<string, mixed>> $newRequirements
     * @return array{0: array<int, array<string, int|string>>, 1: array<int, array<string, int|string>>}
     */

    private function buildRequirementDelta(array $previousRequirements, array $newRequirements): array
    {
        $mapByMaterial = static function (array $requirements): array {
            $mapped = [];

            foreach ($requirements as $requirement) {
                $materialId = (int) ($requirement['material_id'] ?? 0);
                if ($materialId <= 0) {
                    continue;
                }

                $mapped[$materialId] = [
                    'material_id' => $materialId,
                    'material_name' => (string) ($requirement['material_name'] ?? 'Unknown Material'),
                    'required' => (int) ($requirement['required'] ?? 0),
                ];
            }

            return $mapped;
        };

        $previousMap = $mapByMaterial($previousRequirements);
        $newMap = $mapByMaterial($newRequirements);

        $materialIds = array_values(array_unique(array_merge(
            array_keys($previousMap),
            array_keys($newMap)
        )));

        $deductions = [];
        $restores = [];

        foreach ($materialIds as $materialId) {
            $oldRequired = (int) ($previousMap[$materialId]['required'] ?? 0);
            $newRequired = (int) ($newMap[$materialId]['required'] ?? 0);
            $materialName = (string) ($newMap[$materialId]['material_name'] ?? $previousMap[$materialId]['material_name'] ?? 'Unknown Material');

            if ($newRequired > $oldRequired) {
                $deductions[] = [
                    'material_id' => (int) $materialId,
                    'material_name' => $materialName,
                    'required' => $newRequired - $oldRequired,
                ];
                continue;
            }

            if ($oldRequired > $newRequired) {
                $restores[] = [
                    'material_id' => (int) $materialId,
                    'material_name' => $materialName,
                    'required' => $oldRequired - $newRequired,
                ];
            }
        }

        return [$deductions, $restores];
    }

    private function buildOptionSchema(CustomerOrder $order): array
    {
        $template = $order->orderTemplate;
        if (! $template) {
            return [];
        }

        $selected = $order->selected_options ?? [];

        return $template->options
            ->sortBy('position')
            ->map(function ($option) use ($selected): array {
                $optionKey = (string) $option->id;
                $selectedTypeId = $selected[$optionKey] ?? $selected[$option->id] ?? null;

                return [
                    'id' => (int) $option->id,
                    'label' => (string) $option->label,
                    'position' => (int) $option->position,
                    'selected_type_id' => $selectedTypeId !== null ? (int) $selectedTypeId : null,
                    'types' => $option->optionTypes
                        ->where('is_available', true)
                        ->sortBy('position')
                        ->map(fn ($type): array => [
                            'id' => (int) $type->id,
                            'type_name' => (string) $type->type_name,
                            'position' => (int) $type->position,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildRushFeeOptions(): array
    {
        return RushFee::query()
            ->with([
                'timeframes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->orderBy('label')
            ->get()
            ->map(function (RushFee $rushFee): array {
                return [
                    'id' => (int) $rushFee->id,
                    'label' => (string) $rushFee->label,
                    'timeframes' => $rushFee->timeframes
                        ->map(fn ($timeframe): array => [
                            'id' => (int) $timeframe->id,
                            'label' => (string) $timeframe->label,
                            'percentage' => (float) $timeframe->percentage,
                            'sort_order' => (int) $timeframe->sort_order,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeGeneralDriveLink(Request $request): void
    {
        if (! $request->has('general_drive_link')) {
            return;
        }

        $request->merge([
            'general_drive_link' => GoogleDriveUrl::normalize($request->input('general_drive_link')),
        ]);
    }
}
