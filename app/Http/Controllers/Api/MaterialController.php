<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\OrderTemplateOptionType;
use Illuminate\Database\Eloquent\Builder;
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
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $this->validateIndexFilters($request);

            $materialsQuery = Material::query()->with([
                'consumptions.product:id,name',
                'consumptions.optionType:id,type_name',
            ]);

            $this->applySearchFilter(
                $materialsQuery,
                $filters['search'] ?? null,
            );

            $this->applyStockBandFilter(
                $materialsQuery,
                $filters['stock_band'] ?? null,
            );

            $this->applySort(
                $materialsQuery,
                $filters['sort_by'] ?? 'name',
                $filters['sort_direction'] ?? 'asc',
            );

            $materials = $materialsQuery->get();

            return response()->json([
                'success' => true,
                'data' => $materials->map(fn (Material $material) => $this->transformMaterial($material))->values(),
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
                    'max_units' => $validated['max_units'],
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
                    'max_units' => $validated['max_units'],
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
            'max_units' => 'nullable|integer|min:1|gte:units',
            'low_stock_threshold' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'consumptions' => 'nullable|array',
            'consumptions.*.product_id' => 'required|integer|exists:products,id',
            'consumptions.*.order_template_option_type_id' => 'nullable|integer|exists:order_template_option_types,id',
            'consumptions.*.quantity' => 'required|integer|min:1',
        ]);

        $validated['max_units'] = $this->normalizeMaxUnits(
            $validated['max_units'] ?? null,
            (int) $validated['units'],
            $material?->max_units,
        );

        $normalizedConsumptions = $this->normalizeConsumptions($validated['consumptions'] ?? []);

        $optionTypeErrors = $this->validateOptionTypeOwnership($normalizedConsumptions);
        if (! empty($optionTypeErrors)) {
            throw ValidationException::withMessages($optionTypeErrors);
        }

        $validated['consumptions'] = $normalizedConsumptions;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateIndexFilters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:255',
            'stock_band' => ['nullable', Rule::in(['high', 'medium', 'low', 'out_of_stock'])],
            'sort_by' => ['nullable', Rule::in(['name', 'units', 'max_units', 'low_stock_threshold', 'stock_percentage'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }

    private function applySearchFilter(Builder $query, ?string $search): void
    {
        $searchTerm = trim((string) $search);

        if ($searchTerm === '') {
            return;
        }

        $likeSearchTerm = '%' . $searchTerm . '%';

        $query->where(function (Builder $builder) use ($likeSearchTerm): void {
            $builder
                ->where('materials.name', 'like', $likeSearchTerm)
                ->orWhereHas('consumptions.product', function (Builder $productBuilder) use ($likeSearchTerm): void {
                    $productBuilder->where('name', 'like', $likeSearchTerm);
                });
        });
    }

    private function applyStockBandFilter(Builder $query, ?string $stockBand): void
    {
        if ($stockBand === null || $stockBand === '') {
            return;
        }

        $stockPercentageSql = $this->stockPercentageSql();

        if ($stockBand === 'out_of_stock') {
            $query->where('materials.units', '<=', 0);

            return;
        }

        if ($stockBand === 'low') {
            $query
                ->where('materials.units', '>', 0)
                ->whereRaw("{$stockPercentageSql} <= 29");

            return;
        }

        if ($stockBand === 'medium') {
            $query
                ->where('materials.units', '>', 0)
                ->whereRaw("{$stockPercentageSql} > 29")
                ->whereRaw("{$stockPercentageSql} <= 70");

            return;
        }

        if ($stockBand === 'high') {
            $query
                ->where('materials.units', '>', 0)
                ->whereRaw("{$stockPercentageSql} > 70");
        }
    }

    private function applySort(Builder $query, string $sortBy, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $normalizedSortBy = strtolower($sortBy);
        $stockPercentageSql = $this->stockPercentageSql();

        if ($normalizedSortBy === 'stock_percentage') {
            $query->orderByRaw("{$stockPercentageSql} {$direction}");
            $query->orderBy('materials.name');

            return;
        }

        $columnMap = [
            'name' => 'materials.name',
            'units' => 'materials.units',
            'max_units' => 'materials.max_units',
            'low_stock_threshold' => 'materials.low_stock_threshold',
        ];

        $sortColumn = $columnMap[$normalizedSortBy] ?? 'materials.name';

        $query->orderBy($sortColumn, $direction);

        if ($sortColumn !== 'materials.name') {
            $query->orderBy('materials.name');
        }
    }

    private function stockPercentageSql(): string
    {
        return '(materials.units * 100.0) / CASE WHEN materials.max_units > 0 THEN materials.max_units ELSE 1 END';
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

            $normalized[$key]['quantity'] = max(
                (int) $normalized[$key]['quantity'],
                $quantity,
            );
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
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMaterial(Material $material): array
    {
        $units = max(0, (int) $material->units);
        $maxUnits = $this->normalizeMaxUnits(
            $material->max_units,
            $units,
            $material->max_units,
        );
        $stockBand = $this->resolveStockBand($units, $maxUnits);
        $stockPercentage = $this->calculateStockPercentage($units, $maxUnits);

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
            'units' => $units,
            'max_units' => $maxUnits,
            'low_stock_threshold' => (int) $material->low_stock_threshold,
            'stock_percentage' => $stockPercentage,
            'stock_band' => $stockBand,
            'description' => $material->description,
            'created_at' => $material->created_at,
            'updated_at' => $material->updated_at,
            'products' => $legacyProducts,
            'consumptions' => $consumptions,
        ];
    }

    private function normalizeMaxUnits(?int $maxUnits, int $units, ?int $fallbackMaxUnits = null): int
    {
        $safeUnits = max(0, $units);
        $candidateMaxUnits = max(0, (int) ($maxUnits ?? 0));

        if ($candidateMaxUnits < 1) {
            $candidateMaxUnits = max(0, (int) ($fallbackMaxUnits ?? 0));
        }

        return max(1, $safeUnits, $candidateMaxUnits);
    }

    private function calculateStockPercentage(int $units, int $maxUnits): float
    {
        $safeMaxUnits = max(1, $maxUnits);

        return round(($units / $safeMaxUnits) * 100, 2);
    }

    private function resolveStockBand(int $units, int $maxUnits): string
    {
        if ($units <= 0) {
            return 'out_of_stock';
        }

        $stockPercentage = $this->calculateStockPercentage($units, $maxUnits);

        if ($stockPercentage <= 29.0) {
            return 'low';
        }

        if ($stockPercentage <= 70.0) {
            return 'medium';
        }

        return 'high';
    }
}
