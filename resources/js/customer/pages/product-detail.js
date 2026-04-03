/**
 * Product Detail Page Script
 * Displays product price images in a responsive FIFO 2-column grid
 * Features: Skeleton loaders, fade-in animations, responsive layout, centered images
 * Also manages the order modal for customer ordering
 */

// ================= IMPORTS =================
// Removed the broken order_modal import!
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
        const productData = container.getAttribute("data-product");

        if (!productData) {
            throw new Error("Missing data-product attribute on container");
        }

        product = JSON.parse(productData);
        priceImages = product.price_images || [];

        setupEventListeners();
        renderSkeletonLoaders();
        loadAndRenderPriceImages();

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
        if (backBtn) {
            backBtn.addEventListener("click", (e) => {
                e.preventDefault();
                window.history.back();
            });
        }
    } catch (backButtonError) {
        console.error("Error attaching back button listener:", backButtonError);
    }

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

                        // Require login before opening the modal!
                        if (!isAuthenticated) {
                            sessionStorage.setItem(
                                "auth_toast_message",
                                "Please login or create an account to place an order.",
                            );
                            window.location.href = "/login";
                            return;
                        }

                        // Open our new custom order modal
                        if (typeof window.openOrderModal === "function") {
                            window.openOrderModal();
                        } else {
                            console.error(
                                "openOrderModal function not found. Check if order_modal.js is loaded.",
                            );
                        }
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

function renderSkeletonLoaders() {
    gallery.innerHTML = "";
    const imageCount = priceImages.length || 4;
    for (let i = 0; i < imageCount; i++) {
        const skeletonWrapper = document.createElement("div");
        skeletonWrapper.className = "price_image_wrapper loading";
        skeletonWrapper.setAttribute("aria-hidden", "true");
        skeletonWrapper.style.setProperty("--index", i);
        gallery.appendChild(skeletonWrapper);
    }
}

async function loadAndRenderPriceImages() {
    if (!priceImages || priceImages.length === 0) {
        gallery.innerHTML =
            '<div class="price_image_error_text">No price images available for this product.</div>';
        return;
    }
    gallery.innerHTML = "";
    const imageElements = [];

    priceImages.forEach((image, index) => {
        try {
            const wrapper = document.createElement("div");
            wrapper.className = "price_image_wrapper";
            wrapper.style.setProperty("--index", index);

            const img = document.createElement("img");
            img.alt = `${product.name} - Price List ${index + 1}`;
            img.loading = "lazy";

            img.addEventListener("load", () => {
                wrapper.classList.remove("loading");
            });

            img.addEventListener("error", () => {
                console.error("Failed to load price image:", img.src);
                wrapper.classList.add("error");
                wrapper.innerHTML =
                    '<div class="price_image_error_text">Image failed to load</div>';
            });

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

    imageElements.sort((a, b) => a.order - b.order);

    imageElements.forEach((element, index) => {
        element.wrapper.style.setProperty("--index", index);
        gallery.appendChild(element.wrapper);
    });
}
