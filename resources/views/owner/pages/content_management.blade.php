@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/content_management/content_management.css'])
@vite(['resources/css/owner/pages/content_management/home_page_content.css'])
@vite(['resources/css/owner/pages/content_management/products_page_content.css'])
@vite(['resources/css/owner/pages/content_management/order_template.css'])
@endsection

@section('content')
    <h1 class="page_header">Content Management</h1>
    <div class="content_pages">
        <button class="content_option" data-section="contentHomePage">Home Page</button>
        <button class="content_option" data-section="products">Products Page</button>
        <button class="content_option" data-section="ordertemplate">Order Template</button>
        <button class="content_option" data-section="faqs">FAQs</button>
    </div>

    <hr>

    {{-- ===================== HOME PAGE ===================== --}}
    <section class="content_section" id="contentHomePage">

        <div class="content_homepage_images">
            <h2>Home Page Images</h2>
            <p class="empty_home_images">No home page images uploaded yet.</p>
            <div class="home_images_uploads"></div>
            <div class="home_edit">
                <button class="edit_home_images" id="editHomeImage">Edit Images</button>
            </div>

            <div class="edit_home_images_modal" id="editHomeImagesModal">
                <div class="edit_home_image_modal_box">
                    <h3>Edit Home Page Images</h3>
                    <p class="home_image_description">These images will be shown on the main site's Home Page slide show.</p>
                    <div class="home_image_grid" id="homeImageGrid"></div>
                    <p class="home_image_counter" id="homeImageCounter">0 / 5 images uploaded</p>
                    <div class="home_image_actions">
                        <button class="home_image_cancel" id="cancelUpload">Cancel</button>
                        <button class="home_image_save">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="content_homepage_product">
            <h2>Product Sample Images</h2>
            <p class="empty_sample_images">No product sample images uploaded yet.</p>
            <div class="product_samples_wrapper"></div>
            <button class="add_sample">Add Sample</button>

            <div class="add_sample_modal" id="addSampleModal">
                <div class="add_sample_modal_box">
                    <h2>Add Sample Products</h2>
                    <p class="add_sample_description">Display your product samples!</p>
                    <h3>Sample Title</h3>
                    <input type="text" id="sampleNameInput" placeholder="Enter product sample name" maxlength="60"/>
                    <p class="sample_name_error" id="sampleNameError">Sample name is required.</p>
                    <h3>Sample Images</h3>
                    <div class="sample_image_grid" id="sampleImageGrid"></div>
                    <p class="sample_images_error" id="sampleImageError">At least one image is required.</p>
                    <p class="sample_image_counter" id="sampleImageCounter">0 / 5 images selected</p>
                    <div class="sample_image_actions">
                        <div class="left_sample_actions"></div>
                        <div class="right_sample_actions">
                            <button id="cancelSampleUpload">Cancel</button>
                            <button id="saveSampleUpload">Save</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="delete_confirm_modal" id="deleteConfirmModal">
                <div class="delete_confirm_box">
                    <p>Do you wish to delete the selected product sample?</p>
                    <button id="confirmDeleteSample">Delete Sample</button>
                    <small>This process cannot be undone</small>
                </div>
            </div>
        </div>

    </section>

