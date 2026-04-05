<div id="orderModal" class="order_modal_overlay">
    <div class="order_modal_box">
        <div class="order_modal_header">
            <h2 class="order_modal_title" id="dynamicModalTitle">Customize Your Order</h2>
            <button class="order_modal_close_btn" id="closeOrderModal" aria-label="Close order modal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="order_modal_form">
            <div class="order_modal_section">
                <h3 class="order_modal_section_title">Main Drive Link</h3>
                <input type="text" id="generalDriveLink" class="order_modal_drive_input" placeholder="Paste your main Google Drive folder link here..." required>
            </div>

            <div class="order_modal_price_divider"></div>

            <div class="order_modal_section" style="background: #fffcf8; padding: 20px; border-radius: 16px; border: 2px dashed #dcbae6;">
                <h3 class="order_modal_section_title">Add to Order</h3>

                <div id="dynamicOptionsContainer" class="order_modal_options_container" style="margin-bottom: 15px;"></div>

                <div class="file_spec_fields" style="margin-bottom: 15px; grid-template-columns: repeat(2, 1fr);">
                    <div class="file_spec_field">
                        <label class="file_spec_label">Quantity <span class="label_required">*</span></label>
                        <input type="number" id="itemQuantity" class="file_spec_input" min="1" value="1">
                    </div>

                    <div class="file_spec_field">
                        <label class="file_spec_label">Rush Processing</label>
                        <select id="rushFeeSelect" class="order_modal_select">
                            <option value="">Standard Processing (No Extra Fee)</option>
                        </select>
                    </div>
                </div>

                <div class="file_spec_field" style="margin-bottom: 15px;">
                    <label class="file_spec_label">Item Notes <span class="label_optional">(Design filename or instruction)</span></label>
                    <input type="text" id="itemFileName" class="file_spec_input" placeholder="e.g., front_logo.png, matte finish">
                </div>

                <button type="button" id="addItemBtn" class="add_file_spec_btn" style="width: 100%; justify-content: center; margin-top: 10px;">
                    <span>+</span> Add Item to Cart
                </button>
            </div>

            <div class="order_modal_section">
                <h3 class="order_modal_section_title">Your Persistent Cart</h3>
                <div id="cartItemsContainer" class="file_specs_container">
                    <p style="text-align: center; color: #666; font-family: 'Coolvetica', sans-serif; font-size: 14px; margin-top: 10px;" id="emptyCartMsg">Your cart is empty. Add an item above.</p>
                </div>
            </div>

            <div class="order_modal_price_section">
                <div class="order_modal_price_row total">
                    <span class="order_modal_price_label">FULL TOTAL <span class="price_sparkle">✨</span></span>
                    <span class="order_modal_price_value" id="grandTotalDisplay">₱0.00</span>
                </div>
            </div>

            <div id="orderPlacementFeedback" class="order_modal_message error order_modal_message_shortage" hidden aria-live="polite" tabindex="-1"></div>

            <button type="button" class="order_modal_submit_btn" id="submitMasterOrderBtn">
                <span id="submitBtnText">Place Order</span>
            </button>
        </div>
    </div>
</div>