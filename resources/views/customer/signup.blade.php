@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/signup.css','resources/css/customer/popups/tnc.css'])
@endsection

@section('page_js')
    @vite(['resources/js/tnc.js'])
@endsection

@section('content')
    <h1>Sign Up</h1>

    <form>
        <div class="row">
            <div class="signup_firstname_container">
                <label for="firstname_signup">First Name</label>
                <input type="text" name="firstname_signup" placeholder="ex. Juan" required>
            </div>

            <div class="signup_lastname_container">
                <label for="lastname_signup">Last Name</label>
                <input type="text" name="lastname_signup" placeholder="ex. De la Cruz" required>
            </div>
        </div>

        <div class="row">
            <div class="signup_email_container">
                <label for="email_signup">Email</label>
                <input type="email" name="email_signup" placeholder="email@address.com" required>
            </div>

            <div class="signup_contact_container">
                <label for="contact_signup">Contact Number</label>
                <input type="tel" name="contact_signup" placeholder="+63 (912)-345-6789" required>
            </div>
        </div>

        <div class="row">
            <div class="signup_password_container">
                <label for="password_signup">Password</label>
                <input type="password" name="password_signup" placeholder="Enter your password" required>
            </div>

            <div class="signup_confirmpassword_container">
                <label for="confirmpassword_signup">Confirm Password</label>
                <input type="password" name="confirmpassword_signup" placeholder="Re-enter your password" required>
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