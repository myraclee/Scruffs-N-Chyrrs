<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\OrderTemplateDiscount;
use App\Models\OrderTemplateMinOrder;
use App\Models\OrderTemplateLayoutFee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderTemplateController extends Controller
{
    /**
     * Get all order templates with their relationships.
     * GET /api/order-templates
     */
    public function index(): JsonResponse
    {
        try {
            $templates = OrderTemplate::with([
                'product',
                'options.optionTypes',
                'pricings',
                'discounts',
                'minOrder',
                'layoutFee',
            ])->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order templates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new order template with nested options, pricings, and discounts.
     * POST /api/order-templates
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'options' => 'required|array|min:1',
                'options.*.label' => 'required|string|max:255',
                'options.*.position' => 'required|integer|min:0',
                'options.*.option_types' => 'required|array|min:1',
                'options.*.option_types.*.type_name' => 'required|string|max:255',
                'options.*.option_types.*.is_available' => 'required|boolean',
                'options.*.option_types.*.position' => 'required|integer|min:0',
                'pricings' => 'required|array|min:1',
                'pricings.*.combination_key' => 'required|string|max:500',
                'pricings.*.price' => 'required|numeric|min:0',
                'discounts' => 'nullable|array',
                'discounts.*.min_quantity' => 'required_with:discounts|integer|min:1',
                'discounts.*.price_reduction' => 'required_with:discounts|numeric|min:0',
                'discounts.*.position' => 'required_with:discounts|integer|min:0',
                'min_order' => 'nullable|integer|min:1',
                'layout_fee' => 'nullable|numeric|min:0',
            ]);

            $template = DB::transaction(function () use ($validated) {
                // Create the main order template
                $template = OrderTemplate::create([
                    'product_id' => $validated['product_id'],
                ]);

                // Create options and their types
                foreach ($validated['options'] as $optionData) {
                    $option = OrderTemplateOption::create([
                        'order_template_id' => $template->id,
                        'label' => $optionData['label'],
                        'position' => $optionData['position'],
                    ]);

                    foreach ($optionData['option_types'] as $typeData) {
                        OrderTemplateOptionType::create([
                            'order_template_option_id' => $option->id,
                            'type_name' => $typeData['type_name'],
                            'is_available' => $typeData['is_available'],
                            'position' => $typeData['position'],
                        ]);
                    }
                }

                // Create pricings
                foreach ($validated['pricings'] as $pricingData) {
                    OrderTemplatePricing::create([
                        'order_template_id' => $template->id,
                        'combination_key' => $pricingData['combination_key'],
                        'price' => $pricingData['price'],
                    ]);
                }

                // Create discounts if provided
                if (!empty($validated['discounts'])) {
                    foreach ($validated['discounts'] as $discountData) {
                        OrderTemplateDiscount::create([
                            'order_template_id' => $template->id,
                            'min_quantity' => $discountData['min_quantity'],
                            'price_reduction' => $discountData['price_reduction'],
                            'position' => $discountData['position'],
                        ]);
                    }
                }

                // Create minimum order if provided
                if (!empty($validated['min_order'])) {
                    OrderTemplateMinOrder::create([
                        'order_template_id' => $template->id,
                        'min_quantity' => $validated['min_order'],
                    ]);
                }

                // Create layout fee if provided
                if (!empty($validated['layout_fee'])) {
                    OrderTemplateLayoutFee::create([
                        'order_template_id' => $template->id,
                        'fee_amount' => $validated['layout_fee'],
                    ]);
                }

                return $template;
            });

            return response()->json([
                'success' => true,
                'message' => 'Order template created successfully',
                'data' => $template->load(['product', 'options.optionTypes', 'pricings', 'discounts', 'minOrder', 'layoutFee']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single order template with all relationships.
     * GET /api/order-templates/{id}
     */
    public function show(OrderTemplate $orderTemplate): JsonResponse
    {
        try {
            $template = $orderTemplate->load(['product', 'options.optionTypes', 'pricings', 'discounts', 'minOrder', 'layoutFee']);

            return response()->json([
                'success' => true,
                'data' => $template,
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
     * Update an order template with nested data.
     * PUT /api/order-templates/{id}
     */
    public function update(Request $request, OrderTemplate $orderTemplate): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'options' => 'required|array|min:1',
                'options.*.label' => 'required|string|max:255',
                'options.*.position' => 'required|integer|min:0',
                'options.*.option_types' => 'required|array|min:1',
                'options.*.option_types.*.type_name' => 'required|string|max:255',
                'options.*.option_types.*.is_available' => 'required|boolean',
                'options.*.option_types.*.position' => 'required|integer|min:0',
                'pricings' => 'required|array|min:1',
                'pricings.*.combination_key' => 'required|string|max:500',
                'pricings.*.price' => 'required|numeric|min:0',
                'discounts' => 'nullable|array',
                'discounts.*.min_quantity' => 'required_with:discounts|integer|min:1',
                'discounts.*.price_reduction' => 'required_with:discounts|numeric|min:0',
                'discounts.*.position' => 'required_with:discounts|integer|min:0',
                'min_order' => 'nullable|integer|min:1',
                'layout_fee' => 'nullable|numeric|min:0',
            ]);

            $updated = DB::transaction(function () use ($validated, $orderTemplate) {
                // Update the main product
                $orderTemplate->update([
                    'product_id' => $validated['product_id'],
                ]);

                // Delete existing nested records
                $orderTemplate->options()->delete(); // Cascades to option types
                $orderTemplate->pricings()->delete();
                $orderTemplate->discounts()->delete();
                $orderTemplate->minOrder()->delete();
                $orderTemplate->layoutFee()->delete();

                // Create new options and their types
                foreach ($validated['options'] as $optionData) {
                    $option = OrderTemplateOption::create([
                        'order_template_id' => $orderTemplate->id,
                        'label' => $optionData['label'],
                        'position' => $optionData['position'],
                    ]);

                    foreach ($optionData['option_types'] as $typeData) {
                        OrderTemplateOptionType::create([
                            'order_template_option_id' => $option->id,
                            'type_name' => $typeData['type_name'],
                            'is_available' => $typeData['is_available'],
                            'position' => $typeData['position'],
                        ]);
                    }
                }

                // Create new pricings
                foreach ($validated['pricings'] as $pricingData) {
                    OrderTemplatePricing::create([
                        'order_template_id' => $orderTemplate->id,
                        'combination_key' => $pricingData['combination_key'],
                        'price' => $pricingData['price'],
                    ]);
                }

                // Create new discounts if provided
                if (!empty($validated['discounts'])) {
                    foreach ($validated['discounts'] as $discountData) {
                        OrderTemplateDiscount::create([
                            'order_template_id' => $orderTemplate->id,
                            'min_quantity' => $discountData['min_quantity'],
                            'price_reduction' => $discountData['price_reduction'],
                            'position' => $discountData['position'],
                        ]);
                    }
                }

                // Create minimum order if provided
                if (!empty($validated['min_order'])) {
                    OrderTemplateMinOrder::create([
                        'order_template_id' => $orderTemplate->id,
                        'min_quantity' => $validated['min_order'],
                    ]);
                }

                // Create layout fee if provided
                if (!empty($validated['layout_fee'])) {
                    OrderTemplateLayoutFee::create([
                        'order_template_id' => $orderTemplate->id,
                        'fee_amount' => $validated['layout_fee'],
                    ]);
                }

                return $orderTemplate;
            });

            return response()->json([
                'success' => true,
                'message' => 'Order template updated successfully',
                'data' => $updated->load(['product', 'options.optionTypes', 'pricings', 'discounts', 'minOrder', 'layoutFee']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an order template (cascades to nested records).
     * DELETE /api/order-templates/{id}
     */
    public function destroy(OrderTemplate $orderTemplate): JsonResponse
    {
        try {
            $orderTemplate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order template deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
