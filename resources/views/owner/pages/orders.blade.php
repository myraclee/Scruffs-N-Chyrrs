@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/orders.css'])
@endsection

@section('content')
<div class="orders_container">
    <h1 class="page_header">Order Management</h1>

    <div class="search_wrapper">
        <input type="text" class="search_input" placeholder="Search for orders">
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

    <div class="orders_list">

        <div class="empty_state_container"style="display: block;">
            <p class="empty_message">No orders yet... :c</p>
        </div>

        <div id="placeholder_cards" style="display: none;">
            
            <div class="order_card" data-is-paid="false">
                <div class="card_top">
                    <div class="customer_info">
                        <h3>[Customer Name] (Unpaid)</h3>
                        <p>[Customer Email]</p>
                    </div>
                    <button class="view_details_btn">View Details</button>
                </div>

                <div class="card_middle">
                    <div class="status_group">
                        <span class="status_label">Order Status:</span>
                        <select class="status_select status-yellow" onchange="updateOrderStatus(this)">
                            <option value="waiting" selected>Waiting for Order Approval</option>
                            <option value="approved">Order Approved</option>
                            <option value="preparing">Preparing Order</option>
                            <option value="ready">Ready for Shipping</option>
                            <option value="completed">Completed Orders</option>
                            <option value="cancelled">Cancelled Orders</option>
                        </select>
                    </div>

                    <div class="status_group">
                        <span class="status_label">Payment Status:</span>
                        <div class="payment_pill status-yellow">
                            Awaiting Payment 
                        </div>
                    </div>
                </div>

                <hr class="card_divider">

                <div class="card_bottom">
                    <div class="detail_group">
                        <span class="detail_label">Order ID:</span>
                        <span class="detail_value">[Order ID]</span>
                    </div>
                    <div class="detail_group">
                        <span class="detail_label">Order Items:</span>
                        <span class="detail_value">[Order Items]</span>
                    </div>
                    <div class="detail_group">
                        <span class="detail_label">Order Price:</span>
                        <span class="detail_value">[Order Price]</span>
                    </div>
                    <div class="detail_group">
                        <span class="detail_label">Order Date:</span>
                        <span class="detail_value">[Order Date]</span>
                    </div>
                </div>
            </div>

            <div class="order_card" data-is-paid="true">
                <div class="card_top">
                    <div class="customer_info">
                        <h3>[Customer Name] (Paid)</h3>
                        <p>[Customer Email]</p>
                    </div>
                    <button class="view_details_btn">View Details</button>
                </div>

                <div class="card_middle">
                    <div class="status_group">
                        <span class="status_label">Order Status:</span>
                        <select class="status_select status-yellow" onchange="updateOrderStatus(this)">
                            <option value="waiting" selected>Waiting for Order Approval</option>
                            <option value="approved">Order Approved</option>
                            <option value="preparing">Preparing Order</option>
                            <option value="ready">Ready for Shipping</option>
                            <option value="completed">Completed Orders</option>
                            <option value="cancelled">Cancelled Orders</option>
                        </select>
                    </div>

                    <div class="status_group">
                        <span class="status_label">Payment Status:</span>
                        <div class="payment_pill status-green">
                            Payment Received 
                            </div>
                    </div>
                </div>

                <hr class="card_divider">

                <div class="card_bottom">
                    <div class="detail_group">
                        <span class="detail_label">Order ID:</span>
                        <span class="detail_value">[Order ID]</span>
                    </div>
                    <div class="detail_group">
                        <span class="detail_label">Order Items:</span>
                        <span class="detail_value">[Order Items]</span>
                    </div>
                    <div class="detail_group">
                        <span class="detail_label">Order Price:</span>
                        <span class="detail_value">[Order Price]</span>
                    </div>
                    <div class="detail_group">
                        <span class="detail_label">Order Date:</span>
                        <span class="detail_value">[Order Date]</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal_overlay" id="orderDetailsModal" style="display: none;">
        <div class="details_modal_box"> 
            <div class="details_header">
                <div class="details_logo">
                    <img src="{{ asset('images/website_elements/label_name.png') }}" alt="Scruffs & Chyrrs Logo" class="modal_logo_img">
                </div>
                <div class="details_header_info">
                    <p><span class="brand_label">Order Date:</span> <span class="brand_placeholder">[Order Date]</span></p>
                    <p><span class="brand_label">Order ID:</span> <span class="brand_placeholder">[Order ID]</span></p>
                </div>
            </div>

            <hr class="modal_divider">

            <div class="details_customer_info">
                <p><span class="brand_label">Customer Name:</span> <span class="brand_placeholder">[Customer Name]</span></p>
                <p><span class="brand_label">Contact Number:</span> <span class="brand_placeholder">[Contact Number]</span></p>
                <p><span class="brand_label">Email:</span> <span class="brand_placeholder">[Customer Email]</span></p>
            </div>

            <div class="details_drive_link">
                <h3 class="bold_purple" style="font-size: 20px;">Uploaded Drive:</h3>
                <a href="#" target="_blank" class="brand_placeholder" style="text-decoration: underline;">[Drive Link Placeholder]</a>
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
                    <tbody>
                        <tr>
                            <td class="brand_placeholder">[No.]</td>
                            <td class="brand_placeholder">[Item Description]</td>
                            <td class="brand_placeholder">[File Name]</td>
                            <td class="brand_placeholder">[Qty]</td>
                            <td class="brand_placeholder">[Discount]</td>
                            <td class="brand_placeholder">[Total]</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="details_total">
                <h3 class="brand_label" style="font-size: 24px;">Order Total: 
                    <span class="brand_placeholder" style="font-size: 24px; margin-left: 10px;">[Total Amount]</span>
                </h3>
            </div>

            <div class="details_statuses" data-is-paid="true" id="modalStatusContainer">
                <div class="status_group">
                    <span class="brand_label" style="font-size: 26px;">Order Status:</span>
                    <select class="status_select status-yellow" onchange="updateModalStatus(this)">
                            <option value="waiting" selected>Waiting for Order Approval</option>
                            <option value="approved">Order Approved</option>
                            <option value="preparing">Preparing Order</option>
                            <option value="ready">Ready for Shipping</option>
                            <option value="completed">Completed Orders</option>
                            <option value="cancelled">Cancelled Orders</option>
                        </select>
                </div>

                <div class="status_group">
                    <span class="brand_label" style="font-size: 26px;">Payment Status:</span>
                    <div class="payment_pill status-green" id="modalPaymentPill">
                        Payment Received 
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
                    </div>
                </div>
            </div>

            <div class="details_footer">
                <button class="close_modal_btn" onclick="closeDetailsModal()">Close</button>
            </div>

        </div>
    </div>

    <div class="modal_overlay" id="paymentDetailsModal" style="display: none; z-index: 1001;">
        <div class="payment_modal_box">
            <div class="payment_logo">
                <img src="{{ asset('images/website_elements/label_name.png') }}" alt="Scruffs & Chyrrs Logo" class="modal_logo_img">
            </div>
            
            <h3 class="payment_header">Customer Screenshot:</h3>
            
            <div class="payment_screenshot_container">
                <img src="https://via.placeholder.com/300x500.png?text=GCash+Receipt+Placeholder" alt="Receipt Screenshot" class="payment_screenshot">
            </div>
            
            <div class="payment_info_group">
                <span class="payment_label">GCash Contact Number:</span>
                <span class="payment_value">[GCash Number]</span>
            </div>
            
            <div class="payment_info_group">
                <span class="payment_label">Reference Number:</span>
                <span class="payment_value">[Reference Number]</span>
            </div>
            
            <button class="payment_done_btn" onclick="closePaymentModal()">Done</button>
        </div>
    </div>
    <div class="modal_overlay" id="cancelConfirmModal" style="display: none; z-index: 1002;">
        <div class="cancel_modal_box">
            <h2 class="cancel_header">Cancel Order?</h2>
            <p class="cancel_text">Are you sure you want to cancel this order? This action cannot be undone.</p>
            <div class="cancel_buttons">
                <button class="cancel_btn_no" onclick="handleCancelDecision(false)">No, Keep It</button>
                <button class="cancel_btn_yes" onclick="handleCancelDecision(true)">Yes, Cancel Order</button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    // --- Global State Tracking ---
    let currentOpenCard = null;      
    let pendingSelectElement = null; 
    let pendingPreviousValue = null; 
    let pendingCard = null;          

    // --- 1. Core Sync & Color Functions ---
    function updateSelectColors(selectElement, statusValue) {
        if (!selectElement) return;
        selectElement.classList.remove('status-yellow', 'status-green', 'status-blue', 'status-orange', 'status-red');
        if (statusValue === 'waiting') selectElement.classList.add('status-yellow');
        else if (statusValue === 'approved' || statusValue === 'completed') selectElement.classList.add('status-green');
        else if (statusValue === 'preparing') selectElement.classList.add('status-blue');
        else if (statusValue === 'ready') selectElement.classList.add('status-orange');
        else if (statusValue === 'cancelled') selectElement.classList.add('status-red');
    }

    function updatePaymentPill(pillElement, isPaid) {
        if (!pillElement) return;
        if (isPaid) {
            pillElement.className = 'payment_pill status-green';
            pillElement.innerHTML = `Payment Received <svg onclick="openPaymentModal()" style="cursor: pointer; margin-left: 8px; transition: 0.2s;" onmouseover="this.style.color='#682C7A'" onmouseout="this.style.color='currentColor'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>`;
        } else {
            pillElement.className = 'payment_pill status-yellow';
            pillElement.innerHTML = `Awaiting Payment`;
        }
    }

    function syncCardAndModal(card, newStatus) {
        if (!card) return;
        
        card.setAttribute('data-status', newStatus);
        const cardSelect = card.querySelector('.status_select');
        const cardPill = card.querySelector('.payment_pill');
        const isPaid = card.getAttribute('data-is-paid') === 'true';

        if (cardSelect) {
            cardSelect.value = newStatus;
            updateSelectColors(cardSelect, newStatus);
        }
        updatePaymentPill(cardPill, isPaid);

        if (currentOpenCard === card) {
            const modalContainer = document.getElementById('modalStatusContainer');
            modalContainer.setAttribute('data-status', newStatus);
            
            const modalSelect = modalContainer.querySelector('.status_select');
            const modalPill = document.getElementById('modalPaymentPill');
            
            if (modalSelect) {
                modalSelect.value = newStatus;
                updateSelectColors(modalSelect, newStatus);
            }
            updatePaymentPill(modalPill, isPaid);
        }
    }

    function applySafetyRules(card) {
        const isPaid = card.getAttribute('data-is-paid') === 'true';
        const select = card.querySelector('.status_select');
        let currentStatus = card.getAttribute('data-status') || select.value;

        Array.from(select.options).forEach(opt => {
            if (isPaid) opt.disabled = false;
            else opt.disabled = (opt.value !== 'waiting' && opt.value !== 'cancelled');
        });

        if (!isPaid && currentStatus !== 'waiting' && currentStatus !== 'cancelled') {
            currentStatus = 'waiting';
        }
        syncCardAndModal(card, currentStatus);
    }

    // --- 2. Change Handlers ---
    function handleStatusChange(selectElement, isFromModal) {
        const card = isFromModal ? currentOpenCard : selectElement.closest('.order_card');
        if (!card) return;

        const previousStatus = card.getAttribute('data-status'); 
        const newStatus = selectElement.value;

        if (newStatus === 'cancelled') {
            pendingSelectElement = selectElement;
            pendingPreviousValue = previousStatus;
            pendingCard = card;
            document.getElementById('cancelConfirmModal').style.display = 'flex';
        } else {
            syncCardAndModal(card, newStatus);
        }
    }

    function updateOrderStatus(selectElement) { handleStatusChange(selectElement, false); }
    function updateModalStatus(selectElement) { handleStatusChange(selectElement, true); }

    // --- 3. Custom Cancel Modal Logic ---
    function handleCancelDecision(isConfirmed) {
        document.getElementById('cancelConfirmModal').style.display = 'none';
        
        if (!isConfirmed) {
            pendingSelectElement.value = pendingPreviousValue;
            updateSelectColors(pendingSelectElement, pendingPreviousValue);
        } else {
            syncCardAndModal(pendingCard, 'cancelled');
        }
        
        pendingSelectElement = null;
        pendingPreviousValue = null;
        pendingCard = null;
    }

    // --- 4. Open/Close Modals ---
    function openDetailsModal(btnElement) {
        currentOpenCard = btnElement.closest('.order_card');
        const isPaid = currentOpenCard.getAttribute('data-is-paid');
        const cardStatus = currentOpenCard.getAttribute('data-status');

        const modalContainer = document.getElementById('modalStatusContainer');
        modalContainer.setAttribute('data-is-paid', isPaid);
        
        const modalSelect = modalContainer.querySelector('.status_select');
        Array.from(modalSelect.options).forEach(opt => {
            if (isPaid === 'true') opt.disabled = false;
            else opt.disabled = (opt.value !== 'waiting' && opt.value !== 'cancelled');
        });

        syncCardAndModal(currentOpenCard, cardStatus);
        document.getElementById('orderDetailsModal').style.display = 'flex';
    }

    function closeDetailsModal() { document.getElementById('orderDetailsModal').style.display = 'none'; }
    function openPaymentModal() { document.getElementById('paymentDetailsModal').style.display = 'flex'; }
    function closePaymentModal() { document.getElementById('paymentDetailsModal').style.display = 'none'; }

    // --- 5. Page Load & Live Filtering ---
    document.addEventListener("DOMContentLoaded", function() {
        const orderCards = document.querySelectorAll('.order_card');
        const emptyState = document.querySelector('.empty_state_container');
        const emptyMessage = document.querySelector('.empty_message');
        const placeholdersWrapper = document.getElementById('placeholder_cards');

        // Apply rules to cards
        orderCards.forEach(card => {
            const select = card.querySelector('.status_select');
            if(select) card.setAttribute('data-status', select.value);
            applySafetyRules(card);
        });

        document.querySelectorAll('.view_details_btn').forEach(btn => {
            btn.addEventListener('click', function() { openDetailsModal(this); });
        });

        // Smart Filtering Logic
        function runFilter(filterValue) {
            let visibleCount = 0;
            
            // If the wrapper is set to display: none, pretend there are 0 cards!
            const isWrapperHidden = placeholdersWrapper && window.getComputedStyle(placeholdersWrapper).display === 'none';

            if (!isWrapperHidden) {
                orderCards.forEach(card => {
                    const cardStatus = card.getAttribute('data-status');
                    if (filterValue === 'all' || cardStatus === filterValue) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Handle the Empty State Box
            if (emptyState && emptyMessage) {
                if (visibleCount === 0) {
                    emptyState.style.display = 'block';
                    if (filterValue === 'all') {
                        emptyMessage.innerText = "No orders yet... :c";
                    } else {
                        emptyMessage.innerText = "No orders found for this status.";
                    }
                } else {
                    emptyState.style.display = 'none';
                }
            }
        }

        // Run the filter immediately on page load to fix the initial display!
        const activeBtn = document.querySelector('.filter_btn.active');
        if (activeBtn) runFilter(activeBtn.getAttribute('data-filter'));

        // Attach clicks to filter buttons
        const filterButtons = document.querySelectorAll('.filter_btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                runFilter(this.getAttribute('data-filter'));
            });
        });
    });
</script>
@endsection