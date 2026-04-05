@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/login.css'])
@endsection

@section('content')
    <style>
        .unlock_option_wrap {
            margin-top: 14px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(104, 44, 122, 0.06);
            border: 1px solid rgba(104, 44, 122, 0.25);
        }

        .unlock_option_text {
            margin: 0 0 10px;
            color: #5d276f;
            font-family: Coolvetica, sans-serif;
            letter-spacing: 0.2px;
        }

        .unlock_option_button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 10px 12px;
            background: #682c7a;
            color: #fff;
            font-family: Coolvetica, sans-serif;
            letter-spacing: 0.3px;
            cursor: pointer;
        }

        .unlock_option_button:hover {
            background: #542463;
        }
    </style>

    <h1 class="header_login">Owner Login</h1>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="login_container">
            <label for="email">Email</label>
            <input class="login_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required>
            @error('email')
                <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
            @enderror
        </div>

        <div class="login_container">
            <label for="password">Password</label>
            <input class="login_textbox" type="password" id="password" name="password" placeholder="Enter your password" required>
            @error('password')
                <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
            @enderror
        </div>

        <div class="login_button_container">
            <button type="submit" class="login_button">Login</button>
        </div>

        <p class="forgot_password"><a href="{{ route('reset-password') }}">Forgot your password?</a></p>
    </form>

    @if(session('show_unlock_option'))
        <div class="unlock_option_wrap">
            <p class="unlock_option_text">Final option: verify your email to unlock your account.</p>
            <form method="POST" action="{{ route('account-unlock.send') }}">
                @csrf
                <div class="login_container">
                    <label for="unlock_email">Email</label>
                    <input class="login_textbox" type="email" id="unlock_email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required>
                </div>
                <button type="submit" class="unlock_option_button">Unlock via Email Verification</button>
            </form>
        </div>
    @endif
@endsection
