<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrderTemplatePricingUiContractTest extends TestCase
{
    public function test_owner_template_script_persists_combination_keys_for_pricing_rows(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/order_template.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('row.dataset.combinationKey', $script);
        $this->assertStringContainsString('const combinationKey = row.dataset.combinationKey || label;', $script);
    }

    public function test_owner_template_script_tracks_option_type_ids_for_canonical_matching(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/order_template.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('row.dataset.optionTypeId', $script);
        $this->assertStringContainsString('toCanonicalNumericKey', $script);
        $this->assertStringContainsString('normalizePricingToken', $script);
    }
}
