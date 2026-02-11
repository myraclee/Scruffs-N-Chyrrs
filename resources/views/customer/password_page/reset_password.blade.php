@extends('customer.layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/password_page/reset_password.css'])
@endsection

@section('content')
<main class="form-container">
    <h1>Reset Password</h1>
    <p class="description">Don't worry! Enter your email below and we'll send you a code.</p>
    
    <form action="{{ route('enter-code') }}" method="GET">
        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" placeholder="example@email.com" required>
        </div>
        <button type="submit" class="confirm-btn">Send Code</button>
    </form>
</main>
@endsection