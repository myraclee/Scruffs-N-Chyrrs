@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/orders.css'])
@endsection

@section('content')
<div class="orders_container">
    <div class="header_animation">
        <span class="star">✦</span>
        <h1 class="page_header animated_header" id="animatedHeader">Order Management</h1>
        <span class="star">✦</span>
    </div>

    <div class="search_wrapper">
        <input type="text" class="search_input" id="ownerOrdersSearch" placeholder="Search by order #, customer name, or email">
        <svg class="search_icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
        </svg>
    </div>

    <div class="filter_section">
        <label class="filter_label">Order Status:</label>
        <div class="filters_container">
            <button class="filter_btn active" data-filter="all">All Orders</button>
            <button class="filter_btn" data-filter="waiting">Waiting Approval</button>
            <button class="filter_btn" data-filter="approved">Order Approved</button>
            <button class="filter_btn" data-filter="preparing">Preparing Order</button>
            <button class="filter_btn" data-filter="ready">Ready for Ship</button>
            <button class="filter_btn" data-filter="completed">Completed Orders</button>
            <button class="filter_btn" data-filter="cancelled">Cancelled Orders</button>
        </div>
    </div>

    <div class="orders_list" id="ownerOrdersList">
        <div class="empty_state_container" style="display: block;">
            <p class="empty_message">Loading orders...</p>
        </div>
    </div>

    <div class="modal_overlay" id="orderDetailsModal" style="display: none;">
        <div class="details_modal_box">
            <div class="details_header">
                <div class="details_logo">
                    <img src="{{ asset('images/website_elements/label_name.png') }}" alt="Scruffs & Chyrrs Logo" class="modal_logo_img">
                </div>
                <div class="details_header_info">
                    <p><span class="brand_label">Order Date:</span> <span class="brand_placeholder" id="detailOrderDate">-</span></p>
                    <p><span class="brand_label">Order ID:</span> <span class="brand_placeholder" id="detailOrderId">-</span></p>
                </div>
            </div>

            <hr class="modal_divider">

            <div class="details_customer_info">
                <p><span class="brand_label">Customer Name:</span> <span class="brand_placeholder" id="detailCustomerName">-</span></p>
                <p><span class="brand_label">Contact Number:</span> <span class="brand_placeholder" id="detailCustomerContact">-</span></p>
                <p><span class="brand_label">Email:</span> <span class="brand_placeholder" id="detailCustomerEmail">-</span></p>
            </div>

            <div class="details_drive_link">
                <h3 class="bold_purple" style="font-size: 20px;">Uploaded Drive:</h3>
                <a href="#" target="_blank" class="brand_placeholder" id="detailDriveLink" style="text-decoration: underline;">No drive link submitted</a>
            </div>

            <div class="table_responsive_wrapper">
                <table class="details_table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Item Description</th>
                            <th>Uploaded File Name</th>
                            <th>Qty</th>
                            <th>Discount</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="detailItemsBody"></tbody>
                </table>
            </div>

            <div class="details_total">
                <h3 class="brand_label" style="font-size: 24px;">Order Total:
                    <span class="brand_placeholder" id="detailOrderTotal" style="font-size: 24px; margin-left: 10px;">₱0.00</span>
                </h3>
            </div>

            <div class="details_statuses" id="modalStatusContainer">
                <div class="status_group">
                    <span class="brand_label" style="font-size: 26px;">Order Status:</span>
                    <select class="status_select status-yellow" id="detailStatusSelect">
                        <option value="waiting">Waiting for Order Approval</option>
                        <option value="approved">Order Approved</option>
                        <option value="preparing">Preparing Order</option>
                        <option value="ready">Ready for Shipping</option>
                        <option value="completed">Completed Orders</option>
                        <option value="cancelled">Cancelled Orders</option>
                    </select>
                </div>
            </div>

            <div class="details_footer">
                <button class="close_modal_btn" id="closeDetailsModalBtn">Close</button>
            </div>
        </div>
    </div>

    <div class="modal_overlay" id="ownerOrderLoadingModal" style="display: none; z-index: 1001;">
        <div class="payment_modal_box" style="align-items: center;">
            <h3 class="payment_header">Updating Order...</h3>
            <p class="payment_value">Please wait.</p>
        </div>
    </div>
</div>

@vite('resources/js/owner/pages/orders.js')
@vite('resources/js/owner/animations.js')
@endsection
