@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/reset_password.css'])
@endsection

@section('content')
    <h1 class="header_reset">Reset Password</h1>
    <p class="reset_description">Don't worry! Enter your email below and we'll send you a code.</p>

    <form action="{{ route('reset-password.send') }}" method="POST" novalidate>
        @csrf
        
        <div class="reset_container">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="reset_textbox" placeholder="example@email.com" value="{{ old('email') }}" required
                   @error('email') style="border: 1px solid red;" @enderror>
            
            @error('email')
                <span style="color: #d94848; font-family: Coolvetica; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="reset_button_container">
            <button type="submit" class="reset_button">Send Code</button>
        </div>
    </form>
@endsection