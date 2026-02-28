@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/home.css'])
@endsection

@section('content')
    <div class="home_page_opening">
        <div class="home_page_opening_left">
            <h2>PRINT YOUR IMAGINATION</h2>
            <p>Bring your vision into ink with Scruffs&Chyrrs, your friendly printing service!</p>
            <button>View Products</button>
        </div>
        <div class="home_page_opening_right">
        </div>
    </div>

    <div class="page_scoll_divider">
        <div class="text_scroll">
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&Chyrrs</span>
            <span>✦</span>
        </div>
    </div>

    <div class="home_page_product_samples_container">
        <h1>Product Samples</h1>
        <div class="home_page_product_samples">
        </div>
    </div>
@endsection