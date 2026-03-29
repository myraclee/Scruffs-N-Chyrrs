@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/account.css'])
@endsection

@section('content')

{{-- Background Sparkles --}}
<div class="acct_sparkles_bg" aria-hidden="true">
    <span class="acct_spark s1">✦</span>
    <span class="acct_spark s2">✧</span>
    <span class="acct_spark s3">✦</span>
    <span class="acct_spark s4">✧</span>
    <span class="acct_spark s5">✦</span>
    <span class="acct_spark s6">✧</span>
    <span class="acct_spark s7">✦</span>
    <span class="acct_spark s8">✧</span>
    <span class="acct_spark s9">✦</span>
    <span class="acct_spark s10">✧</span>
    <span class="acct_spark s11">✦</span>
    <span class="acct_spark s12">✧</span>
</div>

<div class="account_container">

    {{-- Welcome Header --}}
    <div class="acct_welcome">
        <span class="acct_welcome_spark_big">✦</span>
        <div class="acct_welcome_text">
            <p class="acct_welcome_sub">Welcome to Scruffs&amp;Chyrrs,</p>
            <h1 class="account_header">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h1>
        </div>
        <span class="acct_welcome_spark_small">✦</span>
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

    {{-- Section Header: My Account --}}
    <div class="acct_section_heading">
        <span class="acct_section_spark">✦</span>
        <h2 class="acct_section_title">My Account</h2>
        <span class="acct_section_line"></span>
    </div>

    {{-- Account Info Section --}}
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


</div>

@endsection