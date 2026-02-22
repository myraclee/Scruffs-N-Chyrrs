@extends('layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/new_password.css'])
@endsection

@section('content')
    <h1 class="header_new_password">Create New Password</h1>
    <p class="new_password_description">Enter and confirm your new password.</p>

    <form action="{{ route('login') }}" method="GET">
        <div class="password_container">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="password_textbox" placeholder="Enter your new password" required>
        </div>

        <div class="password_container">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="password_textbox" placeholder="Confirm your password" required>
        </div>

        <div class="password_button_container">
            <button type="submit" class="password_button">Confirm Password</button>
        </div>
    </form>
@endsection