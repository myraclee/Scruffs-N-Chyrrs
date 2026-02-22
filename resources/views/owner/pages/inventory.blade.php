@extends('owner.layouts.app') @section('content')
<link rel="stylesheet" href="{{ asset('css/owner/inventory.css') }}">

<div class="inventory-container">
    <h1 class="page-title">Inventory Management</h1>

    <div class="status-cards">
        <div class="card low-stock">
            <h3>Low Stocks</h3>
            <p>Glossy Lamination</p>
        </div>
        <div class="card out-of-stock">
            <h3>Out of Stock</h3>
            <p>Glossy Lamination</p>
        </div>
    </div>

    <div class="table-container">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Materials</th>
                    <th>Units</th>
                    <th>Product</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Holographic Broken Glass</td>
                    <td class="text-center">67</td>
                    <td>Button Pins, Stickers, Photocards, Business Cards, Posters</td>
                    <td><a href="#" class="edit-icon">✎</a></td>
                </tr>
                <tr>
                    <td>Holographic Rainbow</td>
                    <td class="text-center">89</td>
                    <td>Button Pins, Stickers, Photocards, Business Cards, Posters</td>
                    <td><a href="#" class="edit-icon">✎</a></td>
                </tr>
                <tr>
                    <td>300GSM PHOTOPAPER</td>
                    <td class="text-center">4</td>
                    <td>Button Pins, Stickers, Photocards, Business Cards, Posters</td>
                    <td><a href="#" class="edit-icon">✎</a></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="action-footer">
        <a href="{{ route('owner.materials.add') }}" class="btn-add-material">Add Materials</a>
    </div>
</div>
@endsection