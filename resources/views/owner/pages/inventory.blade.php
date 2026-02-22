@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/inventory.css'])
@endsection

@section('content')
    <h1 class="page_header">Inventory</h1>
    
    <div class="inventory_container">
        <div class="inventory_stats">
            <div class="stat_card">
                <h3>Total Products</h3>
                <p class="stat_number">0</p>
            </div>
            <div class="stat_card">
                <h3>Low Stock</h3>
                <p class="stat_number">0</p>
            </div>
            <div class="stat_card">
                <h3>Out of Stock</h3>
                <p class="stat_number">0</p>
            </div>
        </div>

        <div class="inventory_table_container">
            <h2>Products</h2>
            <table class="inventory_table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="empty_message">No products added yet</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button class="add_product_btn">+ Add Product</button>
    </div>
@endsection
