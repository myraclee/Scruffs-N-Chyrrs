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

@endsection
