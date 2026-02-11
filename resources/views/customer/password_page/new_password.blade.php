@extends('customer.layouts.customer_layout')

@section('page_css')
    @vite(['resources/css/customer/password_page/new_password.css'])
@endsection

@section('content')
<main class="form-container">
    <h1>New Password</h1>
    
    <form action="{{ route('login') }}" method="GET">
        <div class="input-group">
            <label for="new-password">Enter new password</label>
            <input type="password" id="new-password" name="new-password">
        </div>

        <div class="input-group">
            <label for="re-password">Re-enter new password</label>
            <input type="password" id="re-password" name="re-password">
        </div>

        <button type="submit" class="confirm-btn">Confirm</button>
    </form>
</main>
@endsection