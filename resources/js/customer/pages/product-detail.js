/**
 * Product Detail Page Script
 * Displays product price images in a responsive FIFO 2-column grid
 * Features: Skeleton loaders, fade-in animations, responsive layout
 * Also manages the order modal for customer ordering
 */

// ================= IMPORTS =================
import { openOrderModal } from "./order_modal.js";
import Toast from "/resources/js/utils/toast.js";

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

        if (!productData) {
            throw new Error("Missing data-product attribute on container");
        }

        product = JSON.parse(productData);

        priceImages = product.price_images || [];

        // Set up event listeners
        setupEventListeners();

        // Render skeleton loaders
        renderSkeletonLoaders();

        // Load price images
        loadAndRenderPriceImages();

        // Enable Order Now button
        if (orderNowBtn) {
            orderNowBtn.disabled = false;
        }
    } catch (error) {
        console.error("Error initializing product detail:", error);
        gallery.innerHTML =
            '<div class="price_image_error_text">Unable to load product information. Check console for details.</div>';
    }
}

/**
 * Set up event listeners for navigation and buttons
 */
function setupEventListeners() {
    try {
        // Back button - FIRST priority
        if (backBtn) {
            backBtn.addEventListener("click", (e) => {
                e.preventDefault();
                window.history.back();
            });
        }
    } catch (backButtonError) {
        console.error("Error attaching back button listener:", backButtonError);
    }

    // Order Now button - SECOND priority (won't block back button if it fails)
    try {
        if (orderNowBtn) {
            orderNowBtn.addEventListener(
                "click",
                async (e) => {
                    e.preventDefault();

                    try {
                        const authMeta = document.querySelector(
                            'meta[name="user-authenticated"]',
                        );

                        const isAuthenticated =
                            authMeta?.getAttribute("content") === "true";

                        if (!isAuthenticated) {
                            Toast.warning(
                                "Please login or create an account to place an order",
                            );
                            setTimeout(() => {
                                window.location.href = "/login";
                            }, 1500);
                            return;
                        }

                        // Open order modal for this product
                        const productId =
                            container.getAttribute("data-product-id");
                        await openOrderModal(parseInt(productId));
                    } catch (modalError) {
                        console.error(
                            "Error in Order Now handler:",
                            modalError,
                        );
                        Toast.error(
                            "Error opening order modal. Please try again.",
                        );
                    }
                },
                { once: false },
            );
        }
    } catch (orderButtonError) {
        console.error(
            "Error attaching Order Now button listener:",
            orderButtonError,
        );
    }
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
        try {
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
                console.error("Failed to load price image:", img.src);
                wrapper.classList.add("error");
                wrapper.innerHTML =
                    '<div class="price_image_error_text">Image failed to load</div>';
            });

            // Set image source (construct path from storage)
            if (!image.image_path) {
                throw new Error(
                    "image_path is missing from price image object",
                );
            }

            const imagePath = image.image_path.startsWith("/")
                ? image.image_path
                : `/storage/${image.image_path}`;
            img.src = imagePath;

            wrapper.appendChild(img);
            imageElements.push({ wrapper, order: image.sort_order || index });
        } catch (itemError) {
            console.error("Error processing price image:", itemError);
        }
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
