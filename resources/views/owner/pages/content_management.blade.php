@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/content_management/content_management.css'])
@vite(['resources/css/owner/pages/content_management/home_page_content.css'])
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
                    <input type="text" id="sampleNameInput" placeholder="Enter Product Name" maxlength="30"/>
                    <p class="sample_name_error" id="sampleNameError">Sample name is required.</p>

                    <h3 style="margin-top:15px;">Sample Images</h3>
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
@endsection

@vite('resources/js/owner/content_page/main_content_page.js')
@vite('resources/js/owner/content_page/edit_home_images_modal.js')
@vite('resources/js/owner/content_page/product_sample_modal.js')
