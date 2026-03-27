<!-- Order Modal - Customer Ordering Interface -->
<div id="orderModal" class="order_modal_overlay">
    <div class="order_modal_box">
        <!-- Header -->
        <div class="order_modal_header">
            <h2 class="order_modal_title">Customize Your Order</h2>
            <button class="order_modal_close_btn" id="closeOrderModal" aria-label="Close order modal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Product Info Section -->
        <div class="order_modal_product_info" id="productInfo">
            <div class="order_modal_product_image">
                <img id="productInfoImage" src="" alt="Product" loading="lazy" />
            </div>
            <div class="order_modal_product_details">
                <h3 id="productInfoName"></h3>
                <p id="productInfoDescription"></p>
            </div>
        </div>

        <!-- Form Content -->
        <form id="orderForm" class="order_modal_form">
            @csrf

            <!-- Options Section -->
            <div class="order_modal_section">
                <h3 class="order_modal_section_title">Customize Your Selection</h3>
                <div id="optionsContainer" class="order_modal_options_container">
                    <!-- Options will be rendered here by JavaScript -->
                </div>
            </div>

            <!-- Quantity Section -->
            <div class="order_modal_section">
                <label for="quantityInput" class="order_modal_label">
                    Quantity
                    <span class="order_modal_label_info" id="quantityInfo"></span>
                </label>
                <div class="order_modal_quantity_control">
                    <button class="order_modal_qty_btn" id="qtyDecrement" type="button" aria-label="Decrease quantity">
                        <span>−</span>
                    </button>
                    <input
                        type="number"
                        id="quantityInput"
                        name="quantity"
                        class="order_modal_qty_input"
                        min="1"
                        value="1"
                        required
                        aria-label="Quantity"
                    />
                    <button class="order_modal_qty_btn" id="qtyIncrement" type="button" aria-label="Increase quantity">
                        <span>+</span>
                    </button>
                </div>
            </div>

            <!-- Rush Fee Section -->
            <div class="order_modal_section">
                <label for="rushFeeSelect" class="order_modal_label">
                    Rush Fee (Optional)
                </label>
                <select id="rushFeeSelect" name="rush_fee_id" class="order_modal_select">
                    <option value="">Standard Processing</option>
                    <!-- Rush fee options will be rendered here by JavaScript -->
                </select>
            </div>

            <!-- Special Instructions Section -->
            <div class="order_modal_section">
                <label for="specialInstructions" class="order_modal_label">
                    Special Instructions (Optional)
                </label>
                <textarea
                    id="specialInstructions"
                    name="special_instructions"
                    class="order_modal_textarea"
                    placeholder="Any special requests or notes for your order..."
                    maxlength="1000"
                ></textarea>
                <small id="instructionsCount" class="order_modal_char_count">0/1000</small>
            </div>

            <!-- Price Breakdown Section -->
            <div class="order_modal_price_section">
                <h3 class="order_modal_price_title">Price Breakdown</h3>
                
                <div class="order_modal_price_row">
                    <span class="order_modal_price_label">Base Price</span>
                    <span class="order_modal_price_value" id="basePriceDisplay">$0.00</span>
                </div>

                <div class="order_modal_price_row" id="discountRow" style="display: none;">
                    <span class="order_modal_price_label">Bulk Discount</span>
                    <span class="order_modal_price_value discount" id="discountDisplay">-$0.00</span>
                </div>

                <div class="order_modal_price_row" id="layoutFeeRow" style="display: none;">
                    <span class="order_modal_price_label">Layout Fee</span>
                    <span class="order_modal_price_value" id="layoutFeeDisplay">$0.00</span>
                </div>

                <div class="order_modal_price_row" id="rushFeeRow" style="display: none;">
                    <span class="order_modal_price_label">Rush Fee</span>
                    <span class="order_modal_price_value" id="rushFeeDisplay">$0.00</span>
                </div>

                <div class="order_modal_price_divider"></div>

                <div class="order_modal_price_row total">
                    <span class="order_modal_price_label">Total</span>
                    <span class="order_modal_price_value" id="totalPriceDisplay">$0.00</span>
                </div>
            </div>

            <!-- Form Messages -->
            <div id="formMessage" class="order_modal_message" style="display: none;"></div>

            <!-- Submit Button -->
            <button type="submit" class="order_modal_submit_btn" id="submitOrderBtn">
                <span id="submitBtnText">Place Order</span>
                <span id="submitBtnSpinner" class="spinner" style="display: none;"></span>
            </button>
        </form>

        <!-- Unauthenticated User Message -->
        <div id="authMessage" class="order_modal_auth_message" style="display: none;">
            <p>You need to be logged in to place an order.</p>
            <div class="order_modal_auth_buttons">
                <a href="{{ route('login') }}" class="order_modal_auth_link">Login</a>
                <a href="{{ route('signup') }}" class="order_modal_auth_link">Create Account</a>
            </div>
        </div>
    </div>
</div>
