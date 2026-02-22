@extends('layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/enter_code.css'])
@endsection

@section('content')
    <h1 class="header_enter_code">Verify Code</h1>
    <p class="enter_code_description">Check your inbox! We've sent a verification code to your email.</p>
    
    <form action="{{ route('new-password') }}" method="GET">
        <div class="code_container">
            <label for="code">Verification Code</label>
            <input type="text" 
                   id="code"
                   name="code"
                   class="code_input" 
                   maxlength="6" 
                   placeholder="A1B2C3" 
                   style="text-transform: uppercase;" 
                   required>
        </div>
        <div class="code_button_container">
            <button type="submit" class="code_button">Verify</button>
        </div>
        <p class="resend_text">Didn't get a code? <a href="{{ route('reset-password') }}">Resend Code</a></p>
    </form>
@endsection