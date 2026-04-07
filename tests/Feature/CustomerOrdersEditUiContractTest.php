<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerOrdersEditUiContractTest extends TestCase
{
    public function test_customer_orders_blade_contains_order_edit_modal_scaffold(): void
    {
        $blade = file_get_contents(base_path('resources/views/customer/view_orders.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('id="orderEditModal"', $blade);
        $this->assertStringContainsString('id="orderEditDriveLink"', $blade);
        $this->assertStringContainsString('id="orderEditItems"', $blade);
        $this->assertStringContainsString('id="orderEditSubtitle"', $blade);
    }

    public function test_customer_orders_script_contains_strict_view_pay_cancel_action_flow(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/view_orders.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('data-view-order-group', $script);
        $this->assertStringContainsString('data-pay-order-group', $script);
        $this->assertStringContainsString('data-cancel-order-group', $script);
        $this->assertStringContainsString('CustomerOrderAPI.submitPaymentProof', $script);
        $this->assertStringContainsString('CustomerOrderAPI.cancelOrder', $script);
        $this->assertStringNotContainsString('data-edit-order-group', $script);
    }

    public function test_customer_orders_css_contains_action_button_and_details_view_styles(): void
    {
        $css = file_get_contents(base_path('resources/css/customer/view_orders.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.order_group_action_btn', $css);
        $this->assertStringContainsString('.order_group_action_btn_pay', $css);
        $this->assertStringContainsString('.order_group_action_btn_view', $css);
        $this->assertStringContainsString('.order_group_action_btn_cancel', $css);
        $this->assertStringContainsString('.order_group_payment_chip', $css);
        $this->assertStringContainsString('.order_view_item', $css);
        $this->assertStringContainsString('.order_edit_modal_overlay', $css);
        $this->assertStringContainsString('.order_edit_modal', $css);
    }

    public function test_customer_order_api_client_exposes_payment_and_cancel_methods(): void
    {
        $script = file_get_contents(base_path('resources/js/api/customerOrderApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('async submitPaymentProof(orderGroupId, payload)', $script);
        $this->assertStringContainsString('async cancelOrder(orderGroupId)', $script);
        $this->assertStringContainsString('payment-proof', $script);
        $this->assertStringContainsString('/cancel', $script);
    }
}
