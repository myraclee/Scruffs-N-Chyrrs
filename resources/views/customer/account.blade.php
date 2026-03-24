@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/account.css'])
@endsection

@section('content')
    <div class="account_container">
        <h1 class="account_header">My Account</h1>

        @if(session('success'))
            <div class="alert alert_success">
                {{ session('success') }}
            </div>
        @endif

        <div class="account_info_section">
            <div class="info_header">
                <h2>Account Information</h2>
                <a href="{{ route('edit-profile') }}" class="edit_link_btn">Edit</a>
            </div>
            
            <div class="info_grid">
                <div class="info_item">
                    <label>First Name</label>
                    <p>{{ Auth::user()->first_name }}</p>
                </div>
                <div class="info_item">
                    <label>Last Name</label>
                    <p>{{ Auth::user()->last_name }}</p>
                </div>
                <div class="info_item">
                    <label>Email</label>
                    <p>{{ Auth::user()->email }}</p>
                </div>
                <div class="info_item">
                    <label>Phone Number</label>
                    <p>{{ Auth::user()->contact_number ?? 'Not provided' }}</p>
                </div>
                
                <div class="info_item">
                    <label>Password</label>
                    <p style="letter-spacing: 2px; font-size: 18px;">••••••••••••</p>
                </div>
            </div> </div> <div class="account_action_buttons" style="display: flex; justify-content: center; margin-top: -20px; margin-bottom: 30px;">
            <a href="{{ route('change-password') }}" class="password_btn">Change Password</a>
        </div>        

        <div class="account_orders_section">
            
            {{-- ADMIN VIEW: Shows only if the user is the owner --}}
            @if(Auth::check() && Auth::user()->isOwner())
                <h2>Shop Management 👑</h2>
                
                <div class="admin_order_prompt">
                    <p class="admin_prompt_text">Time to bring some imagination to life! Head over to your dashboard to manage incoming customer orders.</p>
                    
            <a href="{{ route('owner.orders') }}" class="admin_dash_btn">Go to Order Management</a>

            {{-- CUSTOMER VIEW: Shows for regular users --}}
            @else
                @php
                    $orderCount = count(Auth::user()->orders ?? []);
                @endphp

                <h2>
                    My Orders 
                    @if ($orderCount === 0)
                        T_T
                    @elseif ($orderCount <= 2)
                        (^-^)
                    @else
                        >o<
                    @endif
                </h2>
                
                <div class="orders_list">
                    @forelse (Auth::user()->orders ?? [] as $order)
                        <div class="order_card">
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
                            <p class="no_orders_text">Oops! You haven't ordered anything yet~ ( ´ ▽ ` )</p>
                            <p class="no_orders_subtext">Time to bring your imagination to life!</p>
                        </div>
                    @endforelse
                </div> @endif
            
            @if(Auth::check() && Auth::user()->isOwner())
                </div> @endif
    </div>
@endsection
