<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerCartItem;
use App\Models\CustomerOrder;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\OrderTemplateDiscount;
use App\Models\OrderTemplateMinOrder;
use App\Models\OrderTemplateLayoutFee;
use App\Support\SchemaMismatchDetector;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        } catch (QueryException $e) {
            if (SchemaMismatchDetector::isMissingOrderTemplateDeletedAt($e)) {
                return response()->json(
                    SchemaMismatchDetector::buildPayload('Failed to fetch order templates due to database schema mismatch.'),
                    500
                );
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order templates',
                'error' => $e->getMessage(),
            ], 500);
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

                $template->load('options.optionTypes');

                // Create pricings
                foreach ($validated['pricings'] as $pricingData) {
                    $normalizedKey = $this->normalizePricingKeyForTemplate(
                        $template,
                        (string) $pricingData['combination_key']
                    );

                    if ($normalizedKey === null) {
                        throw ValidationException::withMessages([
                            'pricings' => [
                                "Invalid pricing combination key: {$pricingData['combination_key']}.",
                            ],
                        ]);
                    }

                    OrderTemplatePricing::create([
                        'order_template_id' => $template->id,
                        'combination_key' => $normalizedKey,
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

                $orderTemplate->load('options.optionTypes');

                // Create new pricings
                foreach ($validated['pricings'] as $pricingData) {
                    $normalizedKey = $this->normalizePricingKeyForTemplate(
                        $orderTemplate,
                        (string) $pricingData['combination_key']
                    );

                    if ($normalizedKey === null) {
                        throw ValidationException::withMessages([
                            'pricings' => [
                                "Invalid pricing combination key: {$pricingData['combination_key']}.",
                            ],
                        ]);
                    }

                    OrderTemplatePricing::create([
                        'order_template_id' => $orderTemplate->id,
                        'combination_key' => $normalizedKey,
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
        $usage = $this->getTemplateUsageCounts($orderTemplate);

        if ($usage['active_order_count'] > 0 || $usage['cart_item_count'] > 0) {
            return $this->buildTemplateInUseResponse($usage);
        }

        try {
            $orderTemplate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order template deleted successfully',
            ]);
        } catch (QueryException $e) {
            if ($this->isForeignKeyConstraintViolation($e)) {
                $latestUsage = $this->getTemplateUsageCounts($orderTemplate);

                return $this->buildTemplateInUseResponse($latestUsage);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order template',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function normalizePricingKeyForTemplate(OrderTemplate $template, string $combinationKey): ?string
    {
        $typeIds = $this->resolveCombinationKeyToTypeIds($template, $combinationKey);

        if (empty($typeIds)) {
            return null;
        }

        return implode(',', $typeIds);
    }

    /**
     * @return array<int, int>
     */
    private function resolveCombinationKeyToTypeIds(OrderTemplate $template, string $combinationKey): array
    {
        $trimmedKey = trim($combinationKey);
        if ($trimmedKey === '') {
            return [];
        }

        if (preg_match('/^\d+(\s*,\s*\d+)*$/', $trimmedKey) === 1) {
            return $this->canonicalizeTypeIds(
                preg_split('/\s*,\s*/', $trimmedKey) ?: []
            );
        }

        $orderedOptions = $template->options->sortBy('position')->values();

        if (str_contains($trimmedKey, '|')) {
            $segments = array_values(array_filter(
                array_map(static fn ($value) => trim($value), explode('|', $trimmedKey)),
                static fn ($value) => $value !== ''
            ));

            if ($orderedOptions->count() === count($segments)) {
                $resolved = [];

                foreach ($orderedOptions as $index => $option) {
                    $resolvedTypeId = $this->resolveTypeIdByOptionToken(
                        $option->optionTypes,
                        $segments[$index]
                    );

                    if ($resolvedTypeId === null) {
                        $resolved = [];
                        break;
                    }

                    $resolved[] = $resolvedTypeId;
                }

                if (! empty($resolved)) {
                    return $this->canonicalizeTypeIds($resolved);
                }
            }
        }

        $tokens = array_values(array_filter(
            preg_split('/\||_/', $trimmedKey) ?: [],
            static fn ($value) => trim((string) $value) !== ''
        ));

        if (empty($tokens)) {
            return [];
        }

        $typeNameMap = [];
        foreach ($orderedOptions as $option) {
            foreach ($option->optionTypes as $type) {
                $normalized = $this->normalizePricingToken((string) $type->type_name);
                if ($normalized === '') {
                    continue;
                }

                if (! array_key_exists($normalized, $typeNameMap)) {
                    $typeNameMap[$normalized] = [];
                }

                $typeNameMap[$normalized][] = (int) $type->id;
            }
        }

        $resolvedIds = [];

        foreach ($tokens as $token) {
            $normalizedToken = $this->normalizePricingToken((string) $token);
            if ($normalizedToken === '' || ! array_key_exists($normalizedToken, $typeNameMap)) {
                return [];
            }

            $resolvedIds[] = (int) $typeNameMap[$normalizedToken][0];
        }

        return $this->canonicalizeTypeIds($resolvedIds);
    }

    private function resolveTypeIdByOptionToken($optionTypes, string $token): ?int
    {
        $normalizedToken = $this->normalizePricingToken($token);
        if ($normalizedToken === '') {
            return null;
        }

        $matchedType = $optionTypes->first(function ($type) use ($normalizedToken): bool {
            return $this->normalizePricingToken((string) $type->type_name) === $normalizedToken;
        });

        return $matchedType ? (int) $matchedType->id : null;
    }

    /**
     * @param array<int, int|string> $typeIds
     * @return array<int, int>
     */
    private function canonicalizeTypeIds(array $typeIds): array
    {
        $normalized = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $typeIds
        ), static fn ($value): bool => $value > 0));

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private function normalizePricingToken(string $token): string
    {
        $normalized = strtolower(trim($token));
        $normalized = preg_replace('/\s*(inch|inches)\s*$/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    /**
     * @return array{active_order_count:int, order_count:int, total_order_count:int, cart_item_count:int}
     */
    private function getTemplateUsageCounts(OrderTemplate $orderTemplate): array
    {
        $totalOrderCount = CustomerOrder::where('order_template_id', $orderTemplate->id)
            ->count();

        return [
            // Legacy key retained for frontend compatibility.
            'active_order_count' => $totalOrderCount,
            // Keep legacy key for backwards compatibility in frontend consumers.
            'order_count' => $totalOrderCount,
            'total_order_count' => $totalOrderCount,
            'cart_item_count' => CustomerCartItem::where('order_template_id', $orderTemplate->id)->count(),
        ];
    }

    /**
     * @param array{active_order_count:int, order_count:int, total_order_count:int, cart_item_count:int} $usage
     */
    private function buildTemplateInUseResponse(array $usage): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Cannot delete order template because it is currently used by customer orders or cart items.',
            'error_code' => 'order_template_in_use',
            'active_order_count' => $usage['active_order_count'],
            'order_count' => $usage['order_count'],
            'total_order_count' => $usage['total_order_count'],
            'cart_item_count' => $usage['cart_item_count'],
        ], 409);
    }

    private function isForeignKeyConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) $e->getCode();

        return $sqlState === '23000' || $sqlState === '23503';
    }
}
