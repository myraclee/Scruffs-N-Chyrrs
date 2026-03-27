@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/edit_profile.css'])
@endsection

@section('page_js')
    @vite(['resources/js/edit_profile_validation.js'])
@endsection

@section('content')
    <div class="edit_profile_container">
        <span class="star star_tr">✦</span>
        <span class="star star_br">✦</span>
        <span class="star star_bl">✦</span>
        <span class="star star_tl">✦</span>
        <div class="edit_section_heading">
        <span class="edit_section_spark">✦</span>
        <h2 class="edit_section_title">Change Password</h2>
        <span class="edit_section_line"></span>
    </div>

        @if($errors->any())
            <div class="alert alert_error">
                <p><strong>Please fix the following errors:</strong></p>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('update-profile') }}" class="edit_profile_form">
            @csrf

            <div class="form_group">
                <label for="first_name">First Name</label>
                <input 
                    type="text" 
                    id="first_name" 
                    name="first_name" 
                    class="form_input @error('first_name') input_error @enderror"
                    value="{{ old('first_name', Auth::user()->first_name) }}"
                    required
                >
                @error('first_name')
                    <span class="error_message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="last_name">Last Name</label>
                <input 
                    type="text" 
                    id="last_name" 
                    name="last_name" 
                    class="form_input @error('last_name') input_error @enderror"
                    value="{{ old('last_name', Auth::user()->last_name) }}"
                    required
                >
                @error('last_name')
                    <span class="error_message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form_input @error('email') input_error @enderror"
                    value="{{ old('email', Auth::user()->email) }}"
                    required
                >
                @error('email')
                    <span class="error_message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="contact_number">Phone Number</label>
                <input 
                    type="text" 
                    id="contact_number" 
                    name="contact_number" 
                    class="form_input @error('contact_number') input_error @enderror"
                    value="{{ old('contact_number', Auth::user()->contact_number) }}"
                    required
                >
                @error('contact_number')
                    <span class="error_message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_actions">
                <a href="{{ route('account') }}" class="action_btn cancel_btn">Cancel</a>
                <button type="submit" class="action_btn submit_btn">Save</button>
            </div>
        </form>
    </div>
@endsection
