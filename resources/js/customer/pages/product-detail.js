/**
 * Product Detail Page Script
 * Displays product price images in a responsive FIFO 2-column grid
 * Features: Skeleton loaders, fade-in animations, responsive layout
 */

// ================= STATE & ELEMENTS =================

let product = null;
let priceImages = [];
const gallery = document.getElementById("priceGallery");
const backBtn = document.getElementById("backBtn");
const orderNowBtn = document.getElementById("orderNowBtn");
const container = document.querySelector(".product_detail_container");

// ================= INITIALIZATION =================

document.addEventListener("DOMContentLoaded", () => {
    initializeProductDetail();
});

/**
 * Initialize the product detail page
 */
function initializeProductDetail() {
    try {
        // Get product data from data attribute
        const productData = container.getAttribute("data-product");
        product = JSON.parse(productData);
        priceImages = product.price_images || [];

        console.log("Product loaded:", product);
        console.log("Price images:", priceImages);

        // Set up event listeners
        setupEventListeners();

        // Render skeleton loaders
        renderSkeletonLoaders();

        // Load price images
        loadAndRenderPriceImages();
    } catch (error) {
        console.error("Error initializing product detail:", error);
        gallery.innerHTML =
            '<div class="price_image_error_text">Unable to load product information.</div>';
    }
}

/**
 * Set up event listeners for navigation and buttons
 */
function setupEventListeners() {
    // Back button
    backBtn.addEventListener("click", (e) => {
        e.preventDefault();
        window.history.back();
    });

    // Breadcrumb link (already navigates via href, so no extra listener needed)
    // Order Now button remains disabled as it's a placeholder
}

/**
 * Render skeleton loaders for all price images
 * Creates a 2-column grid with placeholder elements
 */
function renderSkeletonLoaders() {
    gallery.innerHTML = "";

    const imageCount = priceImages.length || 4; // Minimum 4 skeletons for visual consistency

    for (let i = 0; i < imageCount; i++) {
        const skeletonWrapper = document.createElement("div");
        skeletonWrapper.className = "price_image_wrapper loading";
        skeletonWrapper.setAttribute("aria-hidden", "true");
        gallery.appendChild(skeletonWrapper);
    }
}

/**
 * Load and render price images from the product data
 * Implements FIFO layout: 1st image column 1, 2nd image column 2, 3rd image column 1, etc.
 */
async function loadAndRenderPriceImages() {
    if (!priceImages || priceImages.length === 0) {
        gallery.innerHTML =
            '<div class="price_image_error_text">No price images available for this product.</div>';
        return;
    }

    gallery.innerHTML = "";

    // Create array to track grid positions
    const imageElements = [];

    // Create all image elements first
    priceImages.forEach((image, index) => {
        const wrapper = document.createElement("div");
        wrapper.className = "price_image_wrapper loading";
        wrapper.style.animationDelay = `${index * 0.1}s`;

        const img = document.createElement("img");
        img.alt = `${product.name} - Price List ${index + 1}`;
        img.loading = "lazy";

        // Handle image loading
        img.addEventListener("load", () => {
            wrapper.classList.remove("loading");
            img.style.animation = "fadeIn 0.6s ease both";
        });

        img.addEventListener("error", () => {
            wrapper.classList.add("error");
            wrapper.innerHTML =
                '<div class="price_image_error_text">Image failed to load</div>';
        });

        // Set image source (construct path from storage)
        const imagePath = image.image_path.startsWith("/")
            ? image.image_path
            : `/storage/${image.image_path}`;
        img.src = imagePath;

        wrapper.appendChild(img);
        imageElements.push({ wrapper, order: image.sort_order || index });
    });

    // Sort by sort_order if available
    imageElements.sort((a, b) => a.order - b.order);

    // Append to gallery in FIFO order (left, right, left, right, ...)
    imageElements.forEach((element) => {
        gallery.appendChild(element.wrapper);
    });
}

/**
 * Handle image loading with proper error states
 */
function handleImageLoad(img, wrapper) {
    wrapper.classList.remove("loading");
    img.style.animation = "fadeIn 0.6s ease both";
}

function handleImageError(wrapper) {
    wrapper.classList.add("error");
    wrapper.innerHTML =
        '<div class="price_image_error_text">Failed to load image</div>';
}
