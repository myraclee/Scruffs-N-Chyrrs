<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContentManagementSchemaMismatchUiContractTest extends TestCase
{
    public function test_product_api_preserves_error_status_and_payload_for_get_all_products(): void
    {
        $script = file_get_contents(base_path('resources/js/api/productApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('apiError.status = response.status', $script);
        $this->assertStringContainsString('apiError.payload = errorData', $script);
    }

    public function test_order_template_api_preserves_error_status_and_payload_for_get_all_order_templates(): void
    {
        $script = file_get_contents(base_path('resources/js/api/orderTemplateApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('apiError.status = response.status', $script);
        $this->assertStringContainsString('apiError.payload = errorData', $script);
    }

    public function test_owner_content_management_scripts_handle_schema_mismatch_error_code(): void
    {
        $productsScript = file_get_contents(base_path('resources/js/owner/content_page/products_page_content_refactored.js'));
        $orderTemplateScript = file_get_contents(base_path('resources/js/owner/content_page/order_template.js'));

        $this->assertIsString($productsScript);
        $this->assertIsString($orderTemplateScript);

        $this->assertStringContainsString('error?.payload?.error_code === "schema_mismatch"', $productsScript);
        $this->assertStringContainsString('error?.payload?.error_code === "schema_mismatch"', $orderTemplateScript);
    }
}
