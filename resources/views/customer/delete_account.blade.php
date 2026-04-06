@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/delete_account.css'])
@endsection

@section('page_js')
    @vite(['resources/js/delete_account.js'])
@endsection

@section('content')
    <div class="change_password_container">
        <span class="star star_tr">✦</span>
        <span class="star star_br">✦</span>
        <span class="star star_bl">✦</span>
        <span class="star star_tl">✦</span>
        <div class="change_section_heading">
        <span class="change_section_spark">✦</span>
        <h2 class="change_section_title">Delete Account</h2>
        <span class="change_section_line"></span>
    </div>

        <p class="delete_message">Deleting your account means saying goodbye to everything! Your account and order history will be permanently erased. This action can’t be undone, and there’s no way to bring your data back once it’s gone.</p>

        <form method="POST" action="{{ route('delete-account.destroy') }}" class="change_password_form" novalidate>
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
                <label for="new_password_confirmation">Confirm Current Password</label>
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

            @error('account_deletion')
                <span class="error_message account_delete_error">{{ $message }}</span>
            @enderror

            <div class="form_actions">
                <a href="{{ route('account') }}" class="action_btn cancel_btn">Cancel</a>
                <button type="submit" class="action_btn submit_btn">Delete Account</button>
            </div>
        </form>
    </div>

@endsection