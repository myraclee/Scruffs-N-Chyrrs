@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/account.css'])
@endsection

@section('content')
 
<div class="account_container">
 
    {{-- ===== WELCOME HEADER ===== --}}
    <div class="acct_welcome">
        <span class="acct_welcome_spark_big">✦</span>
        <div class="acct_welcome_text">
            <p class="acct_welcome_sub">Welcome to Scruffs&amp;Chyrrs,</p>
            <h1 class="account_header">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h1>
        </div>
        <span class="acct_welcome_spark_small">✦</span>
    </div>
 
    {{-- ===== FLASH MESSAGES ===== --}}
    @if(session('success'))
        <div class="alert alert_success">
            <span class="alert_icon">✓</span>
            {{ session('success') }}
        </div>
    @endif
 
    {{-- ===== SECTION HEADER: MY ACCOUNT ===== --}}
    <div class="acct_section_heading">
        <span class="acct_section_spark">✦</span>
        <h2 class="acct_section_title">My Account</h2>
        <span class="acct_section_line"></span>
    </div>
 
    {{-- ===== ACCOUNT INFO ===== --}}
    <div class="account_info_section">
        <div class="info_header">
            <h3>Account Information</h3>
            <a href="{{ route('edit-profile') }}" class="edit_link_btn">Edit</a>
        </div>
 
        <div class="info_grid">
            <div class="info_item" style="--delay: 0.05s">
                <label>First Name</label>
                <p>{{ Auth::user()->first_name }}</p>
            </div>
            <div class="info_item" style="--delay: 0.10s">
                <label>Last Name</label>
                <p>{{ Auth::user()->last_name }}</p>
            </div>
            <div class="info_item" style="--delay: 0.15s">
                <label>Email</label>
                <p>{{ Auth::user()->email }}</p>
            </div>
            <div class="info_item" style="--delay: 0.20s">
                <label>Phone Number</label>
                <p>{{ Auth::user()->contact_number ?? 'Not provided' }}</p>
            </div>
            <div class="info_item info_item--password" style="--delay: 0.25s">
                <label>Password</label>
                <p class="info_password_dots">Password Set</p>
            </div>
        </div>
 
        <div class="info_section_footer">
            <a href="{{ route('change-password') }}" class="password_btn">
                <span>Change Password</span>
            </a>
        </div>
    </div>
 
    {{-- ===== SECTION HEADER: MY ORDERS ===== --}}
    <div class="acct_section_heading">
        <span class="acct_section_spark">✦</span>
        <h2 class="acct_section_title">My Orders</h2>
        <span class="acct_section_line"></span>
    </div>
 
    {{-- ===== ORDERS / ADMIN SECTION ===== --}}
    <div class="account_orders_section">
 
        @if(Auth::check() && Auth::user()->isOwner())
 
            {{-- ADMIN VIEW --}}
            <div class="orders_section_header">
                <h3>Shop Management <span>♕</span></h3>
            </div>
 
            <div class="admin_order_prompt">
                <div class="admin_prompt_sparkle" aria-hidden="true">✦</div>
                <p class="admin_prompt_text">Time to bring some imagination to life! Head over to your dashboard to manage incoming customer orders.</p>
                <a href="{{ route('owner.orders') }}" class="admin_dash_btn">
                    Go to Order Management
                    <span class="admin_btn_arrow">→</span>
                </a>
            </div>
 
        @else
 
            {{-- CUSTOMER VIEW --}}
            @php
                $orderCount = count(Auth::user()->orders ?? []);
            @endphp
 
            @if($orderCount > 0)
                <p class="orders_count_label">
                    You have <strong>{{ $orderCount }}</strong> {{ $orderCount === 1 ? 'order' : 'orders' }}
                    @if ($orderCount <= 2)
                        &nbsp;(^-^)
                    @else
                        &nbsp;&gt;o&lt;
                    @endif
                </p>
            @endif
 
            <div class="orders_list">
                @forelse (Auth::user()->orders ?? [] as $order)
                    <div class="order_card">
                        <div class="order_card_accent"></div>
                        <div class="order_header">
                            <span class="order_id">Order #{{ $order->id ?? '0000' }}</span>
                            <span class="order_date">{{ $order->created_at ? $order->created_at->format('F d, Y') : 'Date Pending' }}</span>
                        </div>
                        <div class="order_details">
                            <p><strong>Items:</strong> {{ $order->items_summary ?? 'Your lovely items' }}</p>
                            <p><strong>Total:</strong> Php {{ number_format($order->total_amount ?? 0, 2) }}</p>
                        </div>
                        <div class="order_status status_{{ strtolower($order->status ?? 'pending') }}">
                            {{ ucfirst($order->status ?? 'Pending') }}
                        </div>
                    </div>
                @empty
                    <div class="empty_orders">
                        <div class="empty_orders_sparkles" aria-hidden="true">
                            <span>✦</span><span>✦</span><span>✦</span>
                        </div>
                        <p class="no_orders_text">Oops! You haven't ordered anything yet~ ( ´ ▽ ` )</p>
                        <p class="no_orders_subtext">Time to bring your imagination to life!</p>
                    </div>
                @endforelse
            </div>
 
        @endif
 
    </div>
 
</div>
@endsection
