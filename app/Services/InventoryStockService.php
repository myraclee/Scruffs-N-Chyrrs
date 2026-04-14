<?php

namespace App\Services;

use App\Exceptions\InvalidInventoryConfigurationException;
use App\Exceptions\InsufficientMaterialStockException;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\OrderTemplateOptionType;
use App\Models\Product;
use Illuminate\Support\Collection;

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

        $optionTypeLookup = OrderTemplateOptionType::query()
            ->whereIn('id', $selectedOptionTypeIds)
            ->get()
            ->keyBy('id');

        $requirements = [];
        $mappingIssues = [];

        foreach ($normalizedLines as $line) {
            $lineRules = collect($consumptionRules->get($line['product_id'], collect()));
            $selectedIds = $line['selected_option_type_ids'];
            $product = $products->get($line['product_id']);

            if ($lineRules->isEmpty()) {
                $mappingIssues[] = [
                    'product_id' => (int) $line['product_id'],
                    'product_name' => (string) ($product?->name ?? 'Unknown Product'),
                    'issue' => 'missing_product_option_mappings',
                ];
                continue;
            }

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

            foreach ($selectedIds as $selectedId) {
                $hasSpecificRule = $selectedOptionRules
                    ->contains(fn (MaterialConsumption $rule): bool => (int) $rule->order_template_option_type_id === (int) $selectedId);

                if ($hasSpecificRule || $fallbackRules->isNotEmpty()) {
                    continue;
                }

                $optionType = $optionTypeLookup->get((int) $selectedId);

                $mappingIssues[] = [
                    'product_id' => (int) $line['product_id'],
                    'product_name' => (string) ($product?->name ?? 'Unknown Product'),
                    'issue' => 'missing_selected_option_material_mapping',
                    'order_template_option_type_id' => (int) $selectedId,
                    'option_type_name' => (string) ($optionType?->type_name ?? 'Unknown Option'),
                ];
            }

            $candidateRules = $selectedOptionRules
                ->concat($fallbackRules)
                ->values();

            if ($candidateRules->isEmpty()) {
                continue;
            }

            $candidateRulesByMaterial = $candidateRules
                ->groupBy(fn (MaterialConsumption $rule): int => (int) $rule->material_id);

            foreach ($candidateRulesByMaterial as $ruleGroup) {
                $chosenRule = $ruleGroup
                    ->sort(function (MaterialConsumption $a, MaterialConsumption $b): int {
                        $quantityCompare = (int) $b->quantity <=> (int) $a->quantity;
                        if ($quantityCompare !== 0) {
                            return $quantityCompare;
                        }

                        $aSpecific = $a->order_template_option_type_id !== null ? 1 : 0;
                        $bSpecific = $b->order_template_option_type_id !== null ? 1 : 0;

                        return $bSpecific <=> $aSpecific;
                    })
                    ->first();

                if (! $chosenRule) {
                    continue;
                }

                $perUnit = (int) $chosenRule->quantity;
                if ($perUnit <= 0) {
                    continue;
                }

                $required = $line['quantity'] * $perUnit;

                $this->appendRequirement($requirements, [
                    'material_id' => (int) $chosenRule->material_id,
                    'material_name' => (string) ($chosenRule->material?->name ?? 'Unknown Material'),
                    'required' => $required,
                ], [
                    'product_id' => (int) $chosenRule->product_id,
                    'product_name' => (string) ($chosenRule->product?->name ?? 'Unknown Product'),
                    'source' => $chosenRule->order_template_option_type_id === null
                        ? 'any_option_fallback'
                        : 'selected_option_type',
                    'order_template_option_type_id' => $chosenRule->order_template_option_type_id !== null
                        ? (int) $chosenRule->order_template_option_type_id
                        : null,
                    'option_type_name' => (string) ($chosenRule->optionType?->type_name ?? ''),
                    'order_quantity' => (int) $line['quantity'],
                    'consumption_quantity' => (int) $perUnit,
                    'material_match_count' => (int) $ruleGroup->count(),
                    'dedupe_strategy' => 'highest_quantity_wins',
                    'required' => (int) $required,
                ]);
            }
        }

        if (! empty($mappingIssues)) {
            throw new InvalidInventoryConfigurationException(
                $mappingIssues,
                'Checkout is blocked: one or more selected options are missing material mappings.'
            );
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

        $materials = $this->loadMaterialsForRequirements($requirements, true);
        $shortages = $this->buildRequirementShortages($requirements, $materials);

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
     * @return array<int, array<string, int|string>>
     */
    public function previewShortagesFromRequirements(array $requirements): array
    {
        if (empty($requirements)) {
            return [];
        }

        $materials = $this->loadMaterialsForRequirements($requirements);

        return $this->buildRequirementShortages($requirements, $materials);
    }

    /**
     * @param array{product_id:int, selected_options?:array<int|string, int|string>, selected_option_type_ids?:array<int, int>} $orderLine
     */
    public function calculateMaxOrderQuantityForOrderLine(array $orderLine): ?int
    {
        $requirements = $this->calculateRequirements([
            [
                'product_id' => (int) ($orderLine['product_id'] ?? 0),
                'quantity' => 1,
                'selected_options' => $orderLine['selected_options'] ?? [],
                'selected_option_type_ids' => $orderLine['selected_option_type_ids'] ?? [],
            ],
        ]);

        if (empty($requirements)) {
            return null;
        }

        $materials = $this->loadMaterialsForRequirements($requirements);

        return $this->calculateMaxOrderQuantityFromRequirements($requirements, $materials);
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

    /**
     * @param array<int, array<string, mixed>> $requirements
     */
    private function loadMaterialsForRequirements(array $requirements, bool $lockForUpdate = false): Collection
    {
        $materialIds = collect($requirements)
            ->pluck('material_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($materialIds)) {
            return collect();
        }

        $materialsQuery = Material::query()->whereIn('id', $materialIds);

        if ($lockForUpdate) {
            $materialsQuery->lockForUpdate();
        }

        return $materialsQuery
            ->get()
            ->keyBy('id');
    }

    /**
     * @param array<int, array<string, mixed>> $requirements
     * @return array<int, array<string, int|string>>
     */
    private function buildRequirementShortages(array $requirements, Collection $materials): array
    {
        $shortages = [];

        foreach ($requirements as $requirement) {
            $materialId = (int) ($requirement['material_id'] ?? 0);
            if ($materialId <= 0) {
                continue;
            }

            $required = max(0, (int) ($requirement['required'] ?? 0));
            $material = $materials->get($materialId);
            $available = max(0, (int) ($material?->units ?? 0));
            $lowStockThreshold = max(0, (int) ($material?->low_stock_threshold ?? 0));
            $safeAvailable = max($available - $lowStockThreshold, 0);

            if (! $material || $safeAvailable < $required) {
                $shortages[] = [
                    'material_id' => $materialId,
                    'material_name' => (string) ($material?->name ?? ($requirement['material_name'] ?? 'Unknown Material')),
                    'required' => $required,
                    'available' => $available,
                    'low_stock_threshold' => $lowStockThreshold,
                    'safe_available' => $safeAvailable,
                    'max_allowed_quantity' => $safeAvailable,
                    'deficit' => max($required - $safeAvailable, 0),
                ];
            }
        }

        return $shortages;
    }

    /**
     * @param array<int, array<string, mixed>> $requirements
     */
    private function calculateMaxOrderQuantityFromRequirements(array $requirements, Collection $materials): ?int
    {
        $maxPerMaterial = [];

        foreach ($requirements as $requirement) {
            $materialId = (int) ($requirement['material_id'] ?? 0);
            if ($materialId <= 0) {
                continue;
            }

            $perUnitRequired = (int) ($requirement['required'] ?? 0);
            if ($perUnitRequired <= 0) {
                continue;
            }

            $material = $materials->get($materialId);
            $available = max(0, (int) ($material?->units ?? 0));
            $lowStockThreshold = max(0, (int) ($material?->low_stock_threshold ?? 0));
            $safeAvailable = max($available - $lowStockThreshold, 0);

            $maxPerMaterial[] = intdiv($safeAvailable, $perUnitRequired);
        }

        if (empty($maxPerMaterial)) {
            return null;
        }

        return max(0, (int) min($maxPerMaterial));
    }
}
