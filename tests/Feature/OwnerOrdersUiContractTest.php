<?php

namespace Tests\Feature;

use Tests\TestCase;

class OwnerOrdersUiContractTest extends TestCase
{
    public function test_owner_orders_blade_contains_detail_edit_controls(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/orders.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('id="detailEditBtn"', $blade);
        $this->assertStringContainsString('id="detailSaveBtn"', $blade);
        $this->assertStringContainsString('id="detailCancelBtn"', $blade);
        $this->assertStringContainsString('id="detailDriveLinkInput"', $blade);
        $this->assertStringContainsString('id="detailDriveLinkHint"', $blade);
        $this->assertStringContainsString('id="detailPaymentStatus"', $blade);
        $this->assertStringContainsString('id="detailPaymentMethod"', $blade);
        $this->assertStringContainsString('id="detailPaymentReference"', $blade);
        $this->assertStringContainsString('id="detailPaymentProofLink"', $blade);
        $this->assertStringContainsString('id="detailPaymentNoteInput"', $blade);
        $this->assertStringContainsString('id="detailConfirmPaymentBtn"', $blade);
    }

    public function test_owner_orders_script_wires_details_edit_mode_and_payload_fields(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/pages/orders.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('saveDetailsEdits', $script);
        $this->assertStringContainsString('isDetailsEditMode', $script);
        $this->assertStringContainsString('data-edit-option-id', $script);
        $this->assertStringContainsString('data-edit-quantity-order-id', $script);
        $this->assertStringContainsString('data-edit-rush-order-id', $script);
        $this->assertStringContainsString('general_drive_link', $script);
        $this->assertStringContainsString('isValidGoogleDriveUrl', $script);
        $this->assertStringContainsString('detailDriveLinkHint', $script);
        $this->assertStringContainsString('selected_options', $script);
        $this->assertStringContainsString('rush_fee_id', $script);
        $this->assertStringContainsString('special_instructions', $script);
        $this->assertStringContainsString('confirmPaymentForCurrentOrder', $script);
        $this->assertStringContainsString('detailConfirmPaymentBtn', $script);
        $this->assertStringContainsString('detailPaymentStatus', $script);
        $this->assertStringContainsString('payment_status_label', $script);
    }

    public function test_owner_order_api_supports_details_update_endpoint(): void
    {
        $apiClient = file_get_contents(base_path('resources/js/api/ownerOrderApi.js'));

        $this->assertIsString($apiClient);
        $this->assertStringContainsString('updateOrderDetails', $apiClient);
        $this->assertStringContainsString('/details', $apiClient);
        $this->assertStringContainsString('confirmPayment', $apiClient);
        $this->assertStringContainsString('/payment-confirmation', $apiClient);
    }
}
