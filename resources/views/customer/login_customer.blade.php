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

        .unlock_option_wrap {
            margin-top: 14px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(104, 44, 122, 0.06);
            border: 1px solid rgba(104, 44, 122, 0.25);
        }

        .unlock_option_text {
            margin: 0 0 10px;
            color: #5d276f;
            font-family: Coolvetica, sans-serif;
            letter-spacing: 0.2px;
        }

        .unlock_email_input {
            margin-bottom: 10px;
        }

        .unlock_option_button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 10px 12px;
            background: #682c7a;
            color: #fff;
            font-family: Coolvetica, sans-serif;
            letter-spacing: 0.3px;
            cursor: pointer;
        }

        .unlock_option_button:hover {
            background: #542463;
        }
    </style>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        {{-- Hidden input to pass lockout timestamp to JavaScript --}}
        @if(session('lockout_until'))
            <input type="hidden" id="lockout_timestamp" value="{{ session('lockout_until') }}">
        @endif

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

    @if(session('show_unlock_option'))
        <div class="unlock_option_wrap">
            <p class="unlock_option_text">Final option: verify your email to unlock your account.</p>
            <form method="POST" action="{{ route('account-unlock.send') }}" novalidate>
                @csrf
                <input
                    class="login_textbox unlock_email_input"
                    type="email"
                    id="unlock_email"
                    name="email"
                    placeholder="email@address.com"
                    value="{{ old('email') }}"
                    required
                >
                <button type="submit" class="unlock_option_button">Unlock via Email Verification</button>
            </form>
        </div>
    @endif

    @if(session('force_email_remediation'))
        <div id="email_remediation_overlay" class="email_remediation_overlay active" aria-hidden="false">
            <div class="email_remediation_box" role="dialog" aria-modal="true" aria-labelledby="email_remediation_title">
                <h2 id="email_remediation_title" class="email_remediation_title">Update Required Email</h2>
                <p class="email_remediation_message">To continue, enter a compliant email using @gmail.com or @ust.edu.ph.</p>

                <form method="POST" action="{{ route('login.remediate-email') }}" id="email_remediation_form" novalidate>
                    @csrf
                    <input
                        class="login_textbox"
                        type="email"
                        id="remediation_email"
                        name="email"
                        placeholder="email@address.com"
                        value="{{ old('email', session('email_remediation_value', '')) }}"
                        required
                        maxlength="254"
                    >
                    @error('email')
                        <span class="server_error">{{ $message }}</span>
                    @enderror

                    <div class="email_remediation_actions">
                        <button type="submit" class="login_button">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <input type="hidden" id="force_email_remediation_flag" value="1">
    @endif
    
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
    ></script>
@endsection