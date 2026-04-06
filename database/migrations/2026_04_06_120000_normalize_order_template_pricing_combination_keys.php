<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('order_templates')
            ->orderBy('id')
            ->chunkById(100, function ($templates): void {
                foreach ($templates as $template) {
                    $templateId = (int) $template->id;

                    $options = DB::table('order_template_options')
                        ->where('order_template_id', $templateId)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->get(['id']);

                    if ($options->isEmpty()) {
                        continue;
                    }

                    $optionIds = $options
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all();

                    $typeRows = DB::table('order_template_option_types')
                        ->whereIn('order_template_option_id', $optionIds)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->get(['id', 'order_template_option_id', 'type_name']);

                    if ($typeRows->isEmpty()) {
                        continue;
                    }

                    $typesByOption = [];
                    foreach ($typeRows as $typeRow) {
                        $optionId = (int) $typeRow->order_template_option_id;

                        if (! array_key_exists($optionId, $typesByOption)) {
                            $typesByOption[$optionId] = [];
                        }

                        $typesByOption[$optionId][] = [
                            'id' => (int) $typeRow->id,
                            'type_name' => (string) $typeRow->type_name,
                        ];
                    }

                    $normalizedTypeNameMap = [];
                    foreach ($typeRows as $typeRow) {
                        $normalizedName = $this->normalizePricingToken((string) $typeRow->type_name);
                        if ($normalizedName === '') {
                            continue;
                        }

                        if (! array_key_exists($normalizedName, $normalizedTypeNameMap)) {
                            $normalizedTypeNameMap[$normalizedName] = [];
                        }

                        $normalizedTypeNameMap[$normalizedName][] = (int) $typeRow->id;
                    }

                    $pricings = DB::table('order_template_pricings')
                        ->where('order_template_id', $templateId)
                        ->get(['id', 'combination_key']);

                    foreach ($pricings as $pricing) {
                        $normalizedKey = $this->normalizeCombinationKey(
                            (string) $pricing->combination_key,
                            $optionIds,
                            $typesByOption,
                            $normalizedTypeNameMap,
                        );

                        if ($normalizedKey === null) {
                            continue;
                        }

                        if ($normalizedKey === (string) $pricing->combination_key) {
                            continue;
                        }

                        DB::table('order_template_pricings')
                            ->where('id', (int) $pricing->id)
                            ->update([
                                'combination_key' => $normalizedKey,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible data migration.
    }

    /**
     * @param array<int, int> $optionIds
     * @param array<int, array<int, array{id:int, type_name:string}>> $typesByOption
     * @param array<string, array<int, int>> $normalizedTypeNameMap
     */
    private function normalizeCombinationKey(
        string $combinationKey,
        array $optionIds,
        array $typesByOption,
        array $normalizedTypeNameMap,
    ): ?string {
        $trimmedKey = trim($combinationKey);
        if ($trimmedKey === '') {
            return null;
        }

        if (preg_match('/^\d+(\s*,\s*\d+)*$/', $trimmedKey) === 1) {
            $ids = preg_split('/\s*,\s*/', $trimmedKey) ?: [];
            $canonicalIds = $this->canonicalizeTypeIds($ids);

            return empty($canonicalIds) ? null : implode(',', $canonicalIds);
        }

        if (str_contains($trimmedKey, '|')) {
            $segments = array_values(array_filter(
                array_map(static fn ($segment) => trim($segment), explode('|', $trimmedKey)),
                static fn ($segment) => $segment !== ''
            ));

            if (count($segments) === count($optionIds)) {
                $resolved = [];

                foreach ($optionIds as $index => $optionId) {
                    $resolvedTypeId = $this->resolveTypeIdByOptionAndToken(
                        $typesByOption[$optionId] ?? [],
                        $segments[$index],
                    );

                    if ($resolvedTypeId === null) {
                        $resolved = [];
                        break;
                    }

                    $resolved[] = $resolvedTypeId;
                }

                if (! empty($resolved)) {
                    return implode(',', $this->canonicalizeTypeIds($resolved));
                }
            }
        }

        $tokens = array_values(array_filter(
            preg_split('/\||_/', $trimmedKey) ?: [],
            static fn ($token) => trim((string) $token) !== ''
        ));

        if (empty($tokens)) {
            return null;
        }

        $resolvedIds = [];

        foreach ($tokens as $token) {
            $normalizedToken = $this->normalizePricingToken((string) $token);

            if ($normalizedToken === '' || ! array_key_exists($normalizedToken, $normalizedTypeNameMap)) {
                return null;
            }

            $resolvedIds[] = (int) $normalizedTypeNameMap[$normalizedToken][0];
        }

        $canonicalIds = $this->canonicalizeTypeIds($resolvedIds);

        return empty($canonicalIds) ? null : implode(',', $canonicalIds);
    }

    /**
     * @param array<int, array{id:int, type_name:string}> $types
     */
    private function resolveTypeIdByOptionAndToken(array $types, string $token): ?int
    {
        $normalizedToken = $this->normalizePricingToken($token);
        if ($normalizedToken === '') {
            return null;
        }

        foreach ($types as $type) {
            if ($this->normalizePricingToken($type['type_name']) === $normalizedToken) {
                return (int) $type['id'];
            }
        }

        return null;
    }

    private function normalizePricingToken(string $token): string
    {
        $normalized = strtolower(trim($token));
        $normalized = preg_replace('/\s*(inch|inches)\s*$/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    /**
     * @param array<int, int|string> $typeIds
     * @return array<int, int>
     */
    private function canonicalizeTypeIds(array $typeIds): array
    {
        $normalized = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $typeIds,
        ), static fn ($value): bool => $value > 0));

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }
};
