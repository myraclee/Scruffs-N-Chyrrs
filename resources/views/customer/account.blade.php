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
            <h2>Account Information</h2>
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
            </div>
        </div>
</div>

        <div class="account_orders_section">
            <h2>My Orders</h2>
            
            <div class="orders_list">
                <div class="order_card">
                    <div class="order_header">
                        <span class="order_id">Order #10042</span>
                        <span class="order_date">March 15, 2026</span>
                    </div>
                    <div class="order_details">
                        <p><strong>Items:</strong> 50x Glossy Stickers, 20x Button Pins</p>
                        <p><strong>Total:</strong> Php 1,250.00</p>
                    </div>
                    <div class="order_status status_pending">Pending</div>
                </div>

                <div class="order_card">
                    <div class="order_header">
                        <span class="order_id">Order #10038</span>
                        <span class="order_date">March 02, 2026</span>
                    </div>
                    <div class="order_details">
                        <p><strong>Items:</strong> 100x Matte Business Cards</p>
                        <p><strong>Total:</strong> Php 850.00</p>
                    </div>
                    <div class="order_status status_completed">Completed</div>
                </div>
                
                </div>
        </div>
        
        <div class="account_actions">
            <a href="{{ route('edit-profile') }}" class="action_btn edit_btn">Edit Profile</a>
            <a href="{{ route('change-password') }}" class="action_btn change_password_btn">Change Password</a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="action_btn logout_btn">Logout</button>
            </form>
        </div>
    </div>
@endsection
