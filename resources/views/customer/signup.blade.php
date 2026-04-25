@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/signup.css','resources/css/customer/popups/tnc.css'])
@endsection

@section('page_js')
    @vite(['resources/js/tnc.js', 'resources/js/signup_validation.js'])
@endsection

@section('preconnect')
    <link rel="preconnect" href="https://challenges.cloudflare.com">
@endsection

@section('content')
    <h1 class="header_signup">Sign Up</h1>

    <form method="POST" action="{{ route('signup.store') }}" id="custom_signup_form" novalidate>
        @csrf

        <div class="row">
            <div class="signup_firstname_container input_group">
                <label for="first_name">First Name</label>
                <input class="signup_textbox" type="text" id="first_name" name="first_name" placeholder="ex. Juan"
                    value="{{ old('first_name') }}" required maxlength="50"
                    @error('first_name') style="border: 2px solid #d93025 !important;" @enderror>
                <span class="client_error"></span>
                @error('first_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_lastname_container input_group">
                <label for="last_name">Last Name</label>
                <input class="signup_textbox" type="text" id="last_name" name="last_name" placeholder="ex. De la Cruz"
                    value="{{ old('last_name') }}" required maxlength="50"
                    @error('last_name') style="border: 2px solid #d93025 !important;" @enderror>
                <span class="client_error"></span>
                @error('last_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_email_container input_group">
                <label for="email">Email</label>
                <input class="signup_textbox" type="email" id="email" name="email" placeholder="email@address.com"
                    value="{{ old('email') }}" required maxlength="254"
                    @error('email') style="border: 2px solid #d93025 !important;" @enderror>
                <span class="client_error"></span>
                @error('email')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_contact_container input_group">
                <label for="contact_number">Contact Number</label>
                <div class="contact_wrapper">
                    <span class="contact_prefix">+63</span>
                    <input class="signup_textbox" type="tel" id="contact_number" name="contact_number"
                        placeholder="9123456789" value="{{ str_replace('+63', '', old('contact_number')) }}" required
                        maxlength="10" @error('contact_number') style="border: 2px solid #d93025 !important;" @enderror>
                </div>
                <span class="client_error"></span>
                @error('contact_number')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_password_container input_group">
                <label for="password">Password</label>
                <div class="password_wrapper">
                    <input class="signup_textbox" type="password" id="password" name="password"
                        placeholder="Enter your password" required minlength="8" maxlength="128"
                        @error('password') style="border: 2px solid #d93025 !important;" @enderror>
                    <span class="toggle_password" id="toggle_signup_password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <span class="client_error"></span>
                @error('password')
                    <span class="server_error">{{ $message }}</span>
                @enderror

                <div id="password_requirements" style="display: none;">
                    <p class="hint_label">Password must contain:</p>
                    <ul class="hints_list">
                        <li id="req_length">✗ At least 8 characters</li>
                        <li id="req_upper">✗ An uppercase letter</li>
                        <li id="req_lower">✗ A lowercase letter</li>
                        <li id="req_number">✗ A number</li>
                        <li id="req_symbol">✗ A symbol (!@#$%^&*)</li>
                    </ul>
                </div>
            </div>

            <div class="signup_confirmpassword_container input_group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="password_wrapper">
                    <input class="signup_textbox" type="password" id="password_confirmation" name="password_confirmation"
                        placeholder="Re-enter your password" required minlength="8" maxlength="128"
                        @error('password_confirmation') style="border: 2px solid #d93025 !important;" @enderror>
                    <span class="toggle_password" id="toggle_signup_confirm">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <span class="client_error"></span>
                <span id="match_message"></span>
            </div>
        </div>

        <div class="cf-turnstile" data-sitekey="{{ env('CLOUDFLARE_TURNSTILE_SITEKEY') }}" data-theme="light"
            data-size="normal" data-callback="onSuccess">
        </div>

        <div class="tnc input_group">
            <input type="checkbox" id="tnc_checkbox" required>
            <p>By signing up, you agree to Scruffs&Chyrrs' <a href="#" class="tnc_open" id="openterms">Terms and
                    Conditions of Use</a></p>
            <span class="client_error">You must agree to the terms.</span>
        </div>

        <button class="signup_submit" type="submit" id="signup_submit">Submit</button>

        <div class="existingaccount">
            <p>Already have an account? <a href="{{ route('login') }}">Log in!</a></p>
        </div>
    </form>

    @include('customer.popups.tnc')

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endsection