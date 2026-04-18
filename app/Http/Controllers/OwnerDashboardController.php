<?php

namespace App\Http\Controllers;

use App\Services\OwnerDashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function __construct(
        private readonly OwnerDashboardMetricsService $metricsService,
    ) {
    }

    public function index(Request $request): View
    {
        $reportPeriod = $request->query('report_period');
        $salesPeriod = $request->query('sales_period');
        $legacyPeriod = $request->query('period');

        $metrics = $this->metricsService->build(
            $request->query('year') !== null ? (int) $request->query('year') : null,
            $request->query('month') !== null ? (int) $request->query('month') : null,
            $reportPeriod !== null ? (string) $reportPeriod : null,
            $salesPeriod !== null ? (string) $salesPeriod : null,
            $legacyPeriod !== null ? (string) $legacyPeriod : null,
        );

        return view('owner.pages.dashboard', [
            'availableYears' => $metrics['available_years'],
            'currentYear' => $metrics['selected_year'],
            'selectedMonth' => $metrics['selected_month'],
            'selectedPeriod' => $metrics['selected_period'],
            'selectedReportPeriod' => $metrics['selected_report_period'],
            'selectedSalesPeriod' => $metrics['selected_sales_period'],
            'weeklyTotalSales' => $metrics['weekly_report']['total_sales'],
            'weeklyItemsSold' => $metrics['weekly_report']['items_sold'],
            'lowStockItemName' => $metrics['weekly_report']['low_stock_item_name'],
            'weeklyTotalOrders' => $metrics['weekly_sales']['total_orders'],
            'weeklyReceivedPayment' => $metrics['weekly_sales']['received_payment'],
            'weeklyPendingPayment' => $metrics['weekly_sales']['pending_payment'],
            'weeklyCanceledOrders' => $metrics['weekly_sales']['canceled_orders'],
            'dashboardData' => $metrics,
        ]);
    }
}
