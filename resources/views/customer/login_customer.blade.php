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

        @if(session('lockout_until'))
            <input type="hidden" id="lockout_timestamp" value="{{ session('lockout_until') }}">
        @endif

        @if(session('permanent_lock') || session('reset_required'))
            <input type="hidden" id="force_unlock_modal" value="1">
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

        <div class="cf-turnstile" data-sitekey="{{ env('CLOUDFLARE_TURNSTILE_SITEKEY') }}" data-theme="light" data-size="normal" data-callback="onSuccess"></div>

        <div class="login_button_container">
            <button type="submit" class="login_button">Login</button>
        </div>

        <p class="signup_redirect">Don't have an account? <a href="{{ route('signup') }}">Sign Up</a></p>
    </form>

    {{-- UNLOCK ACCOUNT MODAL --}}
    <div id="unlockAccountModal" class="unlock_modal_overlay" role="dialog" aria-modal="true">
        <div class="unlock_modal_container">
            <button class="unlock_modal_close" id="closeUnlockModal">&times;</button>
            <h2 class="unlock_modal_title">Account Locked</h2>
            <p class="unlock_modal_message">Your account has been locked due to multiple failed attempts. To unlock, verify your email address.</p>
            <form id="unlockForm" method="POST" action="{{ route('account-unlock.send') }}">
                @csrf
                <input type="email" name="email" id="unlock_email" class="unlock_modal_input" placeholder="Enter your email address" required>
                <button type="submit" class="unlock_modal_button">Send Verification Link</button>
            </form>
        </div>
    </div>

    @if(session('force_email_remediation'))
        <div id="email_remediation_overlay" class="email_remediation_overlay active" aria-hidden="false">
            <div class="email_remediation_box" role="dialog" aria-modal="true" aria-labelledby="email_remediation_title">
                <h2 id="email_remediation_title" class="email_remediation_title">Update Required Email</h2>
                <p class="email_remediation_message">To continue, enter a compliant email using @gmail.com or @ust.edu.ph.</p>

                <form method="POST" action="{{ route('login.remediate-email') }}" id="email_remediation_form" novalidate>
                    @csrf
                    <input class="login_textbox" type="email" id="remediation_email" name="email" placeholder="email@address.com" value="{{ old('email', session('email_remediation_value', '')) }}" required maxlength="254">
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

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endsection