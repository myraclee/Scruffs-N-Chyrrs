@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/product-detail.css', 'resources/css/customer/pages/order_modal.css'])
@endsection

@section('page_js')
@vite(['resources/js/customer/pages/product-detail.js', 'resources/js/customer/pages/order_modal.js'])
@endsection

@section('content')
    <div class="product_detail_container" 
         data-product="{{ json_encode($product->load('priceImages')) }}"
         data-product-id="{{ $product->id }}"
         data-product-slug="{{ $product->slug }}">
        
        {{-- BREADCRUMBS & BACK BUTTON --}}
        <div class="breadcrumbs_container">
            <a href="#" class="breadcrumb_back_btn" id="backBtn">← Back</a>
            <nav class="breadcrumbs">
                <a href="{{ route('products') }}" class="breadcrumb_link">Products</a>
                <span class="breadcrumb_separator">/</span>
                <span class="breadcrumb_current">{{ $product->name }}</span>
            </nav>
        </div>

        {{-- PRODUCT TITLE --}}
        <div class="product_detail_header">
            <h1 class="product_detail_title">{{ $product->name }}</h1>
        </div>

        {{-- PRICE IMAGES GALLERY (2-COLUMN GRID) --}}
        <div class="price_gallery" id="priceGallery">
            <!-- Skeleton loaders will be inserted here by JavaScript -->
        </div>

        {{-- ORDER NOW BUTTON --}}
        <div class="order_button_container">
            <button class="order_now_button" id="orderNowBtn" disabled>Order Now</button>
        </div>

    </div>

    {{-- ORDER MODAL COMPONENT --}}
    @include('customer.pages.order_modal')
@endsection
