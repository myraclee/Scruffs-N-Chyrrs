@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/view_orders.css'])
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
        <div class="cart_content">
            {{-- Empty state for cart - you can replace this with your own design --}}
            <div class="empty_state">
                <div class="empty_sparkles" aria-hidden="true">
                    <span>✦</span><span>✧</span><span>✦</span>
                </div>
                <div class="empty_icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <p class="empty_message">Empty as a blank page… ready for your magic touch!</p>
                <a href="{{ route('products') }}" class="browse_products_btn">
                    <span class="btn_sparkle">✦</span>
                    <span>Browse Products</span>
                </a>
            </div>
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
                    <span class="category_count">0</span>
                </div>
                <span class="category_toggle">
                    <svg class="toggle_icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="orders_category_content" id="currentOrdersContent">
                {{-- Your order design will go here --}}
                <div class="orders_placeholder">
                    <p>Your current orders will appear here</p>
                </div>
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
                    <span class="category_count">0</span>
                </div>
                <span class="category_toggle">
                    <svg class="toggle_icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="orders_category_content" id="completedOrdersContent">
                {{-- Your order design will go here --}}
                <div class="orders_placeholder">
                    <p>Your completed orders will appear here</p>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@section('page_scripts')
<script>
// Toggle orders category (expand/collapse)
function toggleOrdersCategory(category) {
    const content = document.getElementById(category + 'OrdersContent');
    const header = content.previousElementSibling;
    const isOpen = content.classList.contains('open');
    
    if (isOpen) {
        content.classList.remove('open');
        header.classList.remove('active');
    } else {
        content.classList.add('open');
        header.classList.add('active');
    }
}

// Initialize - open current orders by default
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('currentOrdersContent').classList.add('open');
    document.querySelector('[onclick="toggleOrdersCategory(\'current\')"]').classList.add('active');
});
</script>
@endsection