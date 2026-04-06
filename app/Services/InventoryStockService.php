<?php

namespace App\Services;

use App\Exceptions\InvalidInventoryConfigurationException;
use App\Exceptions\InsufficientMaterialStockException;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Product;

class InventoryStockService
{
    /**
     * @param array<int, array{
     *     product_id:int,
     *     quantity:int,
     *     selected_options?:array<int|string, int|string>,
     *     selected_option_type_ids?:array<int, int>
     * }> $orderLines
     * @return array<int, array{
     *     material_id:int,
     *     material_name:string,
     *     required:int,
     *     breakdown:array<int, array<string, int|string|null>>
     * }>
     *
     * @throws InvalidInventoryConfigurationException
     */
    public function calculateRequirements(array $orderLines): array
    {
        $normalizedLines = collect($orderLines)
            ->map(function (array $line): array {
                return [
                    'product_id' => (int) ($line['product_id'] ?? 0),
                    'quantity' => (int) ($line['quantity'] ?? 0),
                    'selected_option_type_ids' => $this->extractSelectedOptionTypeIds($line),
                ];
            })
            ->filter(fn (array $line): bool => $line['product_id'] > 0 && $line['quantity'] > 0)
            ->values();

        if ($normalizedLines->isEmpty()) {
            return [];
        }

        $productIds = $normalizedLines
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        $products = Product::with([
            'orderTemplate:id,product_id',
            'orderTemplate.options:id,order_template_id,label,position',
        ])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $configurationIssues = [];

        foreach ($normalizedLines as $line) {
            $product = $products->get($line['product_id']);
            $missingTemplate = ! $product || ! $product->orderTemplate;
            $missingOptions = ! $missingTemplate && $product->orderTemplate->options->isEmpty();

            if (! $missingTemplate && ! $missingOptions) {
                continue;
            }

            $configurationIssues[] = [
                'product_id' => (int) $line['product_id'],
                'product_name' => (string) ($product?->name ?? 'Unknown Product'),
                'issue' => 'missing_template_or_options',
            ];
        }

        if (! empty($configurationIssues)) {
            throw new InvalidInventoryConfigurationException(
                $configurationIssues,
                'Checkout is blocked: one or more products are missing template options configuration.'
            );
        }

        $selectedOptionTypeIds = $normalizedLines
            ->flatMap(fn (array $line): array => $line['selected_option_type_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $consumptionRules = MaterialConsumption::query()
            ->with([
                'material:id,name,units',
                'product:id,name',
                'optionType:id,type_name',
            ])
            ->whereIn('product_id', $productIds)
            ->when(
                ! empty($selectedOptionTypeIds),
                function ($query) use ($selectedOptionTypeIds) {
                    $query->where(function ($innerQuery) use ($selectedOptionTypeIds) {
                        $innerQuery->whereNull('order_template_option_type_id')
                            ->orWhereIn('order_template_option_type_id', $selectedOptionTypeIds);
                    });
                },
                fn ($query) => $query->whereNull('order_template_option_type_id')
            )
            ->get()
            ->groupBy('product_id');

        $legacyProducts = Product::with('materials:id,name,units')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $requirements = [];

        foreach ($normalizedLines as $line) {
            $lineRules = collect($consumptionRules->get($line['product_id'], collect()));
            $selectedIds = $line['selected_option_type_ids'];

            $selectedOptionRules = $lineRules
                ->filter(function (MaterialConsumption $rule) use ($selectedIds): bool {
                    if ($rule->order_template_option_type_id === null) {
                        return false;
                    }

                    return in_array((int) $rule->order_template_option_type_id, $selectedIds, true);
                })
                ->values();

            $fallbackRules = $lineRules
                ->filter(fn (MaterialConsumption $rule): bool => $rule->order_template_option_type_id === null)
                ->values();

            $matchedRules = $selectedOptionRules->isNotEmpty()
                ? $selectedOptionRules
                : $fallbackRules;

            if ($matchedRules->isEmpty()) {
                $legacyProduct = $legacyProducts->get($line['product_id']);

                if (! $legacyProduct) {
                    continue;
                }

                foreach ($legacyProduct->materials as $material) {
                    $perUnit = (int) ($material->pivot->quantity ?? 0);

                    if ($perUnit <= 0) {
                        continue;
                    }

                    $required = $line['quantity'] * $perUnit;

                    $this->appendRequirement($requirements, [
                        'material_id' => (int) $material->id,
                        'material_name' => (string) $material->name,
                        'required' => $required,
                    ], [
                        'product_id' => (int) $legacyProduct->id,
                        'product_name' => (string) $legacyProduct->name,
                        'source' => 'legacy_product_fallback',
                        'order_template_option_type_id' => null,
                        'option_type_name' => null,
                        'order_quantity' => (int) $line['quantity'],
                        'consumption_quantity' => (int) $perUnit,
                        'required' => (int) $required,
                    ]);
                }

                continue;
            }

            foreach ($matchedRules as $matchedRule) {
                $perUnit = (int) $matchedRule->quantity;

                if ($perUnit <= 0) {
                    continue;
                }

                $required = $line['quantity'] * $perUnit;

                $this->appendRequirement($requirements, [
                    'material_id' => (int) $matchedRule->material_id,
                    'material_name' => (string) ($matchedRule->material?->name ?? 'Unknown Material'),
                    'required' => $required,
                ], [
                    'product_id' => (int) $matchedRule->product_id,
                    'product_name' => (string) ($matchedRule->product?->name ?? 'Unknown Product'),
                    'source' => $matchedRule->order_template_option_type_id === null
                        ? 'any_option_fallback'
                        : 'selected_option_type',
                    'order_template_option_type_id' => $matchedRule->order_template_option_type_id !== null
                        ? (int) $matchedRule->order_template_option_type_id
                        : null,
                    'option_type_name' => (string) ($matchedRule->optionType?->type_name ?? ''),
                    'order_quantity' => (int) $line['quantity'],
                    'consumption_quantity' => (int) $perUnit,
                    'required' => (int) $required,
                ]);
            }
        }

        return array_values($requirements);
    }

    /**
     * @param array<int, array<string, mixed>> $requirements
     * @param array{material_id:int, material_name:string, required:int} $requirement
     * @param array<string, int|string|null> $breakdown
     */
    private function appendRequirement(array &$requirements, array $requirement, array $breakdown): void
    {
        $materialId = (int) $requirement['material_id'];

        if (! isset($requirements[$materialId])) {
            $requirements[$materialId] = [
                'material_id' => $materialId,
                'material_name' => (string) $requirement['material_name'],
                'required' => 0,
                'breakdown' => [],
            ];
        }

        $requirements[$materialId]['required'] += (int) $requirement['required'];
        $requirements[$materialId]['breakdown'][] = $breakdown;
    }

    /**
     * @param array<string, mixed> $line
     * @return array<int, int>
     */
    private function extractSelectedOptionTypeIds(array $line): array
    {
        $explicitIds = collect($line['selected_option_type_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($explicitIds->isNotEmpty()) {
            return $explicitIds->all();
        }

        return collect($line['selected_options'] ?? [])
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $requirements
     * @return array<int, array<string, mixed>>
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
        * @param array<int, array<string, mixed>> $requirements
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
        * @param array<int, array{
        *     product_id:int,
        *     quantity:int,
        *     selected_options?:array<int|string, int|string>,
        *     selected_option_type_ids?:array<int, int>
        * }> $orderLines
        * @return array<int, array<string, mixed>>
     *
     * @throws InsufficientMaterialStockException
        * @throws InvalidInventoryConfigurationException
     */
    public function deductForOrderLines(array $orderLines): array
    {
        $requirements = $this->calculateRequirements($orderLines);

        return $this->deductFromRequirements($requirements);
    }
}
