@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/view_orders.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/view_orders.js')
@endsection

@section('content')

{{-- Background Sparkles --}}
<div class="orders_sparkles_bg" aria-hidden="true">
    <span class="orders_spark s1">✦</span>
    <span class="orders_spark s2">✧</span>
    <span class="orders_spark s3">✦</span>
    <span class="orders_spark s4">✧</span>
    <span class="orders_spark s5">✦</span>
    <span class="orders_spark s6">✧</span>
    <span class="orders_spark s7">✦</span>
    <span class="orders_spark s8">✧</span>
    <span class="orders_spark s9">✦</span>
    <span class="orders_spark s10">✧</span>
    <span class="orders_spark s11">✦</span>
    <span class="orders_spark s12">✧</span>
</div>

<div class="orders_container">

    {{-- Page Header --}}
    <div class="orders_page_header">
        <div class="orders_header_sparkles">
            <span>✦</span>
            <h1 class="orders_page_title">Hi, {{ Auth::user()->first_name }}!</h1>
            <span>✦</span>
        </div>
        <p class="orders_page_subtitle">Your designs are waiting! Time to bring them to life!</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert_success">
            <span class="alert_icon">✓</span>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert_error">
            <span class="alert_icon">✕</span>
            {{ session('error') }}
        </div>
    @endif

    {{-- MY CART SECTION --}}
    <div class="orders_section_heading">
        <span class="section_spark">✦</span>
        <h2 class="section_title">My Cart</h2>
        <span class="section_line"></span>
    </div>

    <div class="cart_section">
        <div class="cart_content" id="cartContent">
            <p class="orders_placeholder">Loading your cart...</p>
        </div>
    </div>

    {{-- MY ORDERS SECTION --}}
    <div class="orders_section_heading">
        <span class="section_spark">✦</span>
        <h2 class="section_title">My Orders</h2>
        <span class="section_line"></span>
    </div>

    <div class="orders_section">
        
        {{-- Current Orders --}}
        <div class="orders_category">
            <button class="orders_category_header" onclick="toggleOrdersCategory('current')">
                <div class="category_header_left">
                    <span class="category_icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </span>
                    <h3 class="category_title">Current Orders</h3>
                    <span class="category_count" id="currentOrdersCount">0</span>
                </div>
                <span class="category_toggle">
                    <svg class="toggle_icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="orders_category_content" id="currentOrdersContent">
                <div class="orders_placeholder">Loading current orders...</div>
            </div>
        </div>

        {{-- Completed Orders --}}
        <div class="orders_category">
            <button class="orders_category_header" onclick="toggleOrdersCategory('completed')">
                <div class="category_header_left">
                    <span class="category_icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <h3 class="category_title">Completed Orders</h3>
                    <span class="category_count" id="completedOrdersCount">0</span>
                </div>
                <span class="category_toggle">
                    <svg class="toggle_icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="orders_category_content" id="completedOrdersContent">
                <div class="orders_placeholder">Loading completed orders...</div>
            </div>
        </div>

    </div>

</div>

@endsection