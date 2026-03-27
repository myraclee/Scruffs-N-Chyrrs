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

    <style>
        .client_error, .server_error {
            color: #d93025; 
            font-family: Coolvetica, sans-serif;
            font-size: 14px;
            margin-top: 6px;
            /* CHANGED from 10px to 0 to align with labels as requested */
            margin-left: 0;
            display: block;
        }

        .client_error {
            display: none;
        }
        
        #custom_signup_form.was-validated .signup_textbox:invalid {
            border: 2px solid #d93025 !important;
            box-shadow: 0 0 5px rgba(217, 48, 37, 0.3) !important;
        }
        
        #custom_signup_form.was-validated .signup_textbox:invalid ~ .client_error {
            display: block !important;
        }

        #custom_signup_form.was-validated input[type="checkbox"]:invalid ~ .client_error {
            display: block !important;
        }
    </style>

    <form method="POST" action="{{ route('signup.store') }}" id="custom_signup_form" novalidate>
        @csrf

        <div class="row">
            <div class="signup_firstname_container">
                <label for="first_name">First Name</label>
                <input class="signup_textbox" type="text" id="first_name" name="first_name" placeholder="ex. Juan" value="{{ old('first_name') }}" required maxlength="50"
                       @error('first_name') style="border: 2px solid #d93025;" @enderror>
                <span class="client_error">Please enter your first name.</span>
                @error('first_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_lastname_container">
                <label for="last_name">Last Name</label>
                <input class="signup_textbox" type="text" id="last_name" name="last_name" placeholder="ex. De la Cruz" value="{{ old('last_name') }}" required maxlength="50"
                       @error('last_name') style="border: 2px solid #d93025;" @enderror>
                <span class="client_error">Please enter your last name.</span>
                @error('last_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_email_container">
                <label for="email">Email</label>
                <input class="signup_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required maxlength="254"
                       @error('email') style="border: 2px solid #d93025;" @enderror>
                <span class="client_error">Please enter a valid email.</span>
                @error('email')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_contact_container">
                <label for="contact_number">Contact Number</label>
                
                <div style="position: relative; width: max-content;">
                    <span style="position: absolute; left: 20px; top: 16px; font-family: Coolvetica, sans-serif; font-size: 15px; color: #333; pointer-events: none;">+63</span>
                    
                    <input class="signup_textbox" type="tel" id="contact_number" name="contact_number" placeholder="9123456789" value="{{ old('contact_number') }}" required maxlength="10" pattern="^9[0-9]{9}$"
                           style="width: 320px; padding-left: 50px; @error('contact_number') border: 2px solid #d93025; @enderror">
                           
                    <span class="client_error">Please enter a 10-digit number starting with 9.</span>
                    @error('contact_number')
                        <span class="server_error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="signup_password_container">
                <label for="password">Password</label>
                <input class="signup_textbox" type="password" id="password" name="password" placeholder="Enter your password" required minlength="8" maxlength="128"
                       @error('password') style="border: 2px solid #d93025;" @enderror>
                <span class="client_error">Password must be between 8 and 128 characters.</span>
                @error('password')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_confirmpassword_container">
                <label for="password_confirmation">Confirm Password</label>
                <input class="signup_textbox" type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter your password" required minlength="8" maxlength="128"
                       @error('password_confirmation') style="border: 2px solid #d93025;" @enderror>
                <span class="client_error">Please confirm your password.</span>
            </div>
        </div>

        <div
        class="cf-turnstile"
        data-sitekey="{{ env('CLOUDFLARE_TURNSTILE_SITEKEY') }}"
        data-theme="light"
        data-size="normal"
        data-callback="onSuccess"
        ></div>

        <div class="tnc">
            <input type="checkbox" id="tnc_checkbox" required>
            <p>By signing up, you agree to Scruffs&Chyrrs' <a href="#" class="tnc_open" id="openterms">Terms and Conditions of Use</a></p>
            <span class="client_error" style="margin-left: 0;">You must agree to the terms.</span>
        </div>

        <button class="signup_submit" type="submit" id="signup_submit">Submit</button>

        <div class="existingaccount">
            <p>Already have an account?
            <a href="{{ route('login') }}"> Log in!</a>
            </p>
        </div>
    </form>
    @include('customer.popups.tnc')
    
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
    ></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById("custom_signup_form");
            const submitBtn = document.getElementById("signup_submit");

            if (form) {
                if (submitBtn) {
                    submitBtn.addEventListener("click", function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault(); 
                            form.classList.add("was-validated"); 
                        }
                    });
                }

                form.addEventListener("submit", function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault(); 
                        event.stopImmediatePropagation(); 
                        form.classList.add("was-validated");
                    }
                });
            }
        });
    </script>
@endsection