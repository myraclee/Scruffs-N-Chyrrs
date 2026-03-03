@extends('layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/enter_code.css'])
@endsection

@section('content')
    <h1 class="header_enter_code">Verify Code</h1>
    <p class="enter_code_description">Check your inbox! We've sent a verification code to your email.</p>
    
    @if(session('success'))
        <div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724; font-family: Coolvetica;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; font-family: Coolvetica;">
            @foreach($errors->all() as $error)
                <p style="margin: 5px 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif
    
    <form action="{{ route('enter-code.verify') }}" method="POST">
        @csrf
        
        <div class="code_container">
            <label for="code">Verification Code</label>
            <input type="text" 
                   id="code"
                   name="code"
                   class="code_input" 
                   maxlength="6" 
                   placeholder="A1B2C3" 
                   style="text-transform: uppercase;" 
                   value="{{ old('code') }}"
                   required>
            @error('code')
                <span style="color: #d94848; font-family: Coolvetica; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="code_button_container">
            <button type="submit" class="code_button">Verify</button>
        </div>

        <p class="resend_text">Didn't get a code? <a href="{{ route('reset-password') }}">Request New Code</a></p>
    </form>
@endsection