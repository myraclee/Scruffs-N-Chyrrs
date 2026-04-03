<div id="orderModal" class="order_modal_overlay">
    <div class="order_modal_box">
        <div style="display: flex; gap: 10px; padding: 24px 32px 0; overflow-x: auto; scrollbar-width: none;">
            <button class="order_modal_category_tab" style="background: #682c7a; color: white; padding: 8px 20px; border-radius: 20px; font-family: 'Coolvetica', sans-serif; border: none; cursor: pointer; white-space: nowrap; font-size: 15px;">Stickers</button>
            <button class="order_modal_category_tab" style="background: #dcbae6; color: #682c7a; padding: 8px 20px; border-radius: 20px; font-family: 'Coolvetica', sans-serif; border: none; cursor: pointer; white-space: nowrap; font-size: 15px;">Button Pins</button>
            <button class="order_modal_category_tab" style="background: #dcbae6; color: #682c7a; padding: 8px 20px; border-radius: 20px; font-family: 'Coolvetica', sans-serif; border: none; cursor: pointer; white-space: nowrap; font-size: 15px;">Photocards</button>
            <button class="order_modal_category_tab" style="background: #dcbae6; color: #682c7a; padding: 8px 20px; border-radius: 20px; font-family: 'Coolvetica', sans-serif; border: none; cursor: pointer; white-space: nowrap; font-size: 15px;">Business Cards</button>
            <button class="order_modal_category_tab" style="background: #dcbae6; color: #682c7a; padding: 8px 20px; border-radius: 20px; font-family: 'Coolvetica', sans-serif; border: none; cursor: pointer; white-space: nowrap; font-size: 15px;">Posters</button>
        </div>

        <div class="order_modal_header">
            <h2 class="order_modal_title" id="dynamicModalTitle">Sticker Orders</h2>
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
                
                <div class="file_spec_fields" style="margin-bottom: 15px;">
                    <div class="file_spec_field">
                        <label class="file_spec_label">Type <span class="label_required">*</span></label>
                        <select id="itemType" class="order_modal_select">
                            <option value="Die-Cut">Die-Cut</option>
                            <option value="Kiss-Cut">Kiss-Cut</option>
                        </select>
                    </div>
                    
                    <div class="file_spec_field">
                        <label class="file_spec_label">Lamination <span class="label_required">*</span></label>
                        <select id="itemLamination" class="order_modal_select">
                            <option value="Matte">Matte</option>
                            <option value="Glossy">Glossy</option>
                            <option value="Holographic">Holographic</option>
                        </select>
                    </div>

                    <div class="file_spec_field">
                        <label class="file_spec_label">Quantity</label>
                        <input type="number" id="itemQuantity" class="file_spec_input" min="1" value="1">
                    </div>
                </div>

                <div class="file_spec_field" style="margin-bottom: 15px;">
                    <label class="file_spec_label">Specific File Name <span class="label_optional">(Inside your G-Drive)</span></label>
                    <input type="text" id="itemFileName" class="file_spec_input" placeholder="e.g., cute_cat_sticker.png">
                </div>

                <input type="hidden" id="currentItemCategory" value="Stickers">
                <input type="hidden" id="currentItemBasePrice" value="45.00">

                <button type="button" id="addItemBtn" class="add_file_spec_btn" style="width: 100%; justify-content: center; margin-top: 10px;">
                    <span>+</span> Add Design to List
                </button>
            </div>

            <div class="order_modal_section">
                <h3 class="order_modal_section_title">Your Items</h3>
                <div id="cartItemsContainer" class="file_specs_container">
                    <p style="text-align: center; color: #666; font-family: 'Coolvetica', sans-serif; font-size: 14px; margin-top: 10px;" id="emptyCartMsg">No items added yet. Build your order above!</p>
                </div>
            </div>

            <div class="order_modal_price_section">
                <div class="order_modal_section" style="margin-bottom: 15px;">
                    <label class="order_modal_label">Rush Processing</label>
                    <select id="rushFeeSelect" class="order_modal_select" style="background: white;">
                        <option value="0">Standard Processing (No Extra Fee)</option>
                        <option value="150">24-Hour Rush (+₱150.00)</option>
                    </select>
                </div>

                <div class="order_modal_price_divider"></div>

                <div class="order_modal_price_row total">
                    <span class="order_modal_price_label">FULL TOTAL <span class="price_sparkle">✨</span></span>
                    <span class="order_modal_price_value" id="grandTotalDisplay">₱0.00</span>
                </div>
            </div>

            <button type="button" class="order_modal_submit_btn" id="submitMasterOrderBtn">
                <span id="submitBtnText">Place Order</span>
            </button>
        </div>
    </div>
</div>