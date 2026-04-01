@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/product-detail.css', 'resources/css/customer/pages/order_modal.css'])
@endsection

@section('page_js')
@vite(['resources/js/customer/pages/product-detail.js', 'resources/js/customer/pages/order_modal.js'])
@endsection

@section('content')

{{-- Background Sparkles --}}
<div class="product_sparkles_bg" aria-hidden="true">
    @for($i = 1; $i <= 12; $i++)
        <span class="product_spark s{{ $i }}">✦</span>
    @endfor
</div>

<div class="product_detail_container" 
     data-product="{{ json_encode($product->load('priceImages')) }}"
     data-product-id="{{ $product->id }}"
     data-product-slug="{{ $product->slug }}">
    
    {{-- BREADCRUMBS & BACK BUTTON --}}
    <div class="breadcrumbs_container">
        <a href="#" class="breadcrumb_back_btn" id="backBtn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            <span>Back</span>
        </a>
        <nav class="breadcrumbs">
            <a href="{{ route('products') }}" class="breadcrumb_link">Products</a>
            <span class="breadcrumb_separator">✦</span>
            <span class="breadcrumb_current">{{ $product->name }}</span>
        </nav>
    </div>
    
    {{-- OTHER PRODUCTS NAVIGATION --}}
    @if($otherProducts->count() > 0)
        <div class="other_products_section">
            <div class="other_products_header">
                <span class="section_spark">✦</span>
                <h2 class="section_title">Explore Other Products</h2>
                <span class="section_line"></span>
            </div>
            
            <div class="product_nav_wrapper">
                <div class="product_nav_scroll">
                    @foreach($otherProducts as $navItem)
                        <a href="{{ route('product.detail', $navItem->slug) }}" class="product_nav_btn">
                            <span class="nav_btn_icon">✦</span>
                            <span class="nav_btn_text">{{ $navItem->name }}</span>
                            <span class="nav_btn_arrow">→</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- CURRENT PRODUCT SHOWCASE --}}
    <div class="current_product_showcase">
        <div class="showcase_sparkle left">✦</div>
        <div class="showcase_content">
            <span class="showcase_label">Currently Viewing</span>
            <h1 class="showcase_title">{{ $product->name }}</h1>
            @if($product->description)
                <p class="showcase_description">{{ $product->description }}</p>
            @endif
        </div>
        <div class="showcase_sparkle right">✦</div>
    </div>

    {{-- PRICE IMAGES GALLERY SECTION --}}
    <div class="price_gallery_section">
        <div class="gallery_header">
            <span class="gallery_spark">✦</span>
            <h3>Choose Your Style</h3>
            <span class="gallery_spark">✦</span>
        </div>
        
        <div class="price_gallery" id="priceGallery">
            <!-- Skeleton loaders will be inserted here by JavaScript -->
        </div>
    </div>

    {{-- ORDER NOW BUTTON --}}
    <div class="order_button_container">
        <button class="order_now_button" id="orderNowBtn" disabled>
            <span class="btn_sparkle">✦</span>
            <span>Order Now</span>
            <span class="btn_sparkle">✦</span>
        </button>
    </div>

</div>

{{-- ORDER MODAL COMPONENT --}}
@include('customer.pages.order_modal')

@endsection