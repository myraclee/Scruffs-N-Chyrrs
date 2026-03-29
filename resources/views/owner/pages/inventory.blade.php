@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/inventory.css'])
@endsection

@section('content')

@php
    $lowStocks = []; 
    $outOfStocks = []; 
    $materials = []; 
@endphp

<div class="content_container">
     <div class="header_animation">
        <span class="star">✦</span>
        <h1 class="page_title animated_header" id="animatedHeader">Inventory Management</h1>
        <span class="star">✦</span>
    </div>

    <div class="status_cards">
        <div class="card low_stock">
            <h3>Low Stocks</h3>
            <div id="lowStockList">
                <p class="empty_status" id="lowStockEmpty">Inventory levels are normal.</p>
            </div>
        </div>
        
        <div class="card out_of_stock">
            <h3>Out of Stock</h3>
            <div id="outOfStockList">
                <p class="empty_status" id="outOfStockEmpty">All items are in stock.</p>
            </div>
        </div>
    </div>

    <div class="table_wrapper">
        <table class="inventory_table">
            <thead>
                <tr>
                    <th>Materials</th>
                    <th class="text-center">Units</th>
                    <th>Product</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <tr id="emptyInventoryRow">
                    <td colspan="3" class="text-center" style="padding: 60px; color: #682C7A; font-style: italic;">
                        No materials found. Start by adding new stock below!
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="action_container">
        <button id="openAddModalBtn" class="primary_button">Add Materials</button>
    </div>
</div>

<div class="modal_overlay" id="modalOverlay">
    
    <div class="material_modal" id="addMaterialModal">
        <h2 class="modal_title">Add New Materials</h2>
        
        <div class="input_group">
            <label>Materials</label>
            <input type="text" id="newMaterialInput" class="text_input" placeholder="e.g. Glossy Lamination">
        </div>

        <div class="input_group">
            <label>Units</label>
            <input type="number" id="newUnitsInput" class="number_input" style="width: 150px;" min="0">
        </div>

        <div class="input_group">
            <div class="consumed_header">
                <label>Products Consumed</label>
                <span class="consumed_note">One material is consumed for<br>every amount of this quantity:</span>
            </div>
            
            <div class="consumed_list">
                <!-- Products loaded dynamically by JavaScript -->
            </div>
        </div>

        <div class="modal_actions">
            <button class="cancel_btn" onclick="closeAllModals()">Cancel</button>
            <button class="save_btn" id="saveSimulatedMaterialBtn">Add Material</button>
        </div>
    </div>

    <div class="material_modal" id="editMaterialModal">
        <h2 class="modal_title">Edit Materials</h2>
        
        <div class="input_group">
            <label>Materials</label>
            <input type="text" id="editMaterialName" class="text_input">
        </div>

        <div class="input_group">
            <label>Units</label>
            <input type="number" id="editMaterialUnits" class="number_input" style="width: 150px;" min="0">
        </div>

        <div class="input_group">
            <div class="consumed_header">
                <label>Products Consumed</label>
                <span class="consumed_note">One material is consumed for every amount of this quantity:</span>
            </div>
            
            <div class="consumed_list">
                <!-- Products loaded dynamically by JavaScript -->
            </div>
        </div>

        <div class="modal_actions">
            <button class="cancel_btn" onclick="closeAllModals()">Cancel</button>
            <button class="save_btn" id="saveEditMaterialBtn">Save Changes</button>
        </div>
    </div>

</div>

@vite('resources/js/owner/inventory_refactored.js')
@vite('resources/js/owner/animations.js')
@endsection