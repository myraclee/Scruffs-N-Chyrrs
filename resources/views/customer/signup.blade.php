@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/signup.css','resources/css/customer/popups/tnc.css'])
@endsection

@section('page_js')
    @vite(['resources/js/tnc.js', 'resources/js/signup_validation.js'])
    <link rel="preconnect" href="https://challenges.cloudflare.com">
@endsection

@section('content')
    <h1 class="header_signup">Sign Up</h1>

    <form method="POST" action="{{ route('signup.store') }}">
        @csrf

        <div class="row">
            <div class="signup_firstname_container">
                <label for="first_name">First Name</label>
                <input class="signup_textbox" type="text" id="first_name" name="first_name" placeholder="ex. Juan" value="{{ old('first_name') }}" required>
                @error('first_name')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_lastname_container">
                <label for="last_name">Last Name</label>
                <input class="signup_textbox" type="text" id="last_name" name="last_name" placeholder="ex. De la Cruz" value="{{ old('last_name') }}" required>
                @error('last_name')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_email_container">
                <label for="email">Email</label>
                <input class="signup_textbox" type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required>
                @error('email')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_contact_container">
                <label for="contact_number">Contact Number</label>
                <input class="signup_textbox" type="tel" id="contact_number" name="contact_number" placeholder="+63 (912)-345-6789" value="{{ old('contact_number') }}" required>
                @error('contact_number')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_password_container">
                <label for="password">Password</label>
                <input class="signup_textbox" type="password" id="password" name="password" placeholder="Enter your password" required>
                @error('password')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_confirmpassword_container">
                <label for="password_confirmation">Confirm Password</label>
                <input class="signup_textbox" type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter your password" required>
            </div>
        </div>

        <div
        class="cf-turnstile"
        data-sitekey="{{ env('CLOUDFLARE_TURNSTILE_SITEKEY') }}"
        data-theme="light"
        data-size="normal"
        data-callback="onSuccess"
        ></div>

        <div class="tnc">
            <input type="checkbox" id="tnc_checkbox">
            <p>By signing up, you agree to Scruffs&Chyrrs' <a href="#" class="tnc_open" id="openterms">Terms and Conditions of Use</a></p>
        </div>

        <button class="signup_submit" type="submit" id="signup_submit">Submit</button>

        <div class="existingaccount">
            <p>Already have an account?
            <a href="{{ route('login') }}"> Log in!</a>
            </p>
        </div>
    </form>
    @include('customer.popups.tnc')
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
    ></script>
@endsection