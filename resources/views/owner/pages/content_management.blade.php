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
    {{-- HOME PAGE --}}
    <section class="content_section" id="contentHomePage">
        <div class="content_homepage_images">
            <h2>Home Page Images</h2>
            <p class="empty_home_images">No home page images uploaded yet.</p>
            <div class="home_images_uploads"></div>

            <div class="home_edit">
                <button class="edit_home_images" id="editHomeImage">Edit Images</button>
            </div>

            {{-- ADD HOME IMAGES MODAL --}}
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

            {{-- ADD SAMPLE MODAL --}}
            <div class="add_sample_modal" id="addSampleModal">
                <div class="add_sample_modal_box">
                    <h2>Add Sample Products</h2>
                    <p class="add_sample_description">Display your product samples!</p>
                    <h3>Sample Title</h3>
                    <input type="text" id="sampleNameInput" placeholder="Enter product sample name" maxlength="30"/>
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

            {{-- DELETE SAMPLE CONFIRMATION MODAL --}}
            <div class="delete_confirm_modal" id="deleteConfirmModal">
                <div class="delete_confirm_box">
                    <p>Do you wish to delete the selected product sample?</p>
                    <button id="confirmDeleteSample">Delete Sample</button>
                    <small>This process cannot be undone</small>
                </div>
            </div>
        </div>
    </section>

    <section class="content_section" id="products">
        <div class="products_page">
            <h1>Products</h1>
            <div id="products_container">
                <p id="products_no_items_text">No products uploaded.</p>
            </div>
            <button id="products_add_btn" class="products_btn_primary">Add Product</button>
        </div>

        <!-- Add/Edit Product Modal -->
        <div id="products_modal" class="products_modal">
            <div class="products_modal_content">
                <h2 id="products_modal_title">Add Product</h2>
                <p>Display your products and its price list!</p>

                <label>Product Name</label>
                <input type="text" id="products_title_input" class="products_title_input" placeholder="Enter product name" />
                <span id="products_title_error" class="products_error_message"></span>

                <label>Product Description</label>
                <textarea id="products_description_input" class="products_description_input" placeholder="Enter product description (optional)" rows="3"></textarea>

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

        <!-- Delete Confirmation Modal -->
        <div id="products_delete_confirm_modal" class="delete_products_modal">
            <div class="delete_products_modal_content">
                <p>Do you wish to delete the selected product?</p>
                <button id="products_confirm_delete_btn" class="products_button_confirm_delete">Delete Product</button>
                <p class="products_small_text">This process cannot be undone</p>
            </div>
        </div>

        <!-- Price Images Viewer Modal -->
        <div id="products_price_viewer_modal" class="products_modal">
            <div class="products_modal_content">
                <h3 id="products_viewer_title"></h3>
                <button id="products_prev_price_image" class="products_carousel_btn">&lt;</button>
                <img id="products_price_viewer_image" src="" alt="Price Image" />
                <button id="products_next_price_image" class="products_carousel_btn">&gt;</button>
            </div>
        </div>
    </section>

    <section class="content_section" id="ordertemplate">
        <h2 class="order_template_header">Order Template</h2>

        <!-- Product Cards (between header and button) -->
        <p class="empty_order_template" id="emptyOrderTemplate">No order templates made yet.</p>
        <div id="productCardsContainer" class="product_cards_container"></div>

        <button id="open_add_template_btn" class="add_template_button">Add New Template</button>

        <!-- Add / Edit Modal -->
        <div class="add_template_modal" id="templateModalOverlay">
            <div class="add_template_modal_box" id="templateModal">
                <h2 class="modal_title" id="templateModalTitle">Add New Template</h2>
                <p class="order_description">Sell your products!</p>

                <div class="modal_tabs">
                    <button class="modal_tab modal_tab_active" id="tab_details" type="button">Product Details</button>
                    <button class="modal_tab" id="tab_pricing" type="button">Pricing</button>
                </div>

                <!-- Product Details Panel -->
                <div class="modal_panel" id="panel_details">
                    <div class="product_information">
                        <label for="productName">Product Name</label>
                        <div class="select_wrapper">
                            <select id="productName" class="product_name_select"></select>
                        </div>
                    </div>

                    <div id="productOptionsWrapper"></div>
                </div>

                <!-- Pricing Panel -->
                <div class="modal_panel modal_panel_hidden" id="panel_pricing">
                    <div class="pricing_combinations_header">
                        <span class="pricing_col_combo">Combination</span>
                        <span class="pricing_col_price">Price</span>
                    </div>
                    <div id="pricingCombinations" class="pricing_combinations"></div>

                    <div class="product_discount">
                        <div class="product_discount_checkbox">
                            <label class="product_discount_header">Apply Bulk Discount?</label>
                            <input type="checkbox" id="applyProductDiscount" class="apply_discount_checkbox">
                        </div>
                        <div id="discountRowsWrapper" class="discount_rows_wrapper hidden"></div>
                    </div>
                </div>

                <div class="product_order_actions">
                    <button class="delete_product_modal_btn btn_hidden" id="deleteProductBtn" type="button">Delete</button>
                    <div class="product_order_actions_right">
                        <button class="cancel_product" type="button">Cancel</button>
                        <button class="save_product" type="button">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
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

        <!-- Delete Confirmation Modal -->
        <div class="modal_overlay" id="deleteTemplateModalOverlay">
            <div class="delete_confirm_box template_delete_box">
                <p class="delete_msg">Do you wish to delete the<br>selected template?</p>
                <button class="template_delete_btn_large" id="deleteConfirmProceedBtn" type="button">Delete Template</button>
                <small class="delete_subtext">This process cannot be undone</small>
            </div>
        </div>

    </section>

    {{-- FAQs SECTION --}}
    <section class="content_section" id="faqs">
        <h2 class="faqs_header">Frequently Asked Questions</h2>

        <p class="empty_faqs" id="emptyFaqsText">No FAQs available yet.</p>
        <div id="faqsContainer" class="faqs_container"></div>

        <button id="add_faq_btn" class="add_faq_button">Add FAQ</button>

        {{-- Add / Edit FAQ Modal --}}
        <div class="add_faq_modal" id="faqModalOverlay">
            <div class="add_faq_modal_box" id="faqModal">
                <h2 class="modal_title" id="faqModalTitle">Add New FAQ</h2>
                <p class="faq_description">Manage your Frequently Asked Questions</p>

                <label for="faqCategory">Category</label>
                <div class="select_wrapper">
                    <select id="faqCategory" class="faq_category_select">
                        <option value="">Select a category</option>
                        <option value="General Questions">General Questions</option>
                        <option value="Shipping & Orders">Shipping & Orders</option>
                        <option value="Customization & Finishes">Customization & Finishes</option>
                        <option value="Pricing & Discounts">Pricing & Discounts</option>
                    </select>
                </div>
                <span id="faqCategoryError" class="faq_error_message hidden">Category is required.</span>

                <label for="faqQuestion">Question</label>
                <input type="text" id="faqQuestion" class="faq_question_input" placeholder="Enter question" maxlength="255" />
                <span id="faqQuestionError" class="faq_error_message hidden">Question is required.</span>

                <label for="faqAnswer">Answer</label>
                <textarea id="faqAnswer" class="faq_answer_input" placeholder="Enter answer" rows="6"></textarea>
                <span id="faqAnswerError" class="faq_error_message hidden">Answer is required.</span>

                <div class="faq_modal_actions">
                    <button class="delete_faq_modal_btn btn_hidden" id="deleteFaqBtn" type="button">Delete</button>
                    <div class="faq_modal_actions_right">
                        <button class="cancel_faq" type="button">Cancel</button>
                        <button class="save_faq" type="button">Save</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div class="modal_overlay" id="deleteFaqModalOverlay">
            <div class="delete_confirm_box faq_delete_box">
                <p class="delete_msg">Do you wish to delete the<br>selected FAQ?</p>
                <button class="faq_delete_btn_large" id="deleteFaqConfirmBtn" type="button">Delete FAQ</button>
                <small class="delete_subtext">This process cannot be undone</small>
            </div>
        </div>

    </section>
@endsection

@vite('resources/js/owner/content_page/main_content_page.js')
@vite('resources/js/owner/content_page/edit_home_images_modal.js')
@vite('resources/js/owner/content_page/product_sample_modal.js')
@vite('resources/js/owner/content_page/products_page_content_refactored.js')
@vite('resources/js/owner/content_page/order_template.js')
@vite('resources/js/owner/content_page/faq_management.js')
@vite(['resources/css/owner/pages/content_management/faq_management.css'])