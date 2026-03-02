@extends('owner.layouts.owner_layout')

@section('content')

<style>
    .content_container { padding: 20px 40px; max-width: 1200px; margin: 0 auto; }

    /* Typography & Core Colors */
    .page_title { font-family: 'SuperDream', sans-serif; font-size: 48px; color: #682C7A; margin-bottom: 30px; }
    .text-center { text-align: center !important; } /* Added !important to guarantee alignment */

    /* Status Cards */
    .status_cards { display: flex; gap: 30px; margin-bottom: 40px; justify-content: center; }
    .card { background: #FFF7EC; padding: 20px 40px; border-radius: 20px; text-align: center; box-shadow: 2px 4px 10px rgba(0,0,0,0.1); font-family: 'Coolvetica', sans-serif; min-width: 250px; }
    .low_stock { border: 3px solid #F3C642; color: #F3C642; }
    .out_of_stock { border: 3px solid #D94848; color: #D94848; }
    .card h3 { font-size: 28px; margin-bottom: 10px; font-weight: normal; }
    .card p { font-size: 18px; margin-bottom: 5px; color: #312E2E; }
    .empty_status { color: #312E2E; font-size: 16px; }

    /* Table Styling */
    .table_wrapper { background: #fff; border-radius: 20px; overflow-x: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; border: 2px solid #682C7A; width: 100%; }
    .inventory_table { width: 100%; border-collapse: collapse; font-family: 'Coolvetica', sans-serif; color: #4A205A; min-width: 700px; }
    .inventory_table th { background: #682C7A; color: #FFF; padding: 18px; font-size: 22px; font-weight: normal; text-align: left; font-family: 'Coolvetica', sans-serif; }
    .inventory_table td { padding: 18px; border-bottom: 1px solid #682C7A; font-size: 18px; }
    .inventory_table tr:last-child td { border-bottom: none; }
    
    .edit_btn { background: transparent; border: none; color: #682C7A; cursor: pointer; transition: 0.2s; font-size: 20px; }
    .edit_btn:hover { transform: scale(1.2); color: #A15FB5; }

    /* Add Button */
    .action_container { display: flex; justify-content: center; margin-top: 20px; }
    .primary_button { background: #682C7A; color: white; font-family: 'Coolvetica', sans-serif; font-size: 22px; padding: 15px 40px; border: none; border-radius: 30px; cursor: pointer; box-shadow: 2px 4px 8px rgba(0,0,0,0.2); transition: 0.3s; }
    .primary_button:hover { background: #A15FB5; transform: translateY(-3px); }

    /* Modals */
    .modal_overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; justify-content: center; align-items: center; z-index: 9999; }
    .modal_overlay.active { display: flex; }
    .material_modal { background: #FFF7EC; width: 550px; padding: 40px; border-radius: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); display: none; flex-direction: column; max-height: 90vh; overflow-y: auto; }
    .material_modal.active { display: flex; }
    .modal_title { font-family: 'SuperDream', sans-serif; font-size: 44px; color: #682C7A; text-align: center; margin-bottom: 30px; }
    
    /* Input Styling */
    .input_group { margin-bottom: 25px; }
    .input_group label { display: block; font-family: 'Coolvetica', sans-serif; color: #682C7A; font-size: 22px; margin-bottom: 8px; }
    .text_input, .number_input { width: 100%; padding: 12px 18px; border: 2px solid #EBEBEB; border-radius: 15px; font-size: 18px; color: #4A205A; outline: none; font-family: 'Arial', sans-serif; }
    
    /* Consumed Section Layout */
    .consumed_header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; }
    .consumed_header label { margin-bottom: 0; }
    .consumed_note { font-family: 'Coolvetica', sans-serif; font-size: 12px; color: #682C7A; text-align: center; line-height: 1.1; max-width: 140px; }
    .consumed_list { display: flex; flex-direction: column; gap: 12px; }
    .consumed_item { display: flex; justify-content: space-between; align-items: center; }
    .checkbox_label { font-family: 'Coolvetica', sans-serif; color: #682C7A; font-size: 18px; display: flex; align-items: center; gap: 12px; cursor: pointer; }
    .tiny_input { width: 70px; padding: 8px; text-align: center; border-radius: 10px; border: 1px solid #ccc; outline: none; }

    /* Modal Buttons */
    .modal_actions { display: flex; justify-content: center; gap: 25px; margin-top: 20px; }
    .cancel_btn { background: #9E9E9E; color: white; border-radius: 25px; padding: 12px 30px; border: none; cursor: pointer; font-size: 18px; font-family: 'Coolvetica', sans-serif; transition: 0.3s ease; box-shadow: 2px 4px 8px rgba(0,0,0,0.1); }
    .cancel_btn:hover { background: #7A7A7A; transform: translateY(-3px); }
    .save_btn { background: #682C7A; color: white; border-radius: 25px; padding: 12px 30px; border: none; cursor: pointer; font-size: 18px; font-family: 'Coolvetica', sans-serif; transition: 0.3s ease; box-shadow: 2px 4px 8px rgba(0,0,0,0.2); }
    .save_btn:hover { background: #A15FB5; transform: translateY(-3px); }
</style>

@php
    $lowStocks = []; 
    $outOfStocks = []; 
    $materials = []; 
@endphp

<div class="content_container">
    <h1 class="page_title">Inventory Management</h1>

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
                <span class="consumed_note">One material is consumed for every amount of this quantity:</span>
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
@endsection