{{-- ===================== PRODUCTS ===================== --}}
    <section class="content_section" id="products">
        <div class="products_page">
            <h1>Products</h1>
            <div id="products_container">
                <p id="products_no_items_text">No products uploaded.</p>
            </div>
            <button id="products_add_btn" class="products_btn_primary">Add Product</button>
        </div>

        <div id="products_modal" class="products_modal">
            <div class="products_modal_content">
                <h2 id="products_modal_title">Add Product</h2>
                <p>Display your products and its price list!</p>
                <label>Product Name</label>
                <input type="text" id="products_title_input" class="products_title_input" placeholder="Enter product name" maxlength="60"/>
                <span id="products_title_error" class="products_error_message"></span>
                <label>Product Description</label>
                <textarea id="products_description_input" class="products_description_input" placeholder="Enter product description (optional)" maxlength="200" rows="3"></textarea>
                <label>Main Cover Image</label>
                <div id="products_main_image_wrapper">
                    <div id="products_main_add_box" class="products_add_box">+</div>
                    <img id="products_main_image_preview" alt="Main Image Preview" style="display:none" />
                    <button id="products_remove_main_image_btn" class="products_button_remove" style="display:none">Remove</button>
                </div>
                <span id="products_cover_error" class="products_error_message"></span>
                <label>Price List Images</label>
                <div id="products_price_images_wrapper" class="products_price_wrapper"></div>
                <span id="products_prices_error" class="products_error_message"></span>
                <label>Image Notes</label>
                <div id="products_image_notes_wrapper" class="products_image_notes_wrapper"></div>
                <div class="products_modal_actions">
                    <button id="products_delete_btn" class="products_button_delete" style="display:none">Delete</button>
                    <div class="products_modal_actions_right">
                        <button id="products_cancel_btn" class="products_button_cancel">Cancel</button>
                        <button id="products_save_btn" class="products_button_save">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="products_delete_confirm_modal" class="delete_products_modal">
            <div class="delete_products_modal_content">
                <p>Do you wish to delete the selected product?</p>
                <button id="products_confirm_delete_btn" class="products_button_confirm_delete">Delete Product</button>
                <p class="products_small_text">This process cannot be undone</p>
            </div>
        </div>
    </section>

  {{-- ===================== ORDER TEMPLATE ===================== --}}
    <section class="content_section" id="ordertemplate">

        {{-- Order Templates --}}
        <h2 class="order_template_header">Order Template</h2>
        <p class="empty_order_template" id="emptyOrderTemplate">No order templates made yet.</p>
        <div id="productCardsContainer" class="product_cards_container"></div>
        <button id="open_add_template_btn" class="add_template_button">Add New Template</button>

        {{-- Add / Edit Template Modal --}}
        <div class="add_template_modal" id="templateModalOverlay">
            <div class="add_template_modal_box" id="templateModal">
                <h2 class="modal_title" id="templateModalTitle">Add New Template</h2>
                <p class="order_description">Sell your products!</p>

                <div class="modal_tabs">
                    <button class="modal_tab modal_tab_active" id="tab_details" type="button">Product Details</button>
                    <button class="modal_tab tab_locked" id="tab_pricing" type="button">Pricing</button>
                    <button class="modal_tab tab_locked" id="tab_additional_fees" type="button">Additional Fees</button>
                </div>

                {{-- ---- Panel: Product Details ---- --}}
                <div class="modal_panel" id="panel_details">
                    <div class="product_information">
                        <label for="productName">Product Name</label>
                        <div class="select_wrapper">
                            <select id="productName" class="product_name_select"></select>
                        </div>
                    </div>
                    <div id="productOptionsWrapper"></div>
                </div>

                {{-- ---- Panel: Pricing ---- --}}
                <div class="modal_panel modal_panel_hidden" id="panel_pricing">
                    <div class="pricing_combinations_header">
                        <span class="pricing_col_combo">Combination</span>
                        <span class="pricing_col_price">Price (₱)</span>
                    </div>
                    <div id="pricingCombinations" class="pricing_combinations"></div>
                </div>

                {{-- ---- Panel: Additional Fees ---- --}}
                <div class="modal_panel modal_panel_hidden" id="panel_additional_fees">

                    {{-- Bulk Discount --}}
                    <div class="product_discount">
                        <div class="product_discount_checkbox">
                            <label class="product_discount_header">Apply Bulk Discount?</label>
                            <input type="checkbox" id="applyProductDiscount" class="apply_discount_checkbox">
                        </div>
                        <div class="discount_header_row hidden" id="discountHeaderRow">
                            <span class="discount_col_label">Min. Quantity</span>
                            <span class="discount_col_label">Reduction per Piece</span>
                            <span class="discount_col_actions_spacer"></span>
                        </div>
                        <div id="discountRowsWrapper" class="discount_rows_wrapper hidden"></div>
                    </div>

                    {{-- Minimum Order --}}
                    <div class="product_min_order">
                        <div class="product_min_order_checkbox">
                            <label>Apply Minimum Order?</label>
                            <input type="checkbox" id="applyMinOrder" class="apply_discount_checkbox">
                        </div>
                        <div id="minOrderWrapper" class="min_order_wrapper hidden">
                            <div class="min_order_field">
                                <label for="minOrderQty">Minimum Quantity</label>
                                <input type="text" id="minOrderQty" inputmode="numeric" placeholder="e.g. 50" class="min_order_input">
                            </div>
                        </div>
                    </div>

                    {{-- Layout Fee --}}
                    <div class="product_layout_fee">
                        <div class="product_layout_fee_checkbox">
                            <label>Apply Layout Fee?</label>
                            <input type="checkbox" id="applyLayoutFee" class="apply_discount_checkbox">
                        </div>
                        <div id="layoutFeeWrapper" class="layout_fee_wrapper hidden">
                            <div class="layout_fee_field">
                                <label for="layoutFeeAmount">Layout Fee (₱)</label>
                                <input type="text" id="layoutFeeAmount" inputmode="decimal" placeholder="0.00" class="layout_fee_input">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---- Action Bar ---- --}}
                <div class="product_order_actions">
                    <button class="delete_product_modal_btn btn_hidden" id="deleteProductBtn" type="button">Delete</button>
                    <div class="product_order_actions_right">
                        <button class="cancel_product" type="button">Cancel</button>
                        <button class="next_product" id="nextBtn" type="button">Next →</button>
                        <button class="save_product btn_hidden" type="button">Save</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Modal --}}
        <div class="detail_modal" id="detailModalOverlay">
            <div class="detail_modal_box">
                <h2 class="detail_modal_name" id="detailProductName"></h2>
                <div id="detailOptionsContainer" class="detail_options_container"></div>
                <div class="detail_price_row">
                    <span class="detail_price_label">Price</span>
                    <span class="detail_price_value" id="detailPriceValue">—</span>
                </div>
                <div class="detail_actions">
                    <button class="detail_close_btn" id="detailCloseBtn" type="button">Close</button>
                </div>
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div class="modal_overlay" id="deleteTemplateModalOverlay">
            <div class="delete_confirm_box template_delete_box">
                <p class="delete_msg">Do you wish to delete the<br>selected template?</p>
                <button class="template_delete_btn_large" id="deleteConfirmProceedBtn" type="button">Delete Template</button>
                <small class="delete_subtext">This process cannot be undone</small>
            </div>
        </div>

        {{-- ---- Rush Fees ---- --}}
        <div class="rush_fees_section">
            <h2 class="rush_fees_section_header">Rush Fees</h2>

            {{-- Cards display --}}
            <p class="rush_fees_empty" id="rushFeesEmpty">No rush fees added yet.</p>
            <div class="rush_fees_cards_container" id="rushFeesCardsContainer"></div>

            <button class="add_rush_fee_btn" id="addRushFeeBtn" type="button">Add Rush Fee</button>

            {{-- Add / Edit Rush Fee Modal --}}
            <div class="rush_fees_modal" id="rushFeeModalOverlay">
                <div class="rush_fees_modal_box">
                    <h2 class="rush_modal_title" id="rushModalTitle">Add Rush Fee</h2>
                    <p class="rush_modal_description">Set the price range, timeframes, and fees.</p>

                    {{-- Price Range --}}
                    <div class="rush_range_section">
                        <label class="rush_field_label">Price Range Label</label>
                        <input type="text" id="rushRangeLabel" class="rush_range_label_input"
                               placeholder='e.g. "Below ₱3,000"' />

                        <div class="rush_range_amounts_row">
                            <input type="text" id="rushRangeMin" class="rush_range_amount_input" placeholder="Min (₱)" />
                            <span class="rush_range_separator">–</span>
                            <input type="text" id="rushRangeMax" class="rush_range_amount_input" placeholder="Max (₱, blank = ∞)" />
                        </div>
                    </div>

                    {{-- Timeframe rows --}}
                    <div class="rush_timeframes_section">
                        <div class="rush_tf_col_headers">
                            <span>Timeframe</span>
                            <span>% Added to Total</span>
                        </div>
                        <div id="rushTimeframeRows" class="rush_tf_rows"></div>
                        <button type="button" id="rushAddTimeframeBtn" class="rush_add_row_btn">Add Timeframe</button>
                    </div>

                    {{-- Actions --}}
                    <div class="rush_modal_actions">
                        <button class="rush_delete_modal_btn btn_hidden" id="rushDeleteBtn" type="button">Delete</button>
                        <div class="rush_modal_actions_right">
                            <button class="rush_cancel_btn" id="rushCancelBtn" type="button">Cancel</button>
                            <button class="rush_save_btn" id="rushSaveBtn" type="button">Save</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delete Confirmation Modal --}}
            <div class="modal_overlay" id="rushDeleteConfirmOverlay">
                <div class="delete_confirm_box">
                    <p class="delete_msg">Do you wish to delete<br>this rush fee?</p>
                    <button class="template_delete_btn_large" id="rushDeleteConfirmBtn" type="button">Delete Rush Fee</button>
                    <small class="delete_subtext">This process cannot be undone</small>
                    <button class="rush_delete_cancel_link" id="rushDeleteCancelBtn" type="button">Cancel</button>
                </div>
            </div>
        </div>

    </section>

@endsection

@vite('resources/js/owner/content_page/main_content_page.js')
@vite('resources/js/owner/content_page/edit_home_images_modal.js')
@vite('resources/js/owner/content_page/product_sample_modal.js')
@vite('resources/js/owner/content_page/products_page_content_refactored.js')
@vite('resources/js/owner/content_page/order_template.js')
@vite('resources/js/owner/content_page/rush_fees.js')