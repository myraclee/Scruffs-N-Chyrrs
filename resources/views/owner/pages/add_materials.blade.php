@extends('owner.layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/owner/inventory_forms.css') }}">

<div class="form-overlay">
    <div class="material-card">
        <h2 class="form-title">Add New Materials</h2>
        
        <form action="#" method="POST">
            @csrf
            <div class="form-group">
                <label>Materials</label>
                <input type="text" name="material_name" class="input-main">
            </div>

            <div class="form-group">
                <label>Units</label>
                <input type="number" name="units" class="input-short">
            </div>

            <div class="products-consumed-section">
                <div class="section-header">
                    <label>Products Consumed</label>
                    <span class="helper-text">One material is consumed for every amount of this quantity:</span>
                </div>

                <div class="product-list">
                    @php $products = ['Stickers', 'Button Pins', 'Posters', 'Business Cards', 'Photocards']; @endphp
                    @foreach($products as $product)
                    <div class="product-row">
                        <div class="product-info">
                            <input type="checkbox" name="products[]" value="{{ $product }}">
                            <span>{{ $product }}</span>
                        </div>
                        <input type="number" name="qty[]" class="input-qty">
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Add Material</button>
            </div>
        </form>
    </div>
</div>
@endsection