@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/content_management/contentmanagement.css'])
@endsection

@section('content')
    <h1 class="page_header">Content Management</h1>
    <div class="content_pages">
        <button class="content_option" data-section="homepage">Home Page</button>
        <button class="content_option" data-section="products">Products Page</button>
        <button class="content_option" data-section="ordertemplate">Order Template</button>
        <button class="content_option" data-section="faqs">FAQs</button>
    </div>

    {{-- HOME PAGE --}}
    <section class="content_section" id="contentHomePage">
        <div class="content_homepage_images">
            <h2>Home Page Images</h2>

            {{-- empty home page images --}}
            <p class="empty_home_images">No images uploaded yet.</p>

            {{-- uploaded images --}}
            <div class="home_images_uploads">
                <img src="" alt="Uploaded Image">
                <button class="delete_home_images">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#C83333"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>
                </button>
            </div>

            <div class="home_add">
                <button class="add_home_images">Add Image</button>
                <span class="home_images_count">0/5 images uploaded</span>
            </div>
        </div>

        <div class="content_homepage_product">
        </div>
    </section>
@endsection
