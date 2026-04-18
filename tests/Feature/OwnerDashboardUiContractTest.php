<?php

namespace Tests\Feature;

use Tests\TestCase;

class OwnerDashboardUiContractTest extends TestCase
{
    public function test_dashboard_blade_contains_bootstrap_payload_and_script_hooks(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/dashboard.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('id="ownerDashboardBootstrap"', $blade);
        $this->assertStringContainsString('id="periodSelector"', $blade);
        $this->assertStringContainsString('id="salesPeriodSelector"', $blade);
        $this->assertStringContainsString('id="reportSectionTitle"', $blade);
        $this->assertStringContainsString('id="salesSectionTitle"', $blade);
        $this->assertStringNotContainsString('Applies to Report and Sales', $blade);
        $this->assertStringContainsString('id="yearSelector"', $blade);
        $this->assertStringContainsString('id="monthSelector"', $blade);
        $this->assertStringContainsString('id="revenueNoDataHint"', $blade);
        $this->assertStringContainsString('id="salesNoDataHint"', $blade);
        $this->assertStringContainsString("@vite('resources/js/owner/pages/dashboard.js')", $blade);
        $this->assertStringNotContainsString('const chartData = {', $blade);
    }

    public function test_dashboard_scripts_define_api_fetch_and_chart_update_flow(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/pages/dashboard.js'));
        $apiClient = file_get_contents(base_path('resources/js/api/ownerDashboardApi.js'));

        $this->assertIsString($script);
        $this->assertIsString($apiClient);

        $this->assertStringContainsString('OwnerDashboardAPI.getMetrics', $script);
        $this->assertStringContainsString('handleReportPeriodChange', $script);
        $this->assertStringContainsString('handleSalesPeriodChange', $script);
        $this->assertStringContainsString('updateRevenueChart', $script);
        $this->assertStringContainsString('updateSalesChart', $script);
        $this->assertStringContainsString('selected_report_period', $script);
        $this->assertStringContainsString('selected_sales_period', $script);
        $this->assertStringContainsString('has_data_by_month', $script);

        $this->assertStringContainsString('/api/owner/dashboard/metrics', $apiClient);
        $this->assertStringContainsString('getMetrics', $apiClient);
        $this->assertStringContainsString('report_period', $apiClient);
        $this->assertStringContainsString('sales_period', $apiClient);
        $this->assertStringContainsString('period', $apiClient);
    }
}
