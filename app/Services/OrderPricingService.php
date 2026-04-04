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

    public function calculate(OrderTemplate $template, array $selectedOptions, int $quantity, ?int $rushFeeId = null): array
    {
        $selectedOptions = $this->normalizeSelectedOptions($template, $selectedOptions);

        $validation = $this->validateSelectedOptions($template, $selectedOptions);
        if (!$validation['success']) {
            return $validation;
        }

        try {
            $template->loadMissing(['pricings', 'discounts', 'layoutFee', 'options.optionTypes']);

            $combinationKey = $this->buildCombinationKey($selectedOptions);

            $pricing = $template->pricings
                ->first(fn ($item) => $item->combination_key === $combinationKey);

            // Backward compatibility: older data stores readable keys like "matte_2x2".
            if (! $pricing) {
                $legacyKey = $this->buildLegacyCombinationKey($template, $selectedOptions);

                if ($legacyKey !== '') {
                    $pricing = $template->pricings
                        ->first(fn ($item) => $item->combination_key === $legacyKey);
                }
            }

            if (!$pricing) {
                return [
                    'success' => false,
                    'message' => 'Invalid option combination selected',
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

            $layoutFeeAmount = $template->layoutFee
                ? (float) $template->layoutFee->fee_amount
                : 0;

            $rushFeeAmount = 0;
            if ($rushFeeId) {
                $rushFee = RushFee::with('timeframes')->find($rushFeeId);
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

    private function normalizeLegacySegment(string $segment): string
    {
        $normalized = strtolower(trim($segment));
        $normalized = preg_replace('/\s*(inch|inches)\s*$/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }
}
