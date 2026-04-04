@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/login.css'])
@endsection

@section('page_js')
@vite(['resources/js/customer/pages/login.js'])
@endsection

@section('preconnect')
    <link rel="preconnect" href="https://challenges.cloudflare.com">
@endsection

@section('content')
    <h1 class="header_login">Login</h1>

    <style>
        /* --- UI FIXES APPLIED --- */
        .login_textbox {
            border: 2px solid #682c7a !important;
            outline: none;
            transition: all 0.2s ease;
        }

        .toggle_password svg {
            stroke: #682c7a !important;
        }

        .login_container {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .server_error {
            color: #d93025; 
            font-family: Coolvetica, sans-serif;
            font-size: 14px;
            margin-top: 6px;
            display: block;
        }
    </style>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="login_container">
            <label for="email">Email</label>
            <input class="login_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required maxlength="254" 
                   @error('email') style="border: 2px solid #d93025 !important;" @enderror>
            
            @error('email')
                <span class="server_error">{{ $message }}</span>
            @enderror
        </div>

        <div class="login_container" style="margin-top: 15px;">
            <label for="password">Password</label>
            <div class="password_wrapper">
                {{-- Force password border red if ANY auth error happens --}}
                <input class="login_textbox" type="password" id="password" name="password" placeholder="Enter your password" required
                       @if($errors->has('email') || $errors->has('password')) style="border: 2px solid #d93025 !important;" @endif>
                <span class="toggle_password" id="toggle_login_password">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>
            </div>
            
            @error('password')
                <span class="server_error">{{ $message }}</span>
            @enderror
            
            {{-- Professional error message for incorrect credentials --}}
            @if($errors->has('email') && !$errors->has('password'))
                <span class="server_error">Invalid email or password.</span>
            @endif
            
            <p class="forgot_password" style="margin-top: 10px;"><a href="{{ route('reset-password') }}">Forgot your password?</a></p>
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