<?php

namespace Tests\Feature;

use Tests\TestCase;

class InventoryUiContractTest extends TestCase
{
    public function test_inventory_blade_contains_threshold_inputs_and_status_cards(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/inventory.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('id="newMaxUnitsInput"', $blade);
        $this->assertStringContainsString('id="editMaxUnitsInput"', $blade);
        $this->assertStringContainsString('id="newThresholdInput"', $blade);
        $this->assertStringContainsString('id="editThresholdInput"', $blade);
        $this->assertStringContainsString('id="highStockCard"', $blade);
        $this->assertStringContainsString('id="mediumStockCard"', $blade);
        $this->assertStringContainsString('id="lowStockCard"', $blade);
        $this->assertStringContainsString('id="outOfStockCard"', $blade);
        $this->assertStringContainsString('id="inventorySearchInput"', $blade);
        $this->assertStringContainsString('id="inventorySortSelect"', $blade);
        $this->assertStringContainsString('id="clearInventoryFiltersBtn"', $blade);
        $this->assertStringContainsString('id="inventoryFilterSummary"', $blade);
        $this->assertStringContainsString('id="deleteMaterialConfirmOverlay"', $blade);
        $this->assertStringContainsString('id="deleteMaterialConfirmModal"', $blade);
        $this->assertStringContainsString('id="deleteMaterialConfirmMessage"', $blade);
        $this->assertStringContainsString('id="deleteMaterialCancelBtn"', $blade);
        $this->assertStringContainsString('id="deleteMaterialConfirmBtn"', $blade);
    }

    public function test_inventory_actions_use_standard_edit_delete_buttons_instead_of_emojis(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('class="action_btn edit_btn"', $script);
        $this->assertStringContainsString('class="action_btn delete_btn"', $script);
        $this->assertStringNotContainsString('✏️', $script);
        $this->assertStringNotContainsString('🗑️', $script);
    }

    public function test_inventory_blade_explains_option_precedence_in_helper_text(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/inventory.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('Any Option fallback applies to all selections.', $blade);
        $this->assertStringContainsString('If one material matches multiple rules, the highest quantity is used.', $blade);
    }

    public function test_inventory_status_cards_support_show_more_and_healthy_messages(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('No items above 70%', $script);
        $this->assertStringContainsString('No items between 30% and 70%', $script);
        $this->assertStringContainsString('No items below 29%', $script);
        $this->assertStringContainsString('All Items In Stock', $script);
        $this->assertStringContainsString('Show More', $script);
        $this->assertStringContainsString('activeStockFilter', $script);
        $this->assertStringContainsString('inventorySearchTerm', $script);
        $this->assertStringContainsString('inventorySortMode', $script);
    }

    public function test_inventory_script_supports_option_based_consumption_mappings(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('add_consumption_checkbox', $script);
        $this->assertStringContainsString('edit_consumption_checkbox', $script);
        $this->assertStringContainsString('order_template_option_type_id', $script);
        $this->assertStringContainsString('Any Option (Fallback)', $script);
    }

    public function test_inventory_render_initializes_usage_text_for_every_row(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('const usageText = buildMaterialUsageText(material);', $script);
        $this->assertStringContainsString('const stockSnapshot = resolveMaterialStockSnapshot(material);', $script);
        $this->assertStringContainsString('stock_badge_${stockSnapshot.stockBand}', $script);
        $this->assertStringContainsString('<td>${usageText}</td>', $script);
    }

    public function test_inventory_save_flow_tracks_selected_consumption_and_blocks_invalid_inputs(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('let hasSelectedProduct = false;', $script);
        $this->assertStringContainsString('if (!hasSelectedProduct)', $script);
        $this->assertStringContainsString('if (errors.length > 0)', $script);
        $this->assertStringContainsString('Please fix the highlighted fields.', $script);
        $this->assertStringNotContainsString('let quantitiesValid = true;', $script);
    }

    public function test_inventory_load_flow_distinguishes_fetch_and_render_failures(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('Error loading inventory data:', $script);
        $this->assertStringContainsString('Error rendering inventory UI:', $script);
        $this->assertStringContainsString('Failed to render inventory. Please refresh the page.', $script);
    }

    public function test_inventory_delete_flow_uses_modal_and_not_browser_confirm(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);
        $this->assertStringNotContainsString('confirm(', $script);
        $this->assertStringContainsString('openDeleteMaterialConfirm', $script);
        $this->assertStringContainsString('closeDeleteMaterialConfirm', $script);
        $this->assertStringContainsString('confirmDeleteMaterial', $script);
        $this->assertStringContainsString('Deleting...', $script);
        $this->assertStringContainsString('deleteMaterialConfirmOverlay', $script);
    }

    public function test_inventory_delete_lock_explicitly_guards_outside_click_and_escape_paths(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/inventory_refactored.js'));

        $this->assertIsString($script);

        $this->assertMatchesRegularExpression(
            '/deleteMaterialConfirmOverlay\.addEventListener\("click", \(e\) => \{\s*if \(e\.target === deleteMaterialConfirmOverlay\) \{\s*if \(isDeletingMaterial\) \{\s*return;\s*\}\s*closeDeleteMaterialConfirm\(\);/s',
            $script,
        );

        $this->assertMatchesRegularExpression(
            '/if \(event\.key === "Escape"\) \{\s*event\.preventDefault\(\);\s*if \(isDeleteModalActive\) \{\s*if \(isDeletingMaterial\) \{\s*return;\s*\}\s*closeDeleteMaterialConfirm\(\);/s',
            $script,
        );
    }

    public function test_inventory_css_contains_status_state_and_pulse_styles(): void
    {
        $css = file_get_contents(base_path('resources/css/owner/pages/inventory.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.high_stock', $css);
        $this->assertStringContainsString('.medium_stock', $css);
        $this->assertStringContainsString('.status_card.is_active', $css);
        $this->assertStringContainsString('.card.status_healthy', $css);
        $this->assertStringContainsString('.card.status_alert.pulse_glow.low_stock', $css);
        $this->assertStringContainsString('.card.status_alert.pulse_glow.out_of_stock', $css);
        $this->assertStringContainsString('.inventory_controls', $css);
        $this->assertStringContainsString('.stock_badge_high', $css);
        $this->assertStringContainsString('.status_more_btn', $css);
    }

    public function test_material_api_client_supports_server_side_filter_query_strings(): void
    {
        $script = file_get_contents(base_path('resources/js/api/materialApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('buildMaterialQueryString', $script);
        $this->assertStringContainsString("appendParam('search'", $script);
        $this->assertStringContainsString("appendParam('stock_band'", $script);
        $this->assertStringContainsString("appendParam('sort_by'", $script);
        $this->assertStringContainsString("appendParam('sort_direction'", $script);
    }
}
