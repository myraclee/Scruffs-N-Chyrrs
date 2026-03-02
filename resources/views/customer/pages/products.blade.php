@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/products.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/products_page.js')
@endsection

@section('content')

    <div class="products_opening">
        <h1>Our Products</h1>
        <p>Explore our collection of high-quality merchandise for your creative journey.</p>
    </div>

    <div class="products_section">
        <div class="products_grid" id="productsGrid">
            <p style="text-align: center; color: #999; padding: 40px; grid-column: 1 / -1;">Loading products...</p>
        </div>
    </div>

    <!-- Price List Modal -->
    <div id="priceListModal" class="products_price_modal_overlay">
        <div class="products_price_modal_content">
            <button class="products_price_modal_close" id="closePriceModal">&times;</button>
            
            <div class="products_price_modal_header">
                <h2 id="priceModalTitle">Product Price List</h2>
            </div>

            <div class="products_price_modal_body">
                <div class="products_price_carousel">
                    <button class="products_price_carousel_btn" id="prevPriceImage">&lt;</button>
                    <img id="priceListImage" src="" alt="Price List" class="products_price_image">
                    <button class="products_price_carousel_btn" id="nextPriceImage">&gt;</button>
                </div>
                <p class="products_price_counter" id="priceImageCounter"></p>
            </div>
        </div>
    </div>

@endsection
