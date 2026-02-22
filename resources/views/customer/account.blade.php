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
