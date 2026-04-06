<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\OrderTemplateOptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    /**
     * Get all materials with their associated products.
     * GET /api/materials
     */
    public function index(): JsonResponse
    {
        try {
            $materials = Material::with([
                'consumptions.product:id,name',
                'consumptions.optionType:id,type_name',
            ])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $materials->map(fn (Material $material) => $this->transformMaterial($material))->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch materials',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new material.
     * POST /api/materials
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateMaterialPayload($request);

            $material = DB::transaction(function () use ($validated) {
                $material = Material::create([
                    'name' => $validated['name'],
                    'units' => $validated['units'],
                    'low_stock_threshold' => $validated['low_stock_threshold'],
                    'description' => $validated['description'] ?? null,
                ]);

                $this->syncConsumptions($material, $validated['consumptions'] ?? []);

                return $material;
            });

            $material->load([
                'consumptions.product:id,name',
                'consumptions.optionType:id,type_name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material created successfully',
                'data' => $this->transformMaterial($material),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single material with its products.
     * GET /api/materials/{id}
     */
    public function show(Material $material): JsonResponse
    {
        try {
            $material->load([
                'consumptions.product:id,name',
                'consumptions.optionType:id,type_name',
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->transformMaterial($material),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a material.
     * PUT /api/materials/{id}
     */
    public function update(Request $request, Material $material): JsonResponse
    {
        try {
            $validated = $this->validateMaterialPayload($request, $material);

            DB::transaction(function () use ($material, $validated) {
                $material->update([
                    'name' => $validated['name'],
                    'units' => $validated['units'],
                    'low_stock_threshold' => $validated['low_stock_threshold'],
                    'description' => $validated['description'] ?? null,
                ]);

                $this->syncConsumptions($material, $validated['consumptions'] ?? []);
            });

            $material->refresh()->load([
                'consumptions.product:id,name',
                'consumptions.optionType:id,type_name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material updated successfully',
                'data' => $this->transformMaterial($material),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a material.
     * DELETE /api/materials/{id}
     */
    public function destroy(Material $material): JsonResponse
    {
        try {
            DB::transaction(function () use ($material) {
                $material->consumptions()->delete();
                $material->products()->detach();
                $material->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Material deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMaterialPayload(Request $request, ?Material $material = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('materials', 'name')->ignore($material?->id),
            ],
            'units' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'consumptions' => 'nullable|array',
            'consumptions.*.product_id' => 'required|integer|exists:products,id',
            'consumptions.*.order_template_option_type_id' => 'nullable|integer|exists:order_template_option_types,id',
            'consumptions.*.quantity' => 'required|integer|min:1',
        ]);

        $normalizedConsumptions = $this->normalizeConsumptions($validated['consumptions'] ?? []);

        $optionTypeErrors = $this->validateOptionTypeOwnership($normalizedConsumptions);
        if (! empty($optionTypeErrors)) {
            throw ValidationException::withMessages($optionTypeErrors);
        }

        $validated['consumptions'] = $normalizedConsumptions;

        return $validated;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{product_id:int, order_template_option_type_id:int|null, quantity:int}>
     */
    private function normalizeConsumptions(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $optionTypeId = $row['order_template_option_type_id'] ?? null;
            $optionTypeId = $optionTypeId !== null ? (int) $optionTypeId : null;
            $quantity = max(1, (int) ($row['quantity'] ?? 0));

            if ($productId <= 0) {
                continue;
            }

            $key = $productId . '|' . ($optionTypeId ?? 'any');

            if (! isset($normalized[$key])) {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'order_template_option_type_id' => $optionTypeId,
                    'quantity' => $quantity,
                ];
                continue;
            }

            $normalized[$key]['quantity'] += $quantity;
        }

        return array_values($normalized);
    }

    /**
     * @param array<int, array{product_id:int, order_template_option_type_id:int|null, quantity:int}> $rows
     * @return array<string, string>
     */
    private function validateOptionTypeOwnership(array $rows): array
    {
        $optionTypeIds = collect($rows)
            ->pluck('order_template_option_type_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($optionTypeIds)) {
            return [];
        }

        $optionTypes = OrderTemplateOptionType::query()
            ->with('option.orderTemplate:id,product_id')
            ->whereIn('id', $optionTypeIds)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($rows as $index => $row) {
            $optionTypeId = $row['order_template_option_type_id'];

            if ($optionTypeId === null) {
                continue;
            }

            $optionType = $optionTypes->get($optionTypeId);
            $optionProductId = (int) ($optionType?->option?->orderTemplate?->product_id ?? 0);

            if (! $optionType || $optionProductId !== (int) $row['product_id']) {
                $errors["consumptions.{$index}.order_template_option_type_id"] =
                    'Selected option type must belong to the selected product.';
            }
        }

        return $errors;
    }

    /**
     * @param array<int, array{product_id:int, order_template_option_type_id:int|null, quantity:int}> $rows
     */
    private function syncConsumptions(Material $material, array $rows): void
    {
        $material->consumptions()->delete();

        if (! empty($rows)) {
            $material->consumptions()->createMany($rows);
        }

        $legacyProductSync = [];
        foreach ($rows as $row) {
            if ($row['order_template_option_type_id'] !== null) {
                continue;
            }

            $productId = (int) $row['product_id'];
            if (! isset($legacyProductSync[$productId])) {
                $legacyProductSync[$productId] = ['quantity' => 0];
            }

            $legacyProductSync[$productId]['quantity'] += (int) $row['quantity'];
        }

        if (! empty($legacyProductSync)) {
            $material->products()->sync($legacyProductSync);
            return;
        }

        $material->products()->detach();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMaterial(Material $material): array
    {
        $consumptions = $material->consumptions
            ->map(function (Model $consumption): array {
                return [
                    'id' => (int) $consumption->id,
                    'product_id' => (int) $consumption->product_id,
                    'product_name' => (string) ($consumption->product?->name ?? 'Unknown Product'),
                    'order_template_option_type_id' => $consumption->order_template_option_type_id !== null
                        ? (int) $consumption->order_template_option_type_id
                        : null,
                    'option_type_name' => (string) ($consumption->optionType?->type_name ?? ''),
                    'is_fallback' => $consumption->order_template_option_type_id === null,
                    'quantity' => (int) $consumption->quantity,
                ];
            })
            ->values();

        $legacyProducts = $consumptions
            ->filter(fn (array $row): bool => $row['order_template_option_type_id'] === null)
            ->groupBy('product_id')
            ->map(function ($rows): array {
                $first = $rows->first();

                return [
                    'id' => (int) ($first['product_id'] ?? 0),
                    'name' => (string) ($first['product_name'] ?? 'Unknown Product'),
                    'pivot' => [
                        'quantity' => (int) $rows->sum('quantity'),
                    ],
                ];
            })
            ->values();

        return [
            'id' => (int) $material->id,
            'name' => (string) $material->name,
            'units' => (int) $material->units,
            'low_stock_threshold' => (int) $material->low_stock_threshold,
            'description' => $material->description,
            'created_at' => $material->created_at,
            'updated_at' => $material->updated_at,
            'products' => $legacyProducts,
            'consumptions' => $consumptions,
        ];
    }
}
