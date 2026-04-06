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
        $this->assertStringContainsString('id="orderEditSaveBtn"', $blade);
    }

    public function test_customer_orders_script_contains_waiting_only_edit_actions_and_save_flow(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/view_orders.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('data-edit-order-group', $script);
        $this->assertStringContainsString('group?.status === EDITABLE_STATUS', $script);
        $this->assertStringContainsString('CustomerOrderAPI.updateOrderDetails', $script);
        $this->assertStringContainsString('customer_order_not_editable', $script);
        $this->assertStringContainsString('Only orders waiting for approval can be edited.', $script);
    }

    public function test_customer_orders_css_contains_edit_button_and_modal_styles(): void
    {
        $css = file_get_contents(base_path('resources/css/customer/view_orders.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.order_group_edit_btn', $css);
        $this->assertStringContainsString('.order_edit_modal_overlay', $css);
        $this->assertStringContainsString('.order_edit_modal', $css);
        $this->assertStringContainsString('.order_edit_primary_btn', $css);
    }

    public function test_customer_order_api_client_exposes_update_order_details_method(): void
    {
        $script = file_get_contents(base_path('resources/js/api/customerOrderApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('async updateOrderDetails(orderGroupId, payload)', $script);
        $this->assertStringContainsString('`${this.orderBaseUrl}/${orderGroupId}/details`', $script);
    }
}
