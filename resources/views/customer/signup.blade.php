@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/signup.css','resources/css/customer/popups/tnc.css'])
@endsection

@section('page_js')
    @vite(['resources/js/tnc.js'])
@endsection

@section('content')
    <h1>Sign Up</h1>

    <form method="POST" action="{{ route('signup.store') }}">
        @csrf

        <div class="row">
            <div class="signup_firstname_container">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" placeholder="ex. Juan" value="{{ old('first_name') }}" required>
                @error('first_name')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_lastname_container">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" placeholder="ex. De la Cruz" value="{{ old('last_name') }}" required>
                @error('last_name')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_email_container">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="email@address.com" value="{{ old('email') }}" required>
                @error('email')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_contact_container">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" placeholder="+63 (912)-345-6789" value="{{ old('contact_number') }}" required>
                @error('contact_number')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="signup_password_container">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                @error('password')
                    <span style="color: red; font-family: Coolvetica; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="signup_confirmpassword_container">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter your password" required>
            </div>
        </div>

        <div class="tnc">
            <p>Please view Scruffs&Chyrrs' <a href="#" class="tnc_open" id="openterms">Terms and Conditions of Use</a> to proceed with the sign up.</p>
        </div>

        <button type="submit">Submit</button>

        <div class="existingaccount">
            <p>Already have an account?
            <a href="{{ route('login') }}"> Log in!</a>
            </p>
        </div>
    </form>
    @include('customer.popups.tnc')
@endsection