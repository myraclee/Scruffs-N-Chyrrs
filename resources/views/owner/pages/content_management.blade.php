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
        <textarea id="products_description_input" class="products_description_input" placeholder="Enter product description (optional)" rows="3" style="width: 100%; padding: 20px; padding-bottom: 30px; border-radius: 4px; border: 1px solid #ddd; font-family: Arial, sans-serif;"></textarea>

        <label>Main Cover Image:</label>
        <div id="products_main_image_wrapper">
            <div id="products_main_add_box" class="products_add_box">+</div>
            <img id="products_main_image_preview" alt="Main Image Preview" style="display:none" />
            <button id="products_remove_main_image_btn" class="products_button_remove" style="display:none">Remove</button>
        </div>
        <span id="products_cover_error" class="products_error_message"></span>

        <label>Price List Images:</label>
        <div id="products_price_images_wrapper" class="products_price_wrapper"></div>
        <span id="products_prices_error" class="products_error_message"></span>

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
        <h2 style="font-family: 'SuperDream', sans-serif; color: #682C7A; font-size: 40px; margin-bottom: 20px;">Order Template</h2>

        <div id="template_list_container" class="template_list_container">
            <div class="template_table_wrapper">
                <table class="template_table">
                    <thead>
                        <tr>
                            <th>Product Category</th>
                            <th>Lamination Options</th>
                            <th>Options Label</th>
                            <th>Options Selection</th>
                            <th>Discount Description</th>
                            <th>Discount Rate</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Stickers</td>
                            <td>Matte, Glossy, Glitter, Holo Rainbow, Holo Broken Glass</td>
                            <td>Sticker Type</td>
                            <td>Die-Cut, Kiss-Cut</td>
                            <td>5 peso per sheet for 7 sheets and above</td>
                            <td>- Php 5 / qty >= 7</td>
                            <td class="action_cells">
                                <button class="template_edit_btn" onclick="openTemplateModal(true)">Edit</button>
                                <button class="template_delete_btn" onclick="openDeleteTemplateModal()">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            </div>

        <button id="open_add_template_btn" class="primary_button" onclick="openTemplateModal(false)" style="margin-top: 20px;">Add New Template</button>

        <div class="modal_overlay" id="templateModalOverlay">
            <div class="template_modal" id="templateModal">
                <h2 id="templateModalTitle" class="modal_title">Add New Template</h2>
                <p class="order_description">Sell your products!</p>

                <div class="template_scroll_area">
                    <div class="input_group">
                        <label>Product Name</label>
                        <input type="text" id="tempCategory" class="text_input">
                    </div>

                    <div class="input_group">
                        <label>Product Options (1)</label>
                        <input type="text" id="tempOpt1Label" class="text_input">
                    </div>

                    <div class="option_list">
                        <div class="option_header">
                            <h3 class="small_option">Option Selection</h3>
                            <h3 class="small_price">Price (Php)</h3>
                        </div>

                        <div class="option_inputs">
                            <input type="text" id="productOptions"></input>
                            <input type="text" id="productPrices"></input>
                            <div class="option_actions">
                                <svg xmlns="http://www.w3.org/2000/svg" id="deleteProductOptions" height="20px" viewBox="0 -960 960 960" width="20px" fill="#c83333"><path d="m339-288 141-141 141 141 51-51-141-141 141-141-51-51-141 141-141-141-51 51 141 141-141 141 51 51ZM480-96q-79 0-149-30t-122.5-82.5Q156-261 126-331T96-480q0-80 30-149.5t82.5-122Q261-804 331-834t149-30q80 0 149.5 30t122 82.5Q804-699 834-629.5T864-480q0 79-30 149t-82.5 122.5Q699-156 629.5-126T480-96Zm0-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" id="addProductOptions" height="20px" viewBox="0 -960 960 960" width="20px" fill="#682c7a"><path d="M444-288h72v-156h156v-72H516v-156h-72v156H288v72h156v156Zm36.28 192Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z"/></svg>
                            </div>
                    </div>

                    <div class="input_group">
                        <label>Description</label>
                        <input type="text" id="tempDesc" class="text_input">
                    </div>

                    <div class="input_group checkbox_group">
                        <label>Discount <input type="checkbox" id="tempDiscountCheck" onchange="toggleDiscountFields()"></label>
                    </div>

                    <h4 class="sub_label">Discount Rate</h4>
                    <div class="discount_fields" id="discountFields">
                        <div class="input_group" style="text-align: center;">
                            <label class="small_label">Discount</label>
                            <input type="number" id="tempDiscountVal" class="number_input tiny_input">
                        </div>
                        <div class="input_group" style="text-align: center;">
                            <label class="small_label">Per Quantity</label>
                            <input type="number" id="tempDiscountQty" class="number_input tiny_input">
                        </div>
                    </div>

                    <div class="modal_actions">
                        <button class="cancel_btn" onclick="closeTemplateModals()">Cancel</button>
                        <button class="save_btn" onclick="closeTemplateModals()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal_overlay" id="deleteTemplateModalOverlay">
            <div class="delete_confirm_box template_delete_box">
                <p class="delete_msg">Do you wish to delete the<br>selected template?</p>
                <button class="template_delete_btn_large" onclick="closeTemplateModals()">Delete Template</button>
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