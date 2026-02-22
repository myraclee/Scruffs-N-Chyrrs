@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/reset_password.css'])
@endsection

@section('content')
    <h1 class="header_reset">Reset Password</h1>
    <p class="reset_description">Don't worry! Enter your email below and we'll send you a code.</p>

    <form action="{{ route('enter-code') }}" method="GET">
        <div class="reset_container">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="reset_textbox" placeholder="example@email.com" required>
        </div>

        <div class="reset_button_container">
            <button type="submit" class="reset_button">Send Code</button>
        </div>
    </form>
@endsection