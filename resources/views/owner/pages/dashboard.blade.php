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

    <h2 class="section_title">Charts</h2>
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
        // backend instructions for aaron:
        // Replace the 'mockDatabaseData' object below with real data from the controller.
        // You can inject it directly using Laravel's json directive like this:
        // const chartData = @@json($chartData);
        // ==========================================
        
        const chartData = {
            // Array of 12 numbers representing total revenue from Jan to Dec
            monthly_revenue: [150, 90, 480, 20, 290, 350, 410, 250, 500, 180, 320, 450], 
            
            // Object holding 12 arrays. Each array has 5 numbers representing the 5 categories: 
            // [Stickers, Button Pins, Posters, Business Cards, Photocards]
            monthly_sales_categories: {
                0: [40, 20, 10, 10, 20],  // Jan
                1: [25, 30, 15,  5, 25],  // Feb
                2: [50, 10,  5, 15, 20],  // Mar
                3: [10, 40, 20, 10, 20],  // Apr
                4: [45, 15, 10, 20, 10],  // May
                5: [35, 20, 15, 15, 15],  // Jun
                6: [40, 20, 10, 10, 20],  // Jul
                7: [30, 25, 15,  5, 25],  // Aug
                8: [50, 10,  5, 15, 20],  // Sep
                9: [20, 30, 20, 10, 20],  // Oct
                10:[45, 15, 10, 20, 10],  // Nov
                11:[35, 20, 15, 15, 15]   // Dec
            }
        };

        // --- 1. Monthly Revenue Bar Chart ---
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Revenue (Php)',
                    data: chartData.monthly_revenue, // <--- PULLS FROM THE UNIFIED DATA
                    backgroundColor: [
                        '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7',
                        '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7', '#DCBAE6', '#9659A7'
                    ],
                    borderRadius: 10, 
                    barThickness: 40
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false }, ticks: { font: { family: 'Coolvetica' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Coolvetica' } } }
                }
            }
        });

        // --- 2. Monthly Sales Pie Chart ---
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        window.salesPieChart = new Chart(salesCtx, {
            type: 'pie',
            data: {
                labels: ['Stickers', 'Button Pins', 'Posters', 'Business Cards', 'Photocards'],
                datasets: [{
                    data: chartData.monthly_sales_categories[2], // <--- Defaults to March (Index 2)
                    backgroundColor: ['#9659A7', '#DCBAE6', '#FFF2D9', '#F4D6D2', '#CDBAA7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Coolvetica', size: 12 }, usePointStyle: true, boxWidth: 8 }
                    }
                }
            }
        });

        // --- 3. Interactive Dropdown Logic ---
        window.updatePieChart = function() {
            const dropdown = document.getElementById('monthSelector');
            const selectedMonthIndex = dropdown.value; 
            
            // Swap out the data array with the newly selected month from our unified data object
            window.salesPieChart.data.datasets[0].data = chartData.monthly_sales_categories[selectedMonthIndex];
            window.salesPieChart.update();
        };
    });
</script>
@endsection