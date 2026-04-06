@extends('owner.layouts.owner_layout')

@section('page_css')
    @vite(['resources/css/owner/pages/dashboard.css'])
@endsection

@section('content')
<div class="dashboard_container">
@php
        $greetings = [
            'Welcome back,',
            'Good day,',
            'Ready to create,',
            'Hello there,',
            'Great to see you,'
        ];
        $randomGreeting = $greetings[array_rand($greetings)];
        $monthOptions = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
        $ownerFullName = trim((string) (auth()->user()?->first_name ?? '').' '.(string) (auth()->user()?->last_name ?? ''));
        $selectedMonthValue = (int) ($selectedMonth ?? (now()->month - 1));
        $selectedMonthKey = (string) $selectedMonthValue;
        $initialSalesHasData = (bool) data_get($dashboardData ?? [], "charts.monthly_sales.has_data_by_month.$selectedMonthKey", false);
    @endphp

    <h1 class="welcome_header">{{ $randomGreeting }} <span class="welcome_name">{{ $ownerFullName !== '' ? $ownerFullName : 'Owner' }}!</span></h1>
    
 <h2 class="section_title">Weekly Report</h2>
    <div class="report_grid">
        <div class="report_card">
            <span class="card_label">Total Sales</span>
            <span id="weeklyTotalSales" class="card_value purple_text">Php {{ number_format((float) ($weeklyTotalSales ?? 0), 2) }}</span>
        </div>
        <div class="report_card">
            <span class="card_label">Items Sold</span>
            <span id="weeklyItemsSold" class="card_value purple_text">{{ (int) ($weeklyItemsSold ?? 0) }}</span>
        </div>
        <div class="report_card">
            <span class="card_label">Low Stocks</span>
            <span id="lowStockItemName" class="card_value_small purple_text">{{ $lowStockItemName ?? 'No low stock items' }}</span>
        </div>
    </div>

    <h2 class="section_title">Weekly Sales</h2>
    <div class="sales_grid">
        <div class="sales_card border-purple">
            <span class="card_label text-purple">Total Orders</span>
            <span id="weeklyTotalOrders" class="card_value text-purple">{{ (int) ($weeklyTotalOrders ?? 0) }}</span>
        </div>
        <div class="sales_card border-green">
            <span class="card_label text-green">Received Payment</span>
            <span id="weeklyReceivedPayment" class="card_value text-green">{{ (int) ($weeklyReceivedPayment ?? 0) }}</span>
        </div>
        <div class="sales_card border-yellow">
            <span class="card_label text-yellow">Pending Payment</span>
            <span id="weeklyPendingPayment" class="card_value text-yellow">{{ (int) ($weeklyPendingPayment ?? 0) }}</span>
        </div>
        <div class="sales_card border-red">
            <span class="card_label text-red">Canceled Orders</span>
            <span id="weeklyCanceledOrders" class="card_value text-red">{{ (int) ($weeklyCanceledOrders ?? 0) }}</span>
        </div>
    </div>

    <div class="charts_header_container">
        <h2 class="section_title" style="margin: 0;">Charts</h2>
        <select id="yearSelector" class="year_dropdown">
            @foreach($availableYears as $year)
                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                    {{ $year }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="charts_container">
        
        <div class="chart_card">
            <h3 class="chart_title">Monthly Revenue</h3>
            
            <div class="chart_scroll_area">
                <div class="canvas_wrapper" style="min-width: 1200px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <p id="revenueNoDataHint" class="chart_hint" @if(data_get($dashboardData ?? [], 'charts.has_revenue_data', false)) hidden @endif>
                No revenue data for the selected year.
            </p>
        </div>

        <div class="chart_card">
            <div class="chart_header_row">
                <h3 class="chart_title">Monthly Sales</h3>
                
                <select id="monthSelector" class="month_dropdown">
                    @foreach($monthOptions as $monthIndex => $monthLabel)
                        <option value="{{ $monthIndex }}" {{ $selectedMonthValue === $monthIndex ? 'selected' : '' }}>
                            {{ $monthLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="canvas_wrapper">
                <canvas id="salesChart"></canvas>
            </div>

            <p id="salesNoDataHint" class="chart_hint" @if($initialSalesHasData) hidden @endif>
                No monthly sales data for the selected month.
            </p>
        </div>

    </div>
</div>

<script id="ownerDashboardBootstrap" type="application/json">@json($dashboardData ?? [])</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@vite('resources/js/owner/pages/dashboard.js')
@endsection