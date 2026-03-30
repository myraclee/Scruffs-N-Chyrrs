@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/change_password.css'])
@endsection

@section('page_js')
    @vite(['resources/js/change_password_validation.js'])
@endsection

@section('content')
    <div class="change_password_container">
        <span class="star star_tr">✦</span>
        <span class="star star_br">✦</span>
        <span class="star star_bl">✦</span>
        <span class="star star_tl">✦</span>
        <div class="change_section_heading">
        <span class="change_section_spark">✦</span>
        <h2 class="change_section_title">Change Password</h2>
        <span class="change_section_line"></span>
    </div>

        <form method="POST" action="{{ route('update-password') }}" class="change_password_form" novalidate>
            @csrf

            <div class="form_group">
                <label for="current_password">Current Password</label>
                <div class="password_wrapper">
                    <input type="password" id="current_password" name="current_password" class="form_input @error('current_password') input_error @enderror" required>
                    <span class="toggle_password" id="toggle_current_password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                @error('current_password')
                    <span class="error_message">{{ $message }}</span>
                @enderror
                <p class="forgot_password"><a href="{{ route('reset-password') }}">Forgot your password?</a></p>
            </div>

            <div class="form_group">
                <label for="new_password">New Password</label>
                <div class="password_wrapper">
                    <input type="password" id="new_password" name="new_password" class="form_input @error('new_password') input_error @enderror" required>
                    <span class="toggle_password" id="toggle_new_password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                @error('new_password')
                    <span class="error_message">{{ $message }}</span>
                @enderror
                <div id="password_requirements" style="display: none; margin-top: 10px; margin-left: 10px;">
                    <p class="hint_label" style="font-family: Coolvetica; font-size: 15px; color: #682c7a; margin-bottom: 5px; font-weight: bold;">Password must contain:</p>
                    <ul class="hints_list" style="font-family: Coolvetica, sans-serif; font-size: 14px; color: #682c7a; list-style-type: none; padding-left: 0; margin: 0; display: flex; flex-direction: column; gap: 4px; letter-spacing: 0.5px;">
                        <li id="req_length">✗ At least 8 characters</li>
                        <li id="req_upper">✗ An uppercase letter</li>
                        <li id="req_lower">✗ A lowercase letter</li>
                        <li id="req_number">✗ A number</li>
                        <li id="req_symbol">✗ A symbol (!@#$%^&*)</li>
                    </ul>
                </div>
            </div>

            <div class="form_group">
                <label for="new_password_confirmation">Confirm New Password</label>
                <div class="password_wrapper">
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form_input @error('new_password_confirmation') input_error @enderror" required>
                    <span class="toggle_password" id="toggle_confirm_password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                @error('new_password_confirmation')
                    <span class="error_message">{{ $message }}</span>
                @enderror
                <span id="match_message" style="font-family: Coolvetica; font-size: 13px; margin-top: 5px; display: block;"></span>
            </div>

            <div class="form_actions">
                <a href="{{ route('account') }}" class="action_btn cancel_btn">Cancel</a>
                <button type="submit" class="action_btn submit_btn">Change Password</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.querySelector(".change_password_form");
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