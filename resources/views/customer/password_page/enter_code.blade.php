@extends('layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/enter_code.css'])
@endsection

@section('content')
    <style>
        /* --- UI FIXES FOR CONSISTENCY --- */
        .code_input {
            border: 2px solid #6b3282 !important; 
            outline: none;
            transition: all 0.2s ease;
        }

        /* Make the XXXXXX placeholder match the show password purple */
        .code_input::placeholder {
            color: #682c7a;
            opacity: 0.6; /* Slight fade so it looks like a placeholder */
        }

        /* Fallbacks for older browsers */
        .code_input::-webkit-input-placeholder { color: #682c7a; opacity: 0.6; }
        .code_input::-moz-placeholder { color: #682c7a; opacity: 0.6; }
        .code_input:-ms-input-placeholder { color: #682c7a; opacity: 0.6; }

        .code_container {
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
            text-align: center;
            width: 100%;
        }
    </style>

    <h1 class="header_enter_code">Verify Code</h1>
    <p class="enter_code_description">Check your inbox! We've sent a verification code to your email.</p>
    
    @if(session('success'))
        <div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724; font-family: Coolvetica;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any() && !$errors->has('code'))
        <div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; font-family: Coolvetica;">
            @foreach($errors->all() as $error)
                <p style="margin: 5px 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif
    
    <form action="{{ route('enter-code.verify') }}" method="POST" id="enter_code_form" data-error="{{ $errors->first('code') }}" novalidate>
        @csrf
        
        <div class="code_container">
            <label for="code" style="font-family: 'Coolvetica', sans-serif; color: #6b3282; margin-bottom: 8px; font-size: 20px; font-weight: 100; text-align: center;">Verification Code</label>
            <input type="text" 
                   id="code"
                   name="code"
                   class="code_input" 
                   maxlength="6" 
                   placeholder="XXXXXX" 
                   style="text-transform: uppercase; text-align: center;" 
                   value="{{ old('code') }}"
                   required>
                   
            <span class="validation_error" id="js_code_error" style="display: none;"></span>

            @error('code')
                <span class="server_error">{{ $message }}</span>
            @enderror
        </div>

        <div class="code_button_container">
            <button type="submit" class="code_button">Verify</button>
        </div>

        <p class="resend_text">Didn't get a code? <a href="{{ route('reset-password') }}">Request New Code</a></p>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById("enter_code_form");
            const codeInput = document.getElementById("code");
            const jsError = document.getElementById("js_code_error");

            function showFieldError(message) {
                codeInput.style.setProperty("border", "2px solid #d93025", "important");
                codeInput.style.setProperty("box-shadow", "0 0 8px rgba(217, 48, 37, 0.4)", "important");
                
                jsError.textContent = message;
                jsError.style.display = "block";

                const serverError = document.querySelector(".server_error");
                if (serverError) serverError.style.display = "none";
            }

            function clearFieldError() {
                codeInput.style.setProperty("border", "2px solid #6b3282", "important");
                codeInput.style.setProperty("box-shadow", "0 0 10px 1px rgba(0, 0, 0, 0.12)", "important");
                jsError.style.display = "none";
            }

            function handleCodeValidation() {
                const val = codeInput.value.trim();
                if (val === "") {
                    showFieldError("Verification code is required.");
                    return false;
                } else if (val.length < 6) {
                    showFieldError("Please enter the full 6-digit code.");
                    return false;
                } else {
                    clearFieldError();
                    return true;
                }
            }

            if (codeInput) {
                codeInput.addEventListener("input", handleCodeValidation);
                
                const serverErrorMsg = form.getAttribute("data-error");
                if (serverErrorMsg && serverErrorMsg.trim() !== "") {
                    showFieldError(serverErrorMsg);
                }
            }

            if (form) {
                form.addEventListener("submit", (e) => {
                    if (!handleCodeValidation()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                });
            }
        });
    </script>
@endsection