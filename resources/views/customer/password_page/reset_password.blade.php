@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/reset_password.css'])
@endsection

@section('content')
    <h1 class="header_reset">Reset Password</h1>
    <p class="reset_description">Don't worry! Enter your email below and we'll send you a code.</p>

    @if($errors->any())
        <div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; font-family: Coolvetica;">
            @foreach($errors->all() as $error)
                <p style="margin: 5px 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('reset-password.send') }}" method="POST">
        @csrf
        
        <div class="reset_container">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="reset_textbox" placeholder="example@email.com" value="{{ old('email') }}" required>
            @error('email')
                <span style="color: #d94848; font-family: Coolvetica; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="reset_button_container">
            <button type="submit" class="reset_button">Send Code</button>
        </div>
    </form>
@endsection