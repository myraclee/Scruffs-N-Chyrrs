@extends('layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/new_password.css', 'resources/js/customer/new_password.js'])
@endsection

@section('content')
    <div class="header_section">
        <span class="floating_star star_1">✦</span>
        <span class="floating_star star_2">✦</span>
        <span class="floating_star star_3">✦</span>
        <span class="floating_star star_4">✦</span>
        <span class="floating_star star_5">★</span>
        <span class="floating_star star_6">✦</span>
        
        <h1 class="header_new_password">Create New Password</h1>
        <p class="new_password_description">Enter and confirm your new password.</p>
    </div>

    @if($errors->any())
        <div class="error_container">
            @foreach($errors->all() as $error)
                <p style="margin: 5px 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('new-password.reset') }}" method="POST">
        @csrf
        
        <div class="password_container">
            <label for="new_password">New Password</label>
            <div class="input_wrapper">
                <input type="password" id="new_password" name="new_password" class="password_textbox" placeholder="Enter your new password" required>
                <button type="button" class="toggle_password" aria-label="Toggle password visibility">
                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>
            
            <ul class="password_requirements_list">
                <li id="req_length" class="invalid"><span>✗</span> At least 8 characters</li>
                <li id="req_upper" class="invalid"><span>✗</span> One uppercase letter</li>
                <li id="req_lower" class="invalid"><span>✗</span> One lowercase letter</li>
                <li id="req_number" class="invalid"><span>✗</span> One number</li>
                <li id="req_symbol" class="invalid"><span>✗</span> One special character</li>
            </ul>

            @error('new_password')
                <span class="field_error">{{ $message }}</span>
            @enderror
        </div>

        <div class="password_container">
            <label for="new_password_confirmation">Confirm Password</label>
            <div class="input_wrapper">
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="password_textbox" placeholder="Confirm your password" required>
                <button type="button" class="toggle_password" aria-label="Toggle password visibility">
                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>
            
            <span id="password_match_message" class="match_message"></span>

            @error('new_password_confirmation')
                <span class="field_error">{{ $message }}</span>
            @enderror
        </div>

        <div class="password_button_container">
            <button type="submit" class="password_button">Reset Password</button>
        </div>
    </form>

    <div id="success_modal" class="modal_overlay">
        <div class="modal_content">
            <div class="stars_container">
                <span class="star">✦</span>
                <span class="star_center">✦</span>
                <span class="star">✦</span>
            </div>
            <h2 class="modal_header">Awesome!</h2>
            <p class="modal_text">Your new password is good to go.</p>
            <button type="button" id="modal_continue_btn" class="password_button">Save & Login</button>
        </div>
    </div>
@endsection