@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/faqs.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/faqs_dynamic.js')
@endsection

@section('content')

{{-- Background Sparkles --}}
<div class="faqs_sparkles_bg" aria-hidden="true">
    <span class="faqs_spark s1">✦</span>
    <span class="faqs_spark s2">✧</span>
    <span class="faqs_spark s3">✦</span>
    <span class="faqs_spark s4">✧</span>
    <span class="faqs_spark s5">✦</span>
    <span class="faqs_spark s6">✧</span>
    <span class="faqs_spark s7">✦</span>
    <span class="faqs_spark s8">✧</span>
    <span class="faqs_spark s9">✦</span>
    <span class="faqs_spark s10">✧</span>
    <span class="faqs_spark s11">✦</span>
    <span class="faqs_spark s12">✧</span>
    <span class="faqs_spark s13">✦</span>
    <span class="faqs_spark s14">✧</span>
    <span class="faqs_spark s15">✦</span>
    <span class="faqs_spark s16">✧</span>
    <span class="faqs_spark s17">✦</span>
    <span class="faqs_spark s18">✧</span>
</div>

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