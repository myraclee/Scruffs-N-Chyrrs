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
        $metrics = $this->metricsService->build(
            $request->query('year') !== null ? (int) $request->query('year') : null,
            $request->query('month') !== null ? (int) $request->query('month') : null,
        );

        return view('owner.pages.dashboard', [
            'availableYears' => $metrics['available_years'],
            'currentYear' => $metrics['selected_year'],
            'selectedMonth' => $metrics['selected_month'],
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
