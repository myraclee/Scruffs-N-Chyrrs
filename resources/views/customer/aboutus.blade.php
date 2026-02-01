@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/aboutus.css'])
@endsection

@section('content')

    <h1>About Us</h1>

    <div class="aboutus_main_container">
        <div class="aboutus_name_label">
            <img src="{{ asset('images/brand_elements/label_name.png') }}" class="aboutus_label">
        </div>

        <div class="aboutus_information_container">
            <div class="aboutus_text">
                <p>
                    Scruffs&Chyrrs Printing offers merchandise manufacturing services,
                    mainly for student artists looking to start their own small business 
                    journey. We offer manufacturing of stickers, prints, button pins, and
                    custom cut items. Our goal is to make merchandise manufacturing more
                    affordable and accessible to budding artists, and to make this service
                    easier to reach from the East.
                </p>

                <div class="aboutus_location">
                    <svg xmlns="http://www.w3.org/2000/svg" height="30px" width="30px" viewBox="0 -960 960 960" fill="#682C7A"><path d="M480-388q54-50 84-80t47-50q16-20 22.5-37t6.5-37q0-36-26-62t-62-26q-21 0-40.5 8.5T480-648q-12-15-31-23.5t-41-8.5q-36 0-62 26t-26 62q0 21 6 37t22 36q17 20 46 50t86 81Zm0 202q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z"/></svg>
                    <p>Cainta, Rizal</p>
                </div>

            </div>
        </div>
    </div>

@endsection