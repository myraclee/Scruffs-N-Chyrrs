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
        <div class="card status_card low_stock status_healthy" id="lowStockCard">
            <h3>Low Stocks</h3>
            <div id="lowStockList" aria-live="polite">
                <p class="empty_status">Checking levels...</p>
            </div>
        </div>
        
        <div class="card status_card out_of_stock status_healthy" id="outOfStockCard">
            <h3>Out of Stock</h3>
            <div id="outOfStockList" aria-live="polite">
                <p class="empty_status">Checking levels...</p>
            </div>
        </div>
    </div>

    <div class="table_wrapper">
        <table class="inventory_table">
            <thead>
                <tr>
                    <th>Materials</th>
                    <th class="text-center">Units</th>
                    <th>Consumption Rules</th>
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
        <button type="button" id="openAddModalBtn" class="primary_button">Add Materials</button>
    </div>
</div>

<div class="modal_overlay" id="modalOverlay" aria-hidden="true">
    
    <div class="material_modal" id="addMaterialModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="addMaterialTitle" tabindex="-1">
        <h2 class="modal_title" id="addMaterialTitle">Add New Materials</h2>
        
        <div class="input_group">
            <label>Materials</label>
            <input type="text" id="newMaterialInput" class="text_input" placeholder="e.g. Glossy Lamination">
        </div>

        <div class="input_group">
            <label>Units</label>
            <input type="number" id="newUnitsInput" class="number_input" style="width: 150px;" min="0" step="1" inputmode="numeric">
        </div>

        <div class="input_group">
            <label>Low Stock Threshold</label>
            <input type="number" id="newThresholdInput" class="number_input" style="width: 150px;" min="1" step="1" inputmode="numeric" value="5">
            <small class="field_hint">Alert for this material when units are at or below this number.</small>
        </div>

        <div class="input_group">
            <div class="consumed_header">
                <label>Products Consumed</label>
                <span class="consumed_note">Set fallback and option-specific<br>consumption quantities per product.</span>
            </div>
            
            <div class="consumed_list">
                <!-- Products loaded dynamically by JavaScript -->
            </div>
        </div>

        <div class="modal_actions">
            <button type="button" class="cancel_btn" onclick="closeAllModals()">Cancel</button>
            <button type="button" class="save_btn" id="saveSimulatedMaterialBtn">Add Material</button>
        </div>
    </div>

    <div class="material_modal" id="editMaterialModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="editMaterialTitle" tabindex="-1">
        <h2 class="modal_title" id="editMaterialTitle">Edit Materials</h2>
        
        <div class="input_group">
            <label>Materials</label>
            <input type="text" id="editMaterialName" class="text_input">
        </div>

        <div class="input_group">
            <label>Units</label>
            <input type="number" id="editMaterialUnits" class="number_input" style="width: 150px;" min="0" step="1" inputmode="numeric">
        </div>

        <div class="input_group">
            <label>Low Stock Threshold</label>
            <input type="number" id="editThresholdInput" class="number_input" style="width: 150px;" min="1" step="1" inputmode="numeric" value="5">
            <small class="field_hint">Alert for this material when units are at or below this number.</small>
        </div>

        <div class="input_group">
            <div class="consumed_header">
                <label>Products Consumed</label>
                <span class="consumed_note">Set fallback and option-specific consumption quantities per product.</span>
            </div>
            
            <div class="consumed_list">
                <!-- Products loaded dynamically by JavaScript -->
            </div>
        </div>

        <div class="modal_actions">
            <button type="button" class="cancel_btn" onclick="closeAllModals()">Cancel</button>
            <button type="button" class="save_btn" id="saveEditMaterialBtn">Save Changes</button>
        </div>
    </div>

</div>

<div class="modal_overlay" id="deleteMaterialConfirmOverlay" aria-hidden="true">
    <div class="delete_confirmation_modal" id="deleteMaterialConfirmModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="deleteMaterialConfirmMessage" tabindex="-1">
        <p class="delete_msg" id="deleteMaterialConfirmMessage">
            Are you sure you want to delete this material?
        </p>
        <small class="delete_subtext">This action cannot be undone.</small>
        <div class="delete_confirmation_actions">
            <button type="button" class="cancel_delete_btn" id="deleteMaterialCancelBtn">Cancel</button>
            <button type="button" class="confirm_delete_btn" id="deleteMaterialConfirmBtn">Delete Material</button>
        </div>
    </div>
</div>

@vite('resources/js/owner/inventory_refactored.js')
@vite('resources/js/owner/animations.js')
@endsection