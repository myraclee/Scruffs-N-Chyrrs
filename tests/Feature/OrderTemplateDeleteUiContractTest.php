<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrderTemplateDeleteUiContractTest extends TestCase
{
    public function test_order_template_api_preserves_status_and_payload_on_delete_error(): void
    {
        $script = file_get_contents(base_path('resources/js/api/orderTemplateApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('apiError.status = response.status', $script);
        $this->assertStringContainsString('apiError.payload = errorData', $script);
        $this->assertStringContainsString('if (!response.ok)', $script);
        $this->assertStringContainsString('async deleteOrderTemplate(id)', $script);
    }

    public function test_owner_order_template_script_handles_delete_conflict_counts(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/order_template.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('error?.status === 409', $script);
        $this->assertStringContainsString('active_order_count', $script);
        $this->assertStringContainsString('order_count', $script);
        $this->assertStringContainsString('cart_item_count', $script);
        $this->assertStringContainsString('Cannot delete order template because it is currently in use', $script);
    }
}
