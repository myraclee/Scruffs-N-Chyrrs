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

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="login_container">
            <label for="email">Email</label>
            <input class="login_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required
                   @error('email') style="border: 1px solid red;" @enderror>
            
            @error('email')
                <span style="color: red; font-family: Coolvetica; font-size: 14px; display: block; margin-top: 4px;">{{ $message }}</span>
            @enderror
        </div>

        <div class="login_container">
            <label for="password">Password</label>
            <div class="password_wrapper">
                <input class="login_textbox" type="password" id="password" name="password" placeholder="Enter your password" required
                       @error('password') style="border: 1px solid red;" @enderror>
                <span class="toggle_password" id="toggle_login_password">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>
            </div>
            @error('password')
                <span style="color: red; font-family: Coolvetica; font-size: 14px; display: block; margin-top: 4px;">{{ $message }}</span>
            @enderror
            <p class="forgot_password"><a href="{{ route('reset-password') }}">Forgot your password?</a></p>
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.querySelector("form");
            if (form) {
                form.addEventListener("submit", function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault(); 
                        event.stopPropagation();
                    }
                    form.classList.add("was-validated");
                }, false);
            }
        });
    </script>
@endsection