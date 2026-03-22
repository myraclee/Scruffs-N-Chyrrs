@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/aboutus.css'])
@endsection

@section('content')

    {{-- ===== HERO — WHO WE ARE ===== --}}
    <section class="aboutus_hero">

        {{-- Scattered sparkles outside the box --}}
        <span class="deco_star deco_star_1">✦</span>
        <span class="deco_star deco_star_2">✦</span>
        <span class="deco_star deco_star_3">✦</span>
        <span class="deco_star deco_star_4">✦</span>
        <span class="deco_star deco_star_5">✦</span>
        <span class="deco_star deco_star_6">✦</span>
        <span class="deco_star deco_star_7">✦</span>
        <span class="deco_star deco_star_8">✦</span>

        {{-- The outlined box --}}
        <div class="aboutus_box">

            {{-- Corner sparkles inside the box --}}
            <span class="box_star box_star_tl">✦</span>
            <span class="box_star box_star_tr">✦</span>
            <span class="box_star box_star_bl">✦</span>
            <span class="box_star box_star_br">✦</span>

            <div class="aboutus_hero_inner">
                <p class="aboutus_header">
                    <span class="header_star">✦</span>
                    Who We Are
                    <span class="header_star">✦</span>
                </p>

                <div class="aboutus_brand_img_wrap">
                    <img src="{{ asset('images/brand_elements/label_name.png') }}"
                         class="aboutus_brand_img" alt="Scruffs & Chyrrs" />
                    <div class="aboutus_brand_glow"></div>
                </div>

                <p class="aboutus_body">
                    Scruffs&amp;Chyrrs Printing offers merchandise manufacturing services,
                    mainly for student artists looking to start their own small business journey.
                    We offer manufacturing of stickers, prints, button pins, and custom cut items.
                    Our goal is to make merchandise manufacturing more affordable and accessible
                    to budding artists, and to make this service easier to reach from the East.
                </p>

                <div class="aboutus_location">
                    <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 -960 960 960" fill="#682C7A">
                        <path d="M480-388q54-50 84-80t47-50q16-20 22.5-37t6.5-37q0-36-26-62t-62-26q-21 0-40.5 8.5T480-648q-12-15-31-23.5t-41-8.5q-36 0-62 26t-26 62q0 21 6 37t22 36q17 20 46 50t86 81Zm0 202q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z"/>
                    </svg>
                    <span>Cainta, Rizal</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== SCROLL DIVIDER ===== --}}
    <div class="page_scoll_divider">
        <div class="text_scroll">
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
            <span>Scruffs&amp;Chyrrs</span>
            <span>✦</span>
        </div>
    </div>

    {{-- ===== WHY CHOOSE US ===== --}}
    <section class="aboutus_why">
        <div class="aboutus_why_header">
            <span class="aboutus_divider_star">✦</span>
            <h2 class="aboutus_why_heading">Why Choose Scruffs&amp;Chyrrs</h2>
            <span class="aboutus_divider_star">✦</span>
        </div>

        <div class="aboutus_cards">
            <div class="aboutus_card">
                <div class="aboutus_card_icon">✦</div>
                <h3 class="aboutus_card_title">Great Quality at the Right Price</h3>
                <p class="aboutus_card_text">
                    We are dedicated to sourcing the best materials to ensure exceptional
                    quality and a professional finish for all your printing needs,
                    all at an affordable price.
                </p>
            </div>

            <div class="aboutus_card">
                <div class="aboutus_card_icon">✦</div>
                <h3 class="aboutus_card_title">Reliable &amp; Cost Effective</h3>
                <p class="aboutus_card_text">
                    Our use of cutting-edge printing technology guarantees precision and
                    attention to detail, setting us apart as a leader in the industry.
                </p>
            </div>

            <div class="aboutus_card">
                <div class="aboutus_card_icon">✦</div>
                <h3 class="aboutus_card_title">Efficient Customer Support</h3>
                <p class="aboutus_card_text">
                    We prioritize customer satisfaction and work closely with you to
                    understand your requirements, delivering customized solutions
                    that exceed expectations.
                </p>
            </div>
        </div>
    </section>

@endsection