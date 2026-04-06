<?php

namespace App\Services;

use App\Models\OrderTemplate;
use App\Models\RushFee;

class OrderPricingService
{
    public function normalizeSelectedOptions(OrderTemplate $template, array $selectedOptions): array
    {
        $template->loadMissing('options');

        if (!array_is_list($selectedOptions)) {
            return $selectedOptions;
        }

        $orderedOptions = $template->options
            ->sortBy('position')
            ->values();

        $normalized = [];

        foreach ($orderedOptions as $index => $option) {
            if (!array_key_exists($index, $selectedOptions)) {
                continue;
            }

            $normalized[(string) $option->id] = $selectedOptions[$index];
        }

        return $normalized;
    }

    public function validateSelectedOptions(OrderTemplate $template, array $selectedOptions): array
    {
        $selectedOptions = $this->normalizeSelectedOptions($template, $selectedOptions);
        $template->loadMissing('options.optionTypes');

        foreach ($template->options as $option) {
            $optionId = (string) $option->id;

            if (!array_key_exists($optionId, $selectedOptions) && !array_key_exists($option->id, $selectedOptions)) {
                return [
                    'success' => false,
                    'message' => "Missing selected value for option: {$option->label}",
                ];
            }

            $rawValue = $selectedOptions[$optionId] ?? $selectedOptions[$option->id];
            $selectedTypeId = (int) $rawValue;

            $validType = $option->optionTypes
                ->first(fn ($type) => (int) $type->id === $selectedTypeId && $type->is_available);

            if (!$validType) {
                return [
                    'success' => false,
                    'message' => "Invalid selected value for option: {$option->label}",
                ];
            }
        }

        return ['success' => true];
    }

