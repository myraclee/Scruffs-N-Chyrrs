@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/faqs.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/faqs_dynamic.js')
@endsection

@section('content')

    <div class="faqs_opening">
        <h1>Frequently Asked Questions</h1>
        <p>Have questions about our services? We've got answers!</p>
    </div>

    <div class="faqs_container_section">
        <div class="faqs_accordion_wrapper">
            {{-- FAQs will be loaded dynamically here via JavaScript --}}
        </div>
    </div>

@endsection
