@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/rush_fees.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/rush_fees_display.js')
@endsection

@section('content')

    <div class="rush_fees_opening">
        <h1>Rush Fees</h1>
    </div>

    <div class="rush_fees_container">
        <div id="rushFeesTableWrapper">
            <!-- Table rendered dynamically by JavaScript -->
        </div>
    </div>

@endsection
