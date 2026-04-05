@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/edit_profile.css'])
@endsection

@section('page_js')
    @vite(['resources/js/edit_profile_validation.js'])
@endsection

@section('content')
    <style>
        /* --- UI FIXES APPLIED --- */
        .form_input {
            border: 2px solid #682c7a !important;
            outline: none;
            transition: border 0.2s ease;
        }

        .form_group {
            position: relative;
        }

        .validation_error, .server_error {
            color: #d93025; 
            font-family: Coolvetica, sans-serif;
            font-size: 14px;
            margin-top: 6px;
            display: block;
        }
    </style>

    <div class="edit_profile_container">
        <span class="star star_tr">✦</span>
        <span class="star star_br">✦</span>
        <span class="star star_bl">✦</span>
        <span class="star star_tl">✦</span>
        <div class="edit_section_heading">
        <span class="edit_section_spark">✦</span>
        <h2 class="edit_section_title">Edit Profile</h2>
        <span class="edit_section_line"></span>
    </div>

        <form method="POST" action="{{ route('update-profile') }}" class="edit_profile_form" id="edit_profile_form" novalidate>
            @csrf

            <div class="form_group">
                <label for="first_name">First Name</label>
                <input 
                    type="text" 
                    id="first_name" 
                    name="first_name" 
                    class="form_input"
                    value="{{ old('first_name', Auth::user()->first_name) }}"
                    required
                    maxlength="50"
                    pattern="^[A-Za-z\s]*[A-Za-z][A-Za-z\s]*$"
                    @error('first_name') style="border: 2px solid #d93025 !important;" @enderror
                >
                @error('first_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="last_name">Last Name</label>
                <input 
                    type="text" 
                    id="last_name" 
                    name="last_name" 
                    class="form_input"
                    value="{{ old('last_name', Auth::user()->last_name) }}"
                    required
                    maxlength="50"
                    pattern="^[A-Za-z\s]*[A-Za-z][A-Za-z\s]*$"
                    @error('last_name') style="border: 2px solid #d93025 !important;" @enderror
                >
                @error('last_name')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form_input"
                    value="{{ old('email', Auth::user()->email) }}"
                    required
                    maxlength="254"
                    pattern="^[a-zA-Z0-9._%+\-]{2,}@[a-zA-Z0-9.\-]{2,}\.[a-zA-Z]{2,}$"
                    @error('email') style="border: 2px solid #d93025 !important;" @enderror
                >
                @error('email')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_group">
                <label for="contact_number">Phone Number</label>
                <div style="position: relative; display: block;">
                    <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-family: Coolvetica, sans-serif; font-size: 15px; color: #333; pointer-events: none;">+63</span>
                    <input 
                        type="tel" 
                        id="contact_number" 
                        name="contact_number" 
                        class="form_input"
                        value="{{ old('contact_number', str_replace('+63', '', Auth::user()->contact_number)) }}"
                        required
                        maxlength="10"
                        pattern="^9[0-9]{9}$"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        style="text-indent: 32px; @error('contact_number') border: 2px solid #d93025 !important; @enderror"
                    >
                </div>
                @error('contact_number')
                    <span class="server_error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form_actions">
                <a href="{{ route('account') }}" class="action_btn cancel_btn">Cancel</a>
                <button type="submit" class="action_btn submit_btn">Save</button>
            </div>
        </form>
    </div>
@endsection