@extends('layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/new_password.css'])
@endsection

@section('content')
    <h1 class="header_new_password">Create New Password</h1>
    <p class="new_password_description">Enter and confirm your new password.</p>

    @if($errors->any())
        <div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; font-family: Coolvetica;">
            @foreach($errors->all() as $error)
                <p style="margin: 5px 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('new-password.reset') }}" method="POST">
        @csrf
        
        <div class="password_container">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="password_textbox" placeholder="Enter your new password" required>
            @error('new_password')
                <span style="color: #d94848; font-family: Coolvetica; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="password_container">
            <label for="new_password_confirmation">Confirm Password</label>
            <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="password_textbox" placeholder="Confirm your password" required>
            @error('new_password_confirmation')
                <span style="color: #d94848; font-family: Coolvetica; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="password_button_container">
            <button type="submit" class="password_button">Reset Password</button>
        </div>
    </form>
@endsection