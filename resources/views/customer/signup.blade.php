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
        /* --- UI FIXES APPLIED --- */
        .signup_textbox {
            border: 2px solid #682c7a !important;
            outline: none;
            transition: all 0.2s ease;
        }

        .toggle_password svg {
            stroke: #682c7a !important;
        }
        /* ------------------------ */

        .input_group {
            position: relative;
        }

        .client_error, .server_error {
            color: #d93025; 
            font-family: Coolvetica, sans-serif;
            font-size: 14px;
            margin-top: 6px;
            margin-left: 0;
            display: block;
        }

        .client_error {
            display: none;
        }

        /* Error States */
        .input_error_state,
        #custom_signup_form.was-validated .signup_textbox:invalid {
            border: 2px solid #d93025 !important;
            box-shadow: 0 0 5px rgba(217, 48, 37, 0.3) !important;
        }
        
        /* Force display block when JS adds this class */
        .show_error,
        #custom_signup_form.was-validated .signup_textbox:invalid ~ .client_error {
            display: block !important;
        }

        #custom_signup_form.was-validated input[type="checkbox"]:invalid ~ .client_error {
            display: block !important;
        }

        #custom_signup_form.was-validated .server_error {
            display: none !important;
        }

        .validation_error + .client_error {
            display: none !important;
        }
    </style>

    <form method="POST" action="{{ route('signup.store') }}" id="custom_signup_form" novalidate>
        @csrf

        <div class="row">
            <div class="signup_firstname_container input_group">
                <label for="first_name">First Name</label>
                <input class="signup_textbox" type="text" id="first_name" name="first_name" placeholder="ex. Juan" value="{{ old('first_name') }}" required maxlength="50" pattern="^[A-Za-z\s]*[A-Za-z][A-Za-z\s]*$"
                       @error('first_name') style="border: 2px solid #d93025 !important;" @enderror>
                <span class="client_error">Please enter a valid first name (letters only, cannot be blank).</span>
                @error('first_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_lastname_container input_group">
                <label for="last_name">Last Name</label>
                <input class="signup_textbox" type="text" id="last_name" name="last_name" placeholder="ex. De la Cruz" value="{{ old('last_name') }}" required maxlength="50" pattern="^[A-Za-z\s]*[A-Za-z][A-Za-z\s]*$"
                       @error('last_name') style="border: 2px solid #d93025 !important;" @enderror>
                <span class="client_error">Please enter a valid last name (letters only, cannot be blank).</span>
                @error('last_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_email_container input_group">
                <label for="email">Email</label>
                <input class="signup_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required maxlength="254" 
                       pattern="^[a-zA-Z0-9._%+\-]{2,}@[a-zA-Z0-9.\-]{2,}\.[a-zA-Z]{2,}$"
                       @error('email') style="border: 2px solid #d93025 !important;" @enderror>
                <span class="client_error email_custom_error">Enter a valid email. Each part must be at least 2 characters.</span>
                @error('email')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_contact_container input_group">
                <label for="contact_number">Contact Number</label>
                
                <div style="position: relative; display: block;">
                    <span style="position: absolute; left: 20px; top: 25px; transform: translateY(-50%); font-family: Coolvetica, sans-serif; font-size: 15px; color: #333; pointer-events: none;">+63</span>
                    
                          <input class="signup_textbox @error('contact_number') input_error_state @enderror" type="tel" id="contact_number" name="contact_number" placeholder="9123456789" value="{{ str_replace('+63', '', old('contact_number')) }}" required maxlength="10" pattern="^9[0-9]{9}$" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                              style="text-indent: 32px; margin: 0;">
                           
                    <span class="client_error">Please enter a 10-digit number starting with 9.</span>
                    @error('contact_number')
                        <span class="server_error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="signup_password_container input_group">
                <label for="password">Password</label>
                <div class="password_wrapper">
                    <input class="signup_textbox" type="password" id="password" name="password" placeholder="Enter your password" required minlength="8" maxlength="128"
                           @error('password') style="border: 2px solid #d93025 !important;" @enderror>
                    <span class="toggle_password" id="toggle_signup_password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                <span class="client_error">Password must be between 8 and 128 characters.</span>
                @error('password')
                    <span class="server_error">{{ $message }}</span>
                @enderror
                
                <div id="password_requirements" style="display: none; margin-top: 10px; margin-left: 10px;">
                    <p class="hint_label" style="font-family: Coolvetica; font-size: 14px; color: #682c7a; margin-bottom: 5px;">Password must contain:</p>
                    <ul class="hints_list" style="font-family: sans-serif; font-size: 12px; color: #666; list-style-type: none; padding-left: 0; margin: 0; display: flex; flex-direction: column; gap: 4px;">
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
                    <input class="signup_textbox" type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter your password" required minlength="8" maxlength="128"
                           @error('password_confirmation') style="border: 2px solid #d93025 !important;" @enderror>
                    <span class="toggle_password" id="toggle_signup_confirm">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                <span class="client_error">Please confirm your password.</span>
                <span id="match_message" style="font-family: Coolvetica; font-size: 14px; margin-top: 6px; margin-left: 10px; display: block;"></span>
            </div>
        </div>

        <div
        class="cf-turnstile"
        data-sitekey="{{ env('CLOUDFLARE_TURNSTILE_SITEKEY') }}"
        data-theme="light"
        data-size="normal"
        data-callback="onSuccess"
        ></div>

        <div class="tnc input_group" style="display: grid; grid-template-columns: auto auto; justify-content: center; align-items: center; column-gap: 7px; row-gap: 5px;">
            <input type="checkbox" id="tnc_checkbox" required>
            <p style="margin: 0;">By signing up, you agree to Scruffs&Chyrrs' <a href="#" class="tnc_open" id="openterms">Terms and Conditions of Use</a></p>
            <span class="client_error" style="grid-column: 1 / -1; position: static; text-align: center; margin: 0;">You must agree to the terms.</span>
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
            const emailInput = document.getElementById("email");
            const emailError = document.querySelector(".email_custom_error");

            // Explicitly force error text to show based on custom validation state
            const handleEmailValidation = () => {
                const regex = /^[a-zA-Z0-9._%+\-]{2,}@[a-zA-Z0-9.\-]{2,}\.[a-zA-Z]{2,}$/;
                if (!regex.test(emailInput.value) && emailInput.value.length > 0) {
                    emailInput.setCustomValidity("Invalid email format.");
                    if(form.classList.contains('was-validated')) {
                        emailInput.classList.add("input_error_state");
                        emailError.classList.add("show_error");
                    }
                } else {
                    emailInput.setCustomValidity("");
                    emailInput.classList.remove("input_error_state");
                    emailError.classList.remove("show_error");
                }
            };

            if (emailInput) {
                emailInput.addEventListener("input", handleEmailValidation);
                emailInput.addEventListener("blur", handleEmailValidation);
            }

            if (form) {
                if (submitBtn) {
                    submitBtn.addEventListener("click", function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault(); 
                            form.classList.add("was-validated"); 
                            handleEmailValidation(); // Ensure UI reflects state on click
                        }
                    });
                }

                form.addEventListener("submit", function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault(); 
                        event.stopImmediatePropagation(); 
                        form.classList.add("was-validated");
                        handleEmailValidation(); // Ensure UI reflects state on submit
                    }
                });
            }
        });
    </script>
@endsection