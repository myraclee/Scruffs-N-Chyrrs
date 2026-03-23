@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/home.css'])
@vite(['resources/css/customer/pages/home_images.css'])
@vite(['resources/css/customer/pages/product_sample_modal.css'])
@endsection

@section('content')

    {{-- ===== HERO ===== --}}
    <section class="home_page_opening">

        <div class="hero_bg_dots"></div>

        <span class="hero_spark s1">✦</span>
        <span class="hero_spark s2">✦</span>
        <span class="hero_spark s3">✦</span>
        <span class="hero_spark s4">✦</span>
        <span class="hero_spark s5">✦</span>
        <span class="hero_spark s6">✦</span>
        <span class="hero_spark s7">✦</span>
        <span class="hero_spark s8">✦</span>

        <div class="home_page_opening_left">
            <p class="hero_eyebrow">
                <span class="twinkle_star">✦</span>
                Your Local Printing Service
                <span class="twinkle_star" style="animation-delay:.9s">✦</span>
            </p>
            <h2>PRINT YOUR<br><span class="heading_accent">IMAGINATION</span></h2>
            <p>Bring your vision into ink with Scruffs&amp;Chyrrs — your friendly printing service for stickers, pins, prints, and more.</p>
            <div class="hero_buttons">
                <a href="{{ route('products') }}" class="hero_ghost_btn">View Products</a>
                <a href="{{ route('aboutus') }}" class="hero_ghost_btn">About Us</a>
            </div>
        </div>

        <div class="home_page_opening_right">
            <span class="frame_spark fs_tl">✦</span>
            <span class="frame_spark fs_tr">✦</span>
            <span class="frame_spark fs_bl">✦</span>
            <span class="frame_spark fs_br">✦</span>
            <div class="home_images_slideshow">
                <div class="home_images_empty_state">
                    No images have been uploaded yet.
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

    {{-- ===== PRODUCT SAMPLES ===== --}}
    <div class="home_page_product_samples_container">

        <span class="samples_spark sp1">✦</span>
        <span class="samples_spark sp2">✦</span>
        <span class="samples_spark sp3">✦</span>
        <span class="samples_spark sp4">✦</span>

        <div class="samples_header">
            <span class="twinkle_star samples_header_star">✦</span>
            <h1>Product Samples</h1>
            <span class="twinkle_star samples_header_star" style="animation-delay:1.4s">✦</span>
        </div>
        <p class="samples_sub">A glimpse of what we can make for you</p>

        <div class="home_page_product_samples"></div>
    </div>

    <!-- Product Sample Gallery Modal -->
    <div id="sampleGalleryModal" class="sample_gallery_overlay">
        <div class="sample_gallery_modal">
            <button class="sample_gallery_close_btn" title="Close gallery">&times;</button>
            <button class="sample_gallery_fullscreen_btn" title="Toggle fullscreen">⛶</button>
            
            <div class="sample_gallery_header">
                <h2 class="sample_gallery_title">Product Sample</h2>
                <p class="sample_gallery_description"></p>
            </div>
            
            <div class="sample_gallery_main_container">
                <div class="sample_gallery_main_image_wrapper">
                    <img class="sample_gallery_main_image" src="" alt="Product sample image" />
                </div>
            </div>
            
            <div class="sample_gallery_thumbnails_section">
                <span class="sample_gallery_thumbnails_label">Images</span>
                <div class="sample_gallery_thumbnails_grid" id="sampleThumbnailGrid"></div>
            </div>
        </div>
    </div>

@endsection

@section('page_js')
@vite('resources/js/customer/pages/product_sample_modal.js')
@vite('resources/js/customer/pages/home_product_samples.js')
@vite('resources/js/customer/pages/home_images_slideshow.js')
@show