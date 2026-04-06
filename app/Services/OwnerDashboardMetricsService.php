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

    /**
     * @var list<string>
     */
    private const PENDING_STATUSES = ['waiting', 'approved', 'preparing', 'ready'];

    /**
     * Build owner dashboard metrics for the selected year and month.
     *
     * @return array<string, mixed>
     */
    public function build(?int $requestedYear = null, ?int $requestedMonth = null): array
    {
        $now = CarbonImmutable::now();

        $availableYears = $this->buildAvailableYears($now->year);
        $selectedYear = $this->resolveSelectedYear($requestedYear, $availableYears, $now->year);
        $selectedMonth = $this->resolveSelectedMonth($requestedMonth, $now->month - 1);

        $weekStart = $now->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
        $weekEnd = $now->endOfWeek(CarbonImmutable::SUNDAY)->endOfDay();

        $weeklyGroupsQuery = CustomerOrderGroup::query()
            ->whereBetween('created_at', [$weekStart, $weekEnd]);

        $weeklyTotalSales = (float) (clone $weeklyGroupsQuery)
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $weeklyItemsSold = (int) CustomerOrder::query()
            ->whereHas('orderGroup', function ($query) use ($weekStart, $weekEnd): void {
                $query->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->where('status', '!=', 'cancelled');
            })
            ->sum('quantity');

        $lowStockItemName = Material::query()
            ->whereColumn('units', '<=', 'low_stock_threshold')
            ->orderBy('units')
            ->orderBy('name')
            ->value('name');

        $weeklyTotalOrders = (int) (clone $weeklyGroupsQuery)->count();
        $weeklyReceivedPayment = (int) (clone $weeklyGroupsQuery)
            ->where('status', 'completed')
            ->count();

        $weeklyPendingPayment = (int) (clone $weeklyGroupsQuery)
            ->whereIn('status', self::PENDING_STATUSES)
            ->count();

        $weeklyCanceledOrders = (int) (clone $weeklyGroupsQuery)
            ->where('status', 'cancelled')
            ->count();

        $monthlyRevenue = $this->buildMonthlyRevenue($selectedYear);
        $monthlySales = $this->buildMonthlySalesBreakdown($selectedYear);

        return [
            'available_years' => $availableYears,
            'selected_year' => $selectedYear,
            'selected_month' => $selectedMonth,
            'weekly_report' => [
                'total_sales' => round($weeklyTotalSales, 2),
                'items_sold' => $weeklyItemsSold,
                'low_stock_item_name' => $lowStockItemName ?: null,
            ],
            'weekly_sales' => [
                'total_orders' => $weeklyTotalOrders,
                'received_payment' => $weeklyReceivedPayment,
                'pending_payment' => $weeklyPendingPayment,
                'canceled_orders' => $weeklyCanceledOrders,
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

        $firstOrderYear = $firstOrderCreatedAt
            ? CarbonImmutable::parse((string) $firstOrderCreatedAt)->year
            : $currentYear;

        $startYear = min(self::LAUNCH_YEAR, $firstOrderYear, $currentYear);

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

    /**
     * @return list<float>
     */
    private function buildMonthlyRevenue(int $year): array
    {
        $monthlyRevenue = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyRevenue[] = round((float) CustomerOrderGroup::query()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('status', '!=', 'cancelled')
                ->sum('total_price'), 2);
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

            $rows = DB::table('customer_orders as customer_orders')
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
            $overallTotal = (int) $rows->sum(fn ($row): int => (int) $row->total_quantity);
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
