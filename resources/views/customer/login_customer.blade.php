@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/login.css'])
@endsection

@section('preconnect')
    <link rel="preconnect" href="https://challenges.cloudflare.com">
@endsection

@section('content')
    <h1 class="header_login">Login</h1>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="login_container">
            <label for="email">Email</label>
            <input class="login_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required>
            @error('email')
                <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
            @enderror
        </div>

        <div class="login_container">
            <label for="password">Password</label>
            <input class="login_textbox" type="password" id="password" name="password" placeholder="Enter your password" required>
            <p class="forgot_password"><a href="{{ route('reset-password') }}">Forgot your password?</a></p>
            @error('password')
                <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
            @enderror
        </div>

        <div
        class="cf-turnstile"
        data-sitekey="{{ env('CLOUDFLARE_TURNSTILE_SITEKEY') }}"
        data-theme="light"
        data-size="normal"
        data-callback="onSuccess"
        ></div>

        <div class="login_button_container">
            <button type="submit" class="login_button">Login</button>
        </div>

        <p class="signup_redirect">Don't have an account? <a href="{{ route('signup') }}">Sign Up</a></p>
    </form>
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
    ></script>
@endsection
