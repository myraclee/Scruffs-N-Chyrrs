<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\OrderTemplate;
use App\Models\RushFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerOrderController extends Controller
{
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
                    'options.optionTypes' => fn($q) => $q->orderBy('position'),
                    'pricings',
                    'discounts',
                    'minOrder',
                    'layoutFee',
                ])
                ->firstOrFail();

            // Get all available rush fees for the client to choose from
            $rushFees = RushFee::with('timeframes')
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
                        'options' => $orderTemplate->options->map(fn($opt) => [
                            'id' => $opt->id,
                            'label' => $opt->label,
                            'position' => $opt->position,
                            'option_types' => $opt->optionTypes->map(fn($type) => [
                                'id' => $type->id,
                                'type_name' => $type->type_name,
                                'is_available' => $type->is_available,
                                'position' => $type->position,
                            ]),
                        ]),
                        'pricings' => $orderTemplate->pricings->map(fn($p) => [
                            'combination_key' => $p->combination_key,
                            'price' => (float)$p->price,
                        ]),
                        'discounts' => $orderTemplate->discounts->map(fn($d) => [
                            'min_quantity' => $d->min_quantity,
                            'price_reduction' => (float)$d->price_reduction,
                            'position' => $d->position,
                        ])->sortBy('min_quantity')->values(),
                        'min_order' => $orderTemplate->minOrder->min_quantity ?? 1,
                        'layout_fee' => $orderTemplate->layoutFee?->fee_amount ? (float)$orderTemplate->layoutFee->fee_amount : 0,
                    ],
                    'rush_fees' => $rushFees->map(fn($rf) => [
                        'id' => $rf->id,
                        'label' => $rf->label,
                        'min_price' => (float)$rf->min_price,
                        'max_price' => (float)$rf->max_price,
                        'timeframes' => $rf->timeframes->map(fn($tf) => [
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
            ]);

            $product = Product::findOrFail($validated['product_id']);
            $orderTemplate = OrderTemplate::findOrFail($validated['order_template_id']);
            $quantity = $validated['quantity'];

            // Validate that product has this order template
            if ($product->orderTemplate->id !== $orderTemplate->id) {
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

            // Calculate pricing
            $pricing = $this->calculateOrderPricing(
                $orderTemplate,
                $validated['selected_options'],
                $quantity,
                $validated['rush_fee_id'] ?? null
            );

            if (!isset($pricing['success']) || !$pricing['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $pricing['message'] ?? 'Failed to calculate pricing',
                ], 422);
            }

            // Store the order in a transaction
            $order = DB::transaction(function () use ($validated, $pricing) {
                return CustomerOrder::create([
                    'user_id' => Auth::id(),
                    'product_id' => $validated['product_id'],
                    'order_template_id' => $validated['order_template_id'],
                    'rush_fee_id' => $validated['rush_fee_id'] ?? null,
                    'selected_options' => $validated['selected_options'],
                    'quantity' => $validated['quantity'],
                    'special_instructions' => $validated['special_instructions'] ?? null,
                    'base_price' => $pricing['base_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'rush_fee_amount' => $pricing['rush_fee_amount'],
                    'layout_fee_amount' => $pricing['layout_fee_amount'],
                    'total_price' => $pricing['total_price'],
                    'status' => 'pending',
                ]);
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
     * Calculate final pricing for an order based on selected options, quantity, and rush fee.
     * 
     * Returns: { success, base_price, discount_amount, rush_fee_amount, layout_fee_amount, total_price }
     */
    private function calculateOrderPricing(
        OrderTemplate $template,
        array $selectedOptions,
        int $quantity,
        ?int $rushFeeId = null
    ): array
    {
        try {
            // 1. Find base price from selected option combination
            $combinationKey = $this->buildCombinationKey($selectedOptions);
            
            $pricing = $template->pricings
                ->where('combination_key', $combinationKey)
                ->first();

            if (!$pricing) {
                return [
                    'success' => false,
                    'message' => 'Invalid option combination selected',
                ];
            }

            $basePrice = (float)$pricing->price * $quantity;

            // 2. Apply bulk discount if applicable
            $discountAmount = 0;
            $discount = $template->discounts
                ->where('min_quantity', '<=', $quantity)
                ->sortByDesc('min_quantity')
                ->first();

            if ($discount) {
                $discountAmount = (float)$discount->price_reduction * $quantity;
            }

            // 3. Add layout fee if applicable
            $layoutFeeAmount = 0;
            if ($template->layoutFee) {
                $layoutFeeAmount = (float)$template->layoutFee->fee_amount;
            }

            // 4. Add rush fee if selected
            $rushFeeAmount = 0;
            if ($rushFeeId) {
                $rushFee = RushFee::with('timeframes')->find($rushFeeId);
                if ($rushFee && $rushFee->timeframes->count() > 0) {
                    // Use the first timeframe's percentage
                    $timeframePercentage = (float)$rushFee->timeframes->first()->percentage;
                    $orderValue = $basePrice - $discountAmount + $layoutFeeAmount;
                    $rushFeeAmount = round($orderValue * ($timeframePercentage / 100), 2);
                }
            }

            $totalPrice = $basePrice - $discountAmount + $layoutFeeAmount + $rushFeeAmount;

            return [
                'success' => true,
                'base_price' => round($basePrice, 2),
                'discount_amount' => round($discountAmount, 2),
                'rush_fee_amount' => round($rushFeeAmount, 2),
                'layout_fee_amount' => round($layoutFeeAmount, 2),
                'total_price' => round($totalPrice, 2),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error calculating pricing: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build a combination key from selected option IDs.
     * Format: "1,2,3" where numbers are selected option type IDs
     */
    private function buildCombinationKey(array $selectedOptions): string
    {
        $ids = array_values($selectedOptions);
        sort($ids);
        return implode(',', $ids);
    }
}
