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
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    class="form_input @error('current_password') input_error @enderror"
                    required
                >
                @error('current_password')
                    <span class="error_message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="new_password">New Password</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form_input @error('new_password') input_error @enderror"
                    required
                >
                @error('new_password')
                    <span class="error_message">{{ $message }}</span>
                @enderror
                <p class="password_hint">Password must be at least 8 characters and contain at least uppercase, lowercase, number, and symbol.</p>
            </div>

            <div class="form_group">
                <label for="new_password_confirmation">Confirm New Password</label>
                <input 
                    type="password" 
                    id="new_password_confirmation" 
                    name="new_password_confirmation" 
                    class="form_input @error('new_password_confirmation') input_error @enderror"
                    required
                >
                @error('new_password_confirmation')
                    <span class="error_message">{{ $message }}</span>
                @enderror
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