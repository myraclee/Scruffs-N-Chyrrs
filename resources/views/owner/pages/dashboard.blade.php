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
    @endphp
    
    {{-- Backend Dev: Replace 'Celina' with {{ Auth::user()->name }} --}}
    <h1 class="welcome_header">{{ $randomGreeting }} <span class="welcome_name">Celina!</span></h1>
    
 <h2 class="section_title">Weekly Report</h2>
    <div class="report_grid">
        <div class="report_card">
            <span class="card_label">Total Sales</span>
            <span class="card_value purple_text">Php {{ number_format($weeklyTotalSales ?? 4500, 2) }}</span>
        </div>
        <div class="report_card">
            <span class="card_label">Items Sold</span>
            <span class="card_value purple_text">{{ $weeklyItemsSold ?? 103 }}</span>
        </div>
        <div class="report_card">
            <span class="card_label">Low Stocks</span>
            <span class="card_value_small purple_text">{{ $lowStockItemName ?? 'Glossy Lamination' }}</span>
        </div>
    </div>

    <h2 class="section_title">Weekly Sales</h2>
    <div class="sales_grid">
        <div class="sales_card border-purple">
            <span class="card_label text-purple">Total Orders</span>
            <span class="card_value text-purple">{{ $weeklyTotalOrders ?? 8 }}</span>
        </div>
        <div class="sales_card border-green">
            <span class="card_label text-green">Received Payment</span>
            <span class="card_value text-green">{{ $weeklyReceivedPayment ?? 3 }}</span>
        </div>
        <div class="sales_card border-yellow">
            <span class="card_label text-yellow">Pending Payment</span>
            <span class="card_value text-yellow">{{ $weeklyPendingPayment ?? 4 }}</span>
        </div>
        <div class="sales_card border-red">
            <span class="card_label text-red">Canceled Orders</span>
            <span class="card_value text-red">{{ $weeklyCanceledOrders ?? 1 }}</span>
        </div>
    </div>

    <div class="charts_header_container">
        <h2 class="section_title" style="margin: 0;">Charts</h2>
        <select id="yearSelector" class="year_dropdown" onchange="updateAllCharts()">
            {{-- Dynamically loop through the years --}}
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
            
        </div>

        <div class="chart_card">
            <div class="chart_header_row">
                <h3 class="chart_title">Monthly Sales</h3>
                
                <select id="monthSelector" class="month_dropdown" onchange="updatePieChart()">
                    <option value="0">January</option>
                    <option value="1">February</option>
                    <option value="2" selected>March</option> <option value="3">April</option>
                    <option value="4">May</option>
                    <option value="5">June</option>
                    <option value="6">July</option>
                    <option value="7">August</option>
                    <option value="8">September</option>
                    <option value="9">October</option>
                    <option value="10">November</option>
                    <option value="11">December</option>
                </select>
            </div>
            
            <div class="canvas_wrapper">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // ==========================================
        // 🚀 BACKEND DEV INSTRUCTIONS (AARON):
        // The data is now nested by YEAR. 
        // Inject your JSON here: const chartData = @@json($chartData);
        // ==========================================
        
        const chartData = {
            "2026": {
                monthly_revenue: [150, 90, 480, 20, 290, 350, 410, 250, 500, 180, 320, 450], 
                monthly_sales_categories: {
                    0: [40, 20, 10, 10, 20], 1: [25, 30, 15,  5, 25], 2: [50, 10,  5, 15, 20], 
                    3: [10, 40, 20, 10, 20], 4: [45, 15, 10, 20, 10], 5: [35, 20, 15, 15, 15], 
                    6: [40, 20, 10, 10, 20], 7: [30, 25, 15,  5, 25], 8: [50, 10,  5, 15, 20], 
                    9: [20, 30, 20, 10, 20], 10:[45, 15, 10, 20, 10], 11:[35, 20, 15, 15, 15]
                }
            },
            "2027": {
                monthly_revenue: [200, 300, 150, 600, 450, 500, 700, 350, 800, 400, 650, 900], // Noticeably higher fake sales!
                monthly_sales_categories: {
                    0: [10, 50, 20, 10, 10], 1: [15, 45, 20, 10, 10], 2: [20, 40, 20, 10, 10], 
                    3: [25, 35, 20, 10, 10], 4: [30, 30, 20, 10, 10], 5: [35, 25, 20, 10, 10], 
                    6: [40, 20, 20, 10, 10], 7: [45, 15, 20, 10, 10], 8: [50, 10, 20, 10, 10], 
                    9: [55,  5, 20, 10, 10], 10:[60,  0, 20, 10, 10], 11:[65,  0, 15, 10, 10]
                }
            }
            // Backend will naturally generate 2028, 2029, etc. here
        };

        // --- Initialize Charts with default 2026 data ---
        const currentYear = document.getElementById('yearSelector').value;
        const currentMonthIndex = document.getElementById('monthSelector').value; 

        // 🛡️ THE FAILSAFE: If the year doesn't exist in our fake data yet, give it empty arrays instead of crashing!
        const initialRevenue = chartData[currentYear] ? chartData[currentYear].monthly_revenue : [];
        const initialSales = chartData[currentYear] ? chartData[currentYear].monthly_sales_categories[currentMonthIndex] : [];

        // 1. Monthly Revenue Bar Chart
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        window.revenueBarChart = new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Revenue (Php)',
                    data: initialRevenue, /* <--- Uses the safe data */
                    backgroundColor: [
                        '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7',
                        '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7'
                    ],
                    borderRadius: 10, barThickness: 40
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false }, ticks: { font: { family: 'Coolvetica' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Coolvetica' } } }
                }
            }
        });

        // 2. Monthly Sales Pie Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        window.salesPieChart = new Chart(salesCtx, {
            type: 'pie',
            data: {
                labels: ['Stickers', 'Button Pins', 'Posters', 'Business Cards', 'Photocards'],
                datasets: [{
                    data: initialSales, /* <--- Uses the safe data */
                    backgroundColor: ['#9659A7', '#DCBAE6', '#FFF2D9', '#F4D6D2', '#CDBAA7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { font: { family: 'Coolvetica', size: 12 }, usePointStyle: true, boxWidth: 8 } }
                }
            }
        });

        // --- Interactive Dropdown Logic ---
        
        // Triggers when the MONTH changes
        window.updatePieChart = function() {
            const selectedYear = document.getElementById('yearSelector').value;
            const selectedMonthIndex = document.getElementById('monthSelector').value; 
            
            // Failsafe: if year doesn't exist in data yet, don't crash
            if(!chartData[selectedYear]) return; 

            window.salesPieChart.data.datasets[0].data = chartData[selectedYear].monthly_sales_categories[selectedMonthIndex];
            window.salesPieChart.update();
        };

        // Triggers when the YEAR changes (Updates BOTH charts!)
        window.updateAllCharts = function() {
            const selectedYear = document.getElementById('yearSelector').value;
            
            // Failsafe: If they pick 2028 and Aaron hasn't added data, just clear the charts so it doesn't crash
            if(!chartData[selectedYear]) {
                window.revenueBarChart.data.datasets[0].data = [];
                window.salesPieChart.data.datasets[0].data = [];
            } else {
                // 1. Update Bar Chart
                window.revenueBarChart.data.datasets[0].data = chartData[selectedYear].monthly_revenue;
                // 2. Update Pie Chart (by calling the month function to re-read the currently selected month)
                window.updatePieChart(); 
            }
            
            window.revenueBarChart.update();
            window.salesPieChart.update();
        };
    });
</script>
@endsection