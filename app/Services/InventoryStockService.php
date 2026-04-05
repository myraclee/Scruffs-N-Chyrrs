<?php

namespace App\Services;

use App\Exceptions\InsufficientMaterialStockException;
use App\Models\Material;
use App\Models\Product;

class InventoryStockService
{
    /**
     * @param array<int, array{product_id:int, quantity:int}> $orderLines
     * @return array<int, array{material_id:int, material_name:string, required:int}>
     */
    public function calculateRequirements(array $orderLines): array
    {
        $normalizedLines = collect($orderLines)
            ->map(function (array $line): array {
                return [
                    'product_id' => (int) ($line['product_id'] ?? 0),
                    'quantity' => (int) ($line['quantity'] ?? 0),
                ];
            })
            ->filter(fn (array $line): bool => $line['product_id'] > 0 && $line['quantity'] > 0)
            ->values();

        if ($normalizedLines->isEmpty()) {
            return [];
        }

        $products = Product::with('materials:id,name,units')
            ->whereIn('id', $normalizedLines->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $requirements = [];

        foreach ($normalizedLines as $line) {
            $product = $products->get($line['product_id']);

            if (! $product) {
                continue;
            }

            foreach ($product->materials as $material) {
                $perUnit = (int) ($material->pivot->quantity ?? 0);

                if ($perUnit <= 0) {
                    continue;
                }

                $required = $line['quantity'] * $perUnit;

                if (! isset($requirements[$material->id])) {
                    $requirements[$material->id] = [
                        'material_id' => (int) $material->id,
                        'material_name' => (string) $material->name,
                        'required' => $required,
                    ];
                    continue;
                }

                $requirements[$material->id]['required'] += $required;
            }
        }

        return array_values($requirements);
    }

    /**
     * @param array<int, array{material_id:int, material_name:string, required:int}> $requirements
     * @return array<int, array{material_id:int, material_name:string, required:int}>
     *
     * @throws InsufficientMaterialStockException
     */
    public function deductFromRequirements(array $requirements): array
    {
        if (empty($requirements)) {
            return [];
        }

        $materialIds = collect($requirements)
            ->pluck('material_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $materials = Material::query()
            ->whereIn('id', $materialIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $shortages = [];

        foreach ($requirements as $requirement) {
            $materialId = (int) $requirement['material_id'];
            $required = (int) $requirement['required'];
            $material = $materials->get($materialId);
            $available = (int) ($material?->units ?? 0);

            if (! $material || $available < $required) {
                $shortages[] = [
                    'material_id' => $materialId,
                    'material_name' => (string) ($material?->name ?? $requirement['material_name']),
                    'required' => $required,
                    'available' => $available,
                    'deficit' => max($required - $available, 0),
                ];
            }
        }

        if (! empty($shortages)) {
            throw new InsufficientMaterialStockException($shortages);
        }

        foreach ($requirements as $requirement) {
            $material = $materials->get((int) $requirement['material_id']);
            if (! $material) {
                continue;
            }

            $material->units = max(0, (int) $material->units - (int) $requirement['required']);
            $material->save();
        }

        return $requirements;
    }

    /**
     * @param array<int, array{material_id:int, material_name:string, required:int}> $requirements
     */
    public function restoreFromRequirements(array $requirements): void
    {
        if (empty($requirements)) {
            return;
        }

        $materialIds = collect($requirements)
            ->pluck('material_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $materials = Material::query()
            ->whereIn('id', $materialIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requirements as $requirement) {
            $material = $materials->get((int) $requirement['material_id']);
            if (! $material) {
                continue;
            }

            $material->units = (int) $material->units + (int) $requirement['required'];
            $material->save();
        }
    }

    /**
     * @param array<int, array{product_id:int, quantity:int}> $orderLines
     * @return array<int, array{material_id:int, material_name:string, required:int}>
     *
     * @throws InsufficientMaterialStockException
     */
    public function deductForOrderLines(array $orderLines): array
    {
        $requirements = $this->calculateRequirements($orderLines);

        return $this->deductFromRequirements($requirements);
    }
}
