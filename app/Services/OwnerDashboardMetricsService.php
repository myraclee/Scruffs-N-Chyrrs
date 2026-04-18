<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\Material;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OwnerDashboardMetricsService
{
    private const LAUNCH_YEAR = 2026;
    private const DEFAULT_PERIOD = 'weekly';

    /**
     * @var list<string>
     */
    private const PERIODS = ['daily', 'weekly', 'monthly', 'yearly'];

    /**
     * @var list<string>
     */
    private const PENDING_PAYMENT_STATUSES = ['awaiting_payment', 'waiting_payment_confirmation'];

    /**
     * Build owner dashboard metrics for the selected year and month.
     *
     * @return array<string, mixed>
     */
    public function build(
        ?int $requestedYear = null,
        ?int $requestedMonth = null,
        ?string $requestedReportPeriod = null,
        ?string $requestedSalesPeriod = null,
        ?string $requestedPeriod = null,
    ): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);

        $availableYears = $this->buildAvailableYears($now->year);
        $selectedYear = $this->resolveSelectedYear($requestedYear, $availableYears, $now->year);
        $selectedMonth = $this->resolveSelectedMonth($requestedMonth, $now->month - 1);
        $selectedReportPeriod = $this->resolveSelectedPeriod($requestedReportPeriod ?? $requestedPeriod);
        $selectedSalesPeriod = $this->resolveSelectedPeriod($requestedSalesPeriod ?? $requestedPeriod);

        [$reportPeriodStart, $reportPeriodEnd] = $this->buildPeriodDateRange($selectedReportPeriod, $now);
        [$salesPeriodStart, $salesPeriodEnd] = $this->buildPeriodDateRange($selectedSalesPeriod, $now);

        $reportTotals = $this->buildSummaryTotalsForRange($reportPeriodStart, $reportPeriodEnd);
        $salesTotals = $this->buildSummaryTotalsForRange($salesPeriodStart, $salesPeriodEnd);

        $lowStockItemName = Material::query()
            ->whereColumn('units', '<=', 'low_stock_threshold')
            ->orderBy('units')
            ->orderBy('name')
            ->value('name');

        $monthlyRevenue = $this->buildMonthlyRevenue($selectedYear);
        $monthlySales = $this->buildMonthlySalesBreakdown($selectedYear);

        return [
            'available_years' => $availableYears,
            'selected_year' => $selectedYear,
            'selected_month' => $selectedMonth,
            'selected_period' => $selectedReportPeriod,
            'selected_report_period' => $selectedReportPeriod,
            'selected_sales_period' => $selectedSalesPeriod,
            'weekly_report' => [
                'total_sales' => round($reportTotals['total_sales'], 2),
                'items_sold' => $reportTotals['items_sold'],
                'low_stock_item_name' => $lowStockItemName ?: null,
            ],
            'weekly_sales' => [
                'total_orders' => $salesTotals['total_orders'],
                'received_payment' => $salesTotals['received_payment'],
                'pending_payment' => $salesTotals['pending_payment'],
                'canceled_orders' => $salesTotals['canceled_orders'],
            ],
            'charts' => [
                'monthly_revenue' => $monthlyRevenue,
                'has_revenue_data' => $this->containsPositiveValue($monthlyRevenue),
                'monthly_sales' => $monthlySales,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function buildAvailableYears(int $currentYear): array
    {
        $firstOrderCreatedAt = CustomerOrderGroup::query()
            ->orderBy('created_at')
            ->value('created_at');

        $firstArchivedMetricDate = DB::table('dashboard_deleted_account_daily_metrics')
            ->orderBy('metric_date')
            ->value('metric_date');

        $candidateYears = [self::LAUNCH_YEAR, $currentYear];

        if ($firstOrderCreatedAt) {
            $candidateYears[] = CarbonImmutable::parse((string) $firstOrderCreatedAt)->year;
        }

        if ($firstArchivedMetricDate) {
            $candidateYears[] = CarbonImmutable::parse((string) $firstArchivedMetricDate)->year;
        }

        $startYear = min($candidateYears);

        return range($startYear, $currentYear);
    }

    /**
     * @param list<int> $availableYears
     */
    private function resolveSelectedYear(?int $requestedYear, array $availableYears, int $fallbackYear): int
    {
        $candidate = $requestedYear ?? $fallbackYear;

        if (in_array($candidate, $availableYears, true)) {
            return $candidate;
        }

        if (in_array($fallbackYear, $availableYears, true)) {
            return $fallbackYear;
        }

        return (int) end($availableYears);
    }

    private function resolveSelectedMonth(?int $requestedMonth, int $fallbackMonth): int
    {
        $candidate = $requestedMonth ?? $fallbackMonth;

        if ($candidate >= 0 && $candidate <= 11) {
            return $candidate;
        }

        if ($fallbackMonth >= 0 && $fallbackMonth <= 11) {
            return $fallbackMonth;
        }

        return 0;
    }

    private function resolveSelectedPeriod(?string $requestedPeriod): string
    {
        if ($requestedPeriod === null) {
            return self::DEFAULT_PERIOD;
        }

        $candidate = strtolower(trim($requestedPeriod));

        if (in_array($candidate, self::PERIODS, true)) {
            return $candidate;
        }

        return self::DEFAULT_PERIOD;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function buildPeriodDateRange(string $selectedPeriod, CarbonImmutable $now): array
    {
        $periodEnd = $now->endOfDay();

        return match ($selectedPeriod) {
            'daily' => [$now->startOfDay(), $periodEnd],
            'monthly' => [$now->startOfMonth()->startOfDay(), $periodEnd],
            'yearly' => [$now->startOfYear()->startOfDay(), $periodEnd],
            default => [$now->startOfWeek(CarbonImmutable::MONDAY)->startOfDay(), $periodEnd],
        };
    }

    /**
     * @return array{
     *     total_sales: float,
     *     items_sold: int,
     *     total_orders: int,
     *     received_payment: int,
     *     pending_payment: int,
     *     canceled_orders: int
     * }
     */
    private function buildSummaryTotalsForRange(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $archivedTotals = DB::table('dashboard_deleted_account_daily_metrics')
            ->whereBetween('metric_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('COALESCE(SUM(total_sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(items_sold), 0) as items_sold')
            ->selectRaw('COALESCE(SUM(total_orders), 0) as total_orders')
            ->selectRaw('COALESCE(SUM(received_payment), 0) as received_payment')
            ->selectRaw('COALESCE(SUM(pending_payment), 0) as pending_payment')
            ->selectRaw('COALESCE(SUM(canceled_orders), 0) as canceled_orders')
            ->first();

        $periodGroupsQuery = CustomerOrderGroup::query()
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $liveTotalSales = (float) (clone $periodGroupsQuery)
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $liveItemsSold = (int) CustomerOrder::query()
            ->whereHas('orderGroup', function ($query) use ($periodStart, $periodEnd): void {
                $query->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->where('status', '!=', 'cancelled');
            })
            ->sum('quantity');

        $liveTotalOrders = (int) (clone $periodGroupsQuery)->count();
        $liveReceivedPayment = (int) (clone $periodGroupsQuery)
            ->where('payment_status', 'payment_received')
            ->count();

        $livePendingPayment = (int) (clone $periodGroupsQuery)
            ->whereIn('payment_status', self::PENDING_PAYMENT_STATUSES)
            ->count();

        $liveCanceledOrders = (int) (clone $periodGroupsQuery)
            ->where('status', 'cancelled')
            ->count();

        return [
            'total_sales' => $liveTotalSales + (float) ($archivedTotals->total_sales ?? 0),
            'items_sold' => $liveItemsSold + (int) ($archivedTotals->items_sold ?? 0),
            'total_orders' => $liveTotalOrders + (int) ($archivedTotals->total_orders ?? 0),
            'received_payment' => $liveReceivedPayment + (int) ($archivedTotals->received_payment ?? 0),
            'pending_payment' => $livePendingPayment + (int) ($archivedTotals->pending_payment ?? 0),
            'canceled_orders' => $liveCanceledOrders + (int) ($archivedTotals->canceled_orders ?? 0),
        ];
    }

    /**
     * @return list<float>
     */
    private function buildMonthlyRevenue(int $year): array
    {
        $monthlyRevenue = [];

        for ($month = 1; $month <= 12; $month++) {
            $liveRevenue = (float) CustomerOrderGroup::query()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('status', '!=', 'cancelled')
                ->sum('total_price');

            $archivedRevenue = (float) DB::table('dashboard_deleted_account_daily_metrics')
                ->whereYear('metric_date', $year)
                ->whereMonth('metric_date', $month)
                ->sum('total_sales');

            $monthlyRevenue[] = round($liveRevenue + $archivedRevenue, 2);
        }

        return $monthlyRevenue;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildMonthlySalesBreakdown(int $year): array
    {
        $labelsByMonth = [];
        $valuesByMonth = [];
        $hasDataByMonth = [];

        for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
            $month = $monthIndex + 1;

            $liveRows = DB::table('customer_orders as customer_orders')
                ->join('customer_order_groups as groups', 'groups.id', '=', 'customer_orders.customer_order_group_id')
                ->join('products as products', 'products.id', '=', 'customer_orders.product_id')
                ->whereYear('customer_orders.created_at', $year)
                ->whereMonth('customer_orders.created_at', $month)
                ->where('groups.status', '!=', 'cancelled')
                ->select([
                    'customer_orders.product_id',
                    'products.name as product_name',
                    DB::raw('SUM(customer_orders.quantity) as total_quantity'),
                ])
                ->groupBy('customer_orders.product_id', 'products.name')
                ->orderByDesc('total_quantity')
                ->get();

            $archivedRows = DB::table('dashboard_deleted_account_monthly_product_sales')
                ->where('year', $year)
                ->where('month', $month)
                ->select([
                    'product_id',
                    'product_name',
                    DB::raw('SUM(total_quantity) as total_quantity'),
                ])
                ->groupBy('product_id', 'product_name')
                ->get();

            $combinedByProductId = [];

            foreach ($liveRows as $row) {
                $key = (string) $row->product_id;

                if (! isset($combinedByProductId[$key])) {
                    $combinedByProductId[$key] = [
                        'product_name' => (string) $row->product_name,
                        'total_quantity' => 0,
                    ];
                }

                $combinedByProductId[$key]['total_quantity'] += (int) $row->total_quantity;
            }

            foreach ($archivedRows as $row) {
                $key = (string) $row->product_id;

                if (! isset($combinedByProductId[$key])) {
                    $combinedByProductId[$key] = [
                        'product_name' => (string) $row->product_name,
                        'total_quantity' => 0,
                    ];
                }

                $combinedByProductId[$key]['total_quantity'] += (int) $row->total_quantity;
            }

            $rows = collect(array_values($combinedByProductId))
                ->sortByDesc('total_quantity')
                ->values();

            $monthKey = (string) $monthIndex;

            if ($rows->isEmpty()) {
                $labelsByMonth[$monthKey] = ['No data'];
                $valuesByMonth[$monthKey] = [0];
                $hasDataByMonth[$monthKey] = false;
                continue;
            }

            $topRows = $rows->take(5);
            $labels = $topRows->pluck('product_name')
                ->map(fn ($name): string => (string) $name)
                ->values()
                ->all();

            $values = $topRows->pluck('total_quantity')
                ->map(fn ($quantity): int => (int) $quantity)
                ->values()
                ->all();

            $topTotal = array_sum($values);
            $overallTotal = (int) $rows->sum(fn (array $row): int => (int) $row['total_quantity']);
            $othersTotal = $overallTotal - $topTotal;

            if ($othersTotal > 0) {
                $labels[] = 'Others';
                $values[] = $othersTotal;
            }

            $labelsByMonth[$monthKey] = $labels;
            $valuesByMonth[$monthKey] = $values;
            $hasDataByMonth[$monthKey] = true;
        }

        return [
            'labels_by_month' => $labelsByMonth,
            'values_by_month' => $valuesByMonth,
            'has_data_by_month' => $hasDataByMonth,
        ];
    }

    /**
     * @param list<float> $values
     */
    private function containsPositiveValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value > 0) {
                return true;
            }
        }

        return false;
    }
}
