@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/reset_password.css'])
@endsection

@section('content')
    <style>
        /* --- UI FIXES FOR CONSISTENCY --- */
        .reset_textbox {
            /* We force the purple border here so it's visible by default like Login */
            border: 2px solid #6b3282 !important; 
            outline: none;
            transition: all 0.2s ease;
        }

        .reset_container {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .validation_error, .server_error {
            color: #d93025; 
            font-family: "Coolvetica", sans-serif;
            font-size: 14px;
            margin-top: 8px;
            display: block;
            text-align: left;
            width: 100%;
        }
    </style>

    <h1 class="header_reset">Reset Password</h1>
    <p class="reset_description">Don't worry! Enter your email below and we'll send you a code.</p>

    {{-- Added data-error here to pass Laravel errors to JS cleanly --}}
    <form action="{{ route('reset-password.send') }}" method="POST" id="reset_password_form" data-error="{{ $errors->first('email') }}" novalidate>
        @csrf
        
        <div class="reset_container">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="reset_textbox" placeholder="example@email.com" value="{{ old('email') }}" required>
            
            {{-- This spans handles the real-time JS errors --}}
            <span class="validation_error" id="js_email_error" style="display: none;"></span>

            @error('email')
                <span class="server_error">{{ $message }}</span>
            @enderror
        </div>

        <div class="reset_button_container">
            <button type="submit" class="reset_button">Send Code</button>
        </div>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById("reset_password_form");
            const emailInput = document.getElementById("email");
            const jsError = document.getElementById("js_email_error");

            // Strict Regex: Min 2 chars for username, domain, and extension
            function validateEmail(email) {
                return /^[a-zA-Z0-9._%+\-]{2,}@[a-zA-Z0-9.\-]{2,}\.[a-zA-Z]{2,}$/.test(email);
            }

            function showFieldError(message) {
                // Force the red border and red glow
                emailInput.style.setProperty("border", "2px solid #d93025", "important");
                emailInput.style.setProperty("box-shadow", "0 0 8px rgba(217, 48, 37, 0.4)", "important");
                
                jsError.textContent = message;
                jsError.style.display = "block";

                // Hide Laravel's server error if it exists
                const serverError = document.querySelector(".server_error");
                if (serverError) serverError.style.display = "none";
            }

            function clearFieldError() {
                // Return to original purple and soft shadow
                emailInput.style.setProperty("border", "2px solid #6b3282", "important");
                emailInput.style.setProperty("box-shadow", "0 0 10px 1px rgba(0, 0, 0, 0.12)", "important");
                jsError.style.display = "none";
            }

            function handleEmailValidation() {
                const email = emailInput.value.trim();
                if (email === "") {
                    showFieldError("Email is required.");
                    return false;
                } else if (!email.includes("@")) {
                    showFieldError("Email must contain an @ symbol.");
                    return false;
                } else if (!validateEmail(email)) {
                    showFieldError("Enter a valid email. Each part must be at least 2 characters.");
                    return false;
                } else {
                    clearFieldError();
                    return true;
                }
            }

            if (emailInput) {
                emailInput.addEventListener("input", handleEmailValidation);
                
                // Read the error directly from the HTML form tag (NO MORE PHP IN JS!)
                const serverErrorMsg = form.getAttribute("data-error");
                if (serverErrorMsg && serverErrorMsg.trim() !== "") {
                    showFieldError(serverErrorMsg);
                }
            }

            if (form) {
                form.addEventListener("submit", (e) => {
                    if (!handleEmailValidation()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                });
            }
        });
    </script>
@endsection