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
        $this->assertStringContainsString('selected_option_type_ids[]', $script);
    }

    public function test_order_modal_handles_template_not_configured_error_contract(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/order_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('error?.payload?.error_code === "template_not_configured"', $script);
        $this->assertStringContainsString('This product is not yet available for ordering.', $script);
    }

    public function test_order_modal_supports_inventory_buffer_max_quantity_guardrails(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/order_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('refreshInventoryConstraintsFromSelection', $script);
        $this->assertStringContainsString('max_order_quantity', $script);
        $this->assertStringContainsString('Maximum quantity allowed right now', $script);
    }

    public function test_order_modal_quantity_hint_element_and_max_attribute_contracts(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/order_modal.js'));
        $view = file_get_contents(base_path('resources/views/customer/pages/order_modal.blade.php'));

        $this->assertIsString($script);
        $this->assertIsString($view);
        $this->assertStringContainsString('id="itemQuantityHint"', $view);
        $this->assertStringContainsString('quantityInput.removeAttribute("max");', $script);
        $this->assertStringContainsString('quantityInput.max = String(maxOrderQuantity);', $script);
        $this->assertStringContainsString('quantityHintElement.hidden = false;', $script);
    }
}
