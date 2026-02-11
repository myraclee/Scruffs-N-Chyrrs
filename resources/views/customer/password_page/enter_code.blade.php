@extends('customer.layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/password_page/enter_code.css'])
@endsection

@section('content')
<main class="form-container">
    <h1>Enter Code</h1>
    <p class="description">Check your inbox! We've sent a verification code to your email.</p>
    
    <form action="{{ route('new-password') }}" method="GET">
        <div class="code-container">
            <input type="text" 
                   class="code-input" 
                   maxlength="6" 
                   placeholder="A1B2C3" 
                   style="text-transform: uppercase;" 
                   required>
        </div>
        <button type="submit" class="confirm-btn">Verify</button>
        <p class="resend-text">Didn't get a code? <a href="#">Resend Code</a></p>
    </form>
</main>
@endsection