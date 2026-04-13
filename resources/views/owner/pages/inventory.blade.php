@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/inventory.css'])
@endsection

@section('content')

@php
    $highStocks = []; 
    $mediumStocks = []; 
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

    <div class="status_cards" id="statusCards">
        <div class="card status_card high_stock status_healthy" id="highStockCard" data-filter="high" role="button" tabindex="0" aria-pressed="false">
            <h3>High (71%+)</h3>
            <div id="highStockList" aria-live="polite">
                <p class="empty_status">Checking levels...</p>
            </div>
        </div>

        <div class="card status_card medium_stock status_healthy" id="mediumStockCard" data-filter="medium" role="button" tabindex="0" aria-pressed="false">
            <h3>Medium (30%-70%)</h3>
            <div id="mediumStockList" aria-live="polite">
                <p class="empty_status">Checking levels...</p>
            </div>
        </div>

        <div class="card status_card low_stock status_healthy" id="lowStockCard" data-filter="low" role="button" tabindex="0" aria-pressed="false">
            <h3>Low (Below 29%)</h3>
            <div id="lowStockList" aria-live="polite">
                <p class="empty_status">Checking levels...</p>
            </div>
        </div>
        
        <div class="card status_card out_of_stock status_healthy" id="outOfStockCard" data-filter="out_of_stock" role="button" tabindex="0" aria-pressed="false">
            <h3>Out of Stock</h3>
            <div id="outOfStockList" aria-live="polite">
                <p class="empty_status">Checking levels...</p>
            </div>
        </div>
    </div>

    <div class="inventory_controls" aria-label="Inventory table controls">
        <div class="inventory_control search_control">
            <label for="inventorySearchInput">Search Material</label>
            <input type="search" id="inventorySearchInput" class="text_input" placeholder="Search material or usage..." autocomplete="off">
        </div>

        <div class="inventory_control sort_control">
            <label for="inventorySortSelect">Sort By</label>
            <select id="inventorySortSelect" class="number_input inventory_select">
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="units_asc">Units (Low-High)</option>
                <option value="units_desc">Units (High-Low)</option>
                <option value="percent_asc">Stock % (Low-High)</option>
                <option value="percent_desc">Stock % (High-Low)</option>
            </select>
        </div>

        <div class="inventory_control action_control">
            <button type="button" id="clearInventoryFiltersBtn" class="secondary_button" disabled>Clear Filters</button>
        </div>
    </div>

    <p id="inventoryFilterSummary" class="inventory_filter_summary" aria-live="polite">Showing all materials</p>

    <div class="table_wrapper">
        <table class="inventory_table">
            <thead>
                <tr>
                    <th>Materials</th>
                    <th class="text-center">Units</th>
                    <th class="text-center">Stock Level</th>
                    <th>Products / Usage</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <tr id="emptyInventoryRow">
                    <td colspan="5" class="text-center" style="padding: 60px; color: #682C7A; font-style: italic;">
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
            <input type="text" id="newMaterialInput" class="text_input" placeholder="e.g. Glossy Lamination" maxlength="150">
        </div>

        <div class="half_input">
            <div class="input_group units_input">
                <label>Units</label>
                <input type="number" id="newUnitsInput" class="number_input" style="width: 150px;" min="0" step="1" inputmode="numeric">
                <small class="field_hint">Material in stock</small>
            </div>

            <div class="input_group units_input">
                <label>Total Units</label>
                <input type="number" id="newMaxUnitsInput" class="number_input" style="width: 150px;" min="1" step="1" inputmode="numeric" value="1">
                <small class="field_hint">Capacity baseline for stock %</small>
            </div>

            <div class="input_group lowstock_input">
                <label>Low Stock</label>
                <input type="number" id="newThresholdInput" class="number_input" style="width: 150px;" min="1" step="1" inputmode="numeric" value="5">
                <small class="field_hint">Warn at or below this level</small>
            </div>
        </div>

        <div class="input_group">
            <div class="consumed_header">
                <label>Products Consumed <span style="color: #d94848;">*</span></label>
                <span class="consumed_note">Any Option fallback applies to all selections.<br>If one material matches multiple rules, the highest quantity is used.</span>
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
            <input type="text" id="editMaterialName" class="text_input" placeholder="e.g. Glossy Lamination" maxlength="150">
        </div>

        <div class="half_input">
            <div class="input_group units_input">
                <label>Units</label>
                <input type="number" id="editMaterialUnits" class="number_input" style="width: 150px;" min="0" step="1" inputmode="numeric">
                <small class="field_hint">Material in stock</small>
            </div>

            <div class="input_group units_input">
                <label>Total Units</label>
                <input type="number" id="editMaxUnitsInput" class="number_input" style="width: 150px;" min="1" step="1" inputmode="numeric" value="1">
                <small class="field_hint">Capacity baseline for stock %</small>
            </div>

            <div class="input_group lowstock_input">
                <label>Low Stock</label>
                <input type="number" id="editThresholdInput" class="number_input" style="width: 150px;" min="1" step="1" inputmode="numeric" value="5">
                <small class="field_hint">Warn at or below this level</small>
            </div>
        </div>

        <div class="input_group">
            <div class="consumed_header">
                <label>Products Consumed <span style="color: #d94848;">*</span></label>
                <span class="consumed_note">Any Option fallback applies to all selections.<br>If one material matches multiple rules, the highest quantity is used.</span>
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