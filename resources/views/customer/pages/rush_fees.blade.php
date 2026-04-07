@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/rush_fees.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/rush_fees_display.js')
@endsection

@section('content')

    {{-- ===== BACK BUTTON ===== --}}
    <div class="rush_fees_back_wrap">
        <button class="rush_fees_back_btn" onclick="history.back()">
            <svg xmlns="http://www.w3.org/2000/svg" height="18px" width="18px" viewBox="0 -960 960 960" fill="currentColor">
                <path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/>
            </svg>
            Back
        </button>
    </div>

    {{-- ===== OPENING SECTION ===== --}}
    <div class="rush_fees_opening">

        {{-- Scattered sparkles --}}
        <span class="deco_star deco_star_1">✦</span>
        <span class="deco_star deco_star_2">✦</span>
        <span class="deco_star deco_star_3">✦</span>
        <span class="deco_star deco_star_4">✦</span>
        <span class="deco_star deco_star_5">✦</span>
        <span class="deco_star deco_star_6">✦</span>
        <span class="deco_star deco_star_7">✦</span>
        <span class="deco_star deco_star_8">✦</span>

        <h1 class="rush_fees_title">Rush Fees</h1>
        <p class="rush_fees_tagline">
            You got a deadline? No problem! We'll get it done in record time!
        </p>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="rush_fees_container">
        
        <div class="rush_fees_content_col">
            {{-- THE FIX: Info Section added outside the table wrapper so JS doesn't delete it --}}
            <div class="rush_info_section">
                <h2 class="rush_info_title">Note:</h2>
                <p class="rush_formula_text">
                    Deadline after payment = percentage added to total
                </p>
            </div>

            <div id="rushFeesTableWrapper">
                {{-- Table rendered dynamically by JavaScript --}}
            </div>
        </div>
        
    </div>

@endsection