    public function calculate(
        OrderTemplate $template,
        array $selectedOptions,
        int $quantity,
        ?int $rushFeeId = null,
        ?string $specialInstructions = null
    ): array {
        $selectedOptions = $this->normalizeSelectedOptions($template, $selectedOptions);

        $validation = $this->validateSelectedOptions($template, $selectedOptions);
        if (!$validation['success']) {
            return $validation;
        }

        try {
            $template->loadMissing(['pricings', 'discounts', 'layoutFee', 'options.optionTypes']);

            $pricing = $this->findPricingForSelectedOptions($template, $selectedOptions);

            if (!$pricing) {
                return [
                    'success' => false,
                    'message' => 'Selected option combination is not configured for pricing yet. Please choose another combination or contact support.',
                ];
            }

            $basePrice = (float) $pricing->price * $quantity;

            $discountAmount = 0;
            $discount = $template->discounts
                ->where('min_quantity', '<=', $quantity)
                ->sortByDesc('min_quantity')
                ->first();

            if ($discount) {
                $discountAmount = (float) $discount->price_reduction * $quantity;
            }

            $layoutCount = $this->parseLayoutCountFromNotes($specialInstructions);
            $layoutFeePerLayout = $template->layoutFee
                ? (float) $template->layoutFee->fee_amount
                : 0;
            $layoutFeeAmount = $layoutFeePerLayout * $layoutCount;

            $rushFeeAmount = 0;
            if ($rushFeeId) {
                $rushFee = RushFee::with([
                    'timeframes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                ])->find($rushFeeId);

                if ($rushFee && $rushFee->timeframes->isNotEmpty()) {
                    $timeframePercentage = (float) $rushFee->timeframes->first()->percentage;
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
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error calculating pricing: '.$e->getMessage(),
            ];
        }
    }

    public function buildCombinationKey(array $selectedOptions): string
    {
        $ids = array_map(static fn ($value) => (int) $value, array_values($selectedOptions));
        sort($ids);

        return implode(',', $ids);
    }

    private function findPricingForSelectedOptions(OrderTemplate $template, array $selectedOptions): mixed
    {
        $selectedTypeIds = $this->canonicalizeTypeIds(
            array_values($selectedOptions)
        );

        if (empty($selectedTypeIds)) {
            return null;
        }

        $canonicalNumericKey = implode(',', $selectedTypeIds);
        $legacyKey = $this->buildLegacyCombinationKey($template, $selectedOptions);
        $labelKey = $this->buildLabelCombinationKey($template, $selectedOptions);

        $candidateKeys = array_values(array_filter(array_unique([
            $canonicalNumericKey,
            $legacyKey,
            $labelKey,
        ]), static fn ($value) => $value !== ''));

        $directMatch = $template->pricings
            ->first(fn ($item) => in_array((string) $item->combination_key, $candidateKeys, true));

        if ($directMatch) {
            return $directMatch;
        }

        return $template->pricings->first(function ($item) use ($template, $selectedTypeIds): bool {
            $resolvedIds = $this->resolveCombinationKeyToTypeIds(
                $template,
                (string) $item->combination_key
            );

            return !empty($resolvedIds) && $resolvedIds === $selectedTypeIds;
        });
    }

    private function parseLayoutCountFromNotes(?string $specialInstructions): int
    {
        if ($specialInstructions === null) {
            return 0;
        }

        $segments = array_filter(
            array_map(static fn ($value) => trim($value), explode(',', $specialInstructions)),
            static fn ($value) => $value !== ''
        );

        return count($segments);
    }

    private function buildLegacyCombinationKey(OrderTemplate $template, array $selectedOptions): string
    {
        $segments = [];

        foreach ($template->options->sortBy('position') as $option) {
            $optionKey = (string) $option->id;

            if (! array_key_exists($optionKey, $selectedOptions) && ! array_key_exists($option->id, $selectedOptions)) {
                continue;
            }

            $selectedValue = $selectedOptions[$optionKey] ?? $selectedOptions[$option->id];

            $selectedType = $option->optionTypes
                ->first(fn ($type) => (int) $type->id === (int) $selectedValue);

            if (! $selectedType) {
                return '';
            }

            $segments[] = $this->normalizeLegacySegment($selectedType->type_name);
        }

        return implode('_', array_filter($segments));
    }

    private function buildLabelCombinationKey(OrderTemplate $template, array $selectedOptions): string
    {
        $segments = [];

        foreach ($template->options->sortBy('position') as $option) {
            $optionKey = (string) $option->id;

            if (! array_key_exists($optionKey, $selectedOptions) && ! array_key_exists($option->id, $selectedOptions)) {
                continue;
            }

            $selectedValue = $selectedOptions[$optionKey] ?? $selectedOptions[$option->id];

            $selectedType = $option->optionTypes
                ->first(fn ($type) => (int) $type->id === (int) $selectedValue);

            if (! $selectedType) {
                return '';
            }

            $segments[] = trim((string) $selectedType->type_name);
        }

        return implode(' | ', array_filter($segments, static fn ($segment) => $segment !== ''));
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
                    $resolvedTypeId = $this->resolveTypeIdByOptionAndToken(
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
                $normalized = $this->normalizeOptionToken((string) $type->type_name);
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
            $normalizedToken = $this->normalizeOptionToken((string) $token);
            if ($normalizedToken === '' || ! array_key_exists($normalizedToken, $typeNameMap)) {
                return [];
            }

            $resolvedIds[] = (int) $typeNameMap[$normalizedToken][0];
        }

        return $this->canonicalizeTypeIds($resolvedIds);
    }

    private function resolveTypeIdByOptionAndToken($optionTypes, string $token): ?int
    {
        $normalizedToken = $this->normalizeOptionToken($token);
        if ($normalizedToken === '') {
            return null;
        }

        $match = $optionTypes->first(function ($type) use ($normalizedToken): bool {
            return $this->normalizeOptionToken((string) $type->type_name) === $normalizedToken;
        });

        return $match ? (int) $match->id : null;
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

    private function normalizeOptionToken(string $token): string
    {
        $normalized = trim($token);
        if ($normalized === '') {
            return '';
        }

        return $this->normalizeLegacySegment($normalized);
    }

    private function normalizeLegacySegment(string $segment): string
    {
        $normalized = strtolower(trim($segment));
        $normalized = preg_replace('/\s*(inch|inches)\s*$/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }
}
