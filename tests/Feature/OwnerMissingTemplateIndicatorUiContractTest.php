<?php

namespace Tests\Feature;

use Tests\TestCase;

class OwnerMissingTemplateIndicatorUiContractTest extends TestCase
{
    public function test_content_management_view_contains_missing_template_indicator_container(): void
    {
        $view = file_get_contents(base_path('resources/views/owner/pages/content_management.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('id="missingTemplateIndicator"', $view);
        $this->assertStringContainsString('id="missingTemplateIndicatorText"', $view);
    }

    public function test_order_template_script_updates_missing_template_indicator_from_product_catalog(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/order_template.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('function updateMissingTemplateIndicator()', $script);
        $this->assertStringContainsString('Missing templates:', $script);
        $this->assertStringContainsString('Template Coverage: Complete', $script);
    }
}
