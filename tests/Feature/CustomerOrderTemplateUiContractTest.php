<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerOrderTemplateUiContractTest extends TestCase
{
    public function test_customer_order_api_preserves_error_status_and_payload_for_template_fetch(): void
    {
        $script = file_get_contents(base_path('resources/js/api/customerOrderApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('apiError.status = response.status', $script);
        $this->assertStringContainsString('apiError.payload = errorData', $script);
    }

    public function test_order_modal_handles_template_not_configured_error_contract(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/order_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('error?.payload?.error_code === "template_not_configured"', $script);
        $this->assertStringContainsString('This product is not yet available for ordering.', $script);
    }
}
