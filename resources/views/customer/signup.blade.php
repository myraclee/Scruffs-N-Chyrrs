@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/signup.css'])
@endsection

@section('page_js')
@vite(['resources/js/app.js','resources/js/terms-modal.js'])
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
            <p>Please view Scruffs&Chyrrs' <a href="#" id="openterms">Terms and Conditions of Use</a> to proceed with the sign up.</p>
        </div>

        <button type="submit">Submit</button>

        <div class="existingaccount">
            <p>Already have an account?
            <a href="{{ route('login') }}"> Log in!</a>
            </p>
        </div>
    </form>

    <div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions of Use</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <p><strong>1. Acceptance of Terms</strong></p>
                <p>By using Scruffs&Chyrrs, you agree to these terms.</p>

                <p><strong>2. User Responsibility</strong></p>
                <p>You are responsible for your account usage.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>
@endsection