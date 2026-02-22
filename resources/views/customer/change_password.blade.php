@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/change_password.css'])
@endsection

@section('page_js')
    @vite(['resources/js/change_password_validation.js'])
@endsection

@section('content')
    <div class="change_password_container">
        <h1 class="change_password_header">Change Password</h1>

        @if($errors->any())
            <div class="alert alert_error">
                <p><strong>Please fix the following errors:</strong></p>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('update-password') }}" class="change_password_form">
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
                <button type="submit" class="action_btn submit_btn">Change Password</button>
                <a href="{{ route('account') }}" class="action_btn cancel_btn">Cancel</a>
            </div>
        </form>
    </div>
@endsection
