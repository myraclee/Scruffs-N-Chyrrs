/**
 * Products Page Content Management
 * Handles CRUD operations for products with API integration and persistence
 */

// ================= IMPORTS =================
import ProductAPI from "/resources/js/api/productApi.js";
import Toast from "/resources/js/utils/toast.js";
import FormState from "/resources/js/utils/formState.js";

// ================= ELEMENTS =================
const products_add_btn = document.getElementById("products_add_btn");
const products_modal = document.getElementById("products_modal");
const products_title_input = document.getElementById("products_title_input");
const products_description_input = document.getElementById(
    "products_description_input",
);

const products_main_add_box = document.getElementById("products_main_add_box");
const products_main_image_preview = document.getElementById(
    "products_main_image_preview",
);
const products_remove_main_image_btn = document.getElementById(
    "products_remove_main_image_btn",
);

const products_price_images_wrapper = document.getElementById(
    "products_price_images_wrapper",
);

const products_cancel_btn = document.getElementById("products_cancel_btn");
const products_save_btn = document.getElementById("products_save_btn");
const products_delete_btn = document.getElementById("products_delete_btn");

const products_container = document.getElementById("products_container");
const products_no_items_text = document.getElementById(
    "products_no_items_text",
);

const products_delete_confirm_modal = document.getElementById(
    "products_delete_confirm_modal",
);
const products_confirm_delete_btn = document.getElementById(
    "products_confirm_delete_btn",
);

const products_price_viewer_modal = document.getElementById(
    "products_price_viewer_modal",
);
const products_price_viewer_image = document.getElementById(
    "products_price_viewer_image",
);
const products_prev_price_image = document.getElementById(
    "products_prev_price_image",
);
const products_next_price_image = document.getElementById(
    "products_next_price_image",
);
const products_viewer_title = document.getElementById("products_viewer_title");

const products_title_error = document.getElementById("products_title_error");
const products_cover_error = document.getElementById("products_cover_error");
const products_prices_error = document.getElementById("products_prices_error");

// ================= STATE =================
let products_list = []; // Will be populated from API
let products_edit_id = null; // Store product ID for editing (null for new, number for edit)
let products_current_viewer_index = 0;
let products_has_cover = false;
let products_main_file = null; // Store the actual File object for upload
let products_price_files = []; // Store File objects for price images

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    await loadProductsFromAPI();
});

/**
 * Load all products from API on page load
 */
async function loadProductsFromAPI() {
    try {
        // Show loading state
        products_container.innerHTML =
            '<p style="color: #999;">Loading products...</p>';

        const products = await ProductAPI.getAllProducts();
        products_list = products;
        renderProducts();
    } catch (error) {
        console.error("Error loading products:", error);
        Toast.error("Failed to load products from database");
        products_container.innerHTML =
            '<p style="color: #999;">No products uploaded yet.</p>';
    }
}

// ================= COVER IMAGE HANDLING =================
products_main_add_box.addEventListener("click", () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/png, image/jpg, image/jpeg";

    input.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Store the actual file for upload
        products_main_file = file;

        // Show preview
        const reader = new FileReader();
        reader.onload = (event) => {
            products_main_image_preview.src = event.target.result;
            products_main_image_preview.style.display = "block";
            products_main_image_preview.style.objectFit = "cover";
        };
        reader.readAsDataURL(file);

        products_main_add_box.style.display = "none";
        products_remove_main_image_btn.style.display = "block";

        products_has_cover = true;
        products_cover_error.textContent = "";
    };

    input.click();
});

products_remove_main_image_btn.addEventListener("click", () => {
    products_main_image_preview.src = "";
    products_main_image_preview.style.display = "none";
    products_remove_main_image_btn.style.display = "none";
    products_main_add_box.style.display = "flex";

    products_main_file = null;
    products_has_cover = false;
});

// ================= PRICE IMAGES HANDLING =================
function createPriceBox(existingSrc = null, isExistingFromDB = false) {
    const wrapper = document.createElement("div");
    wrapper.className = "products_price_box_wrapper";
    wrapper.style.display = "flex";
    wrapper.style.flexDirection = "column";
    wrapper.style.alignItems = "center";

    const box = document.createElement("div");
    box.className = "products_price_box";
    box.style.flexDirection = "column";

    if (!existingSrc) {
        // Add new image box
        box.textContent = "+";
        box.addEventListener("click", () => {
            if (box.querySelector("img")) return;

            const input = document.createElement("input");
            input.type = "file";
            input.accept = "image/png, image/jpg, image/jpeg";

            input.onchange = (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // Store file for upload
                products_price_files.push(file);

                // Show preview
                const reader = new FileReader();
                reader.onload = (event) => {
                    box.innerHTML = "";
                    const img = document.createElement("img");
                    img.src = event.target.result;
                    img.style.objectFit = "cover";
                    box.appendChild(img);

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = "Remove";
                    removeBtn.className = "products_button_remove";
                    removeBtn.onclick = () => {
                        wrapper.remove();
                        products_price_files = products_price_files.filter(
                            (f) => f !== file,
                        );
                        createAddPriceBox();
                    };

                    wrapper.appendChild(box);
                    wrapper.appendChild(removeBtn);
                    // FIX: Append wrapper to container so price image preview is visible
                    products_price_images_wrapper.appendChild(wrapper);
                    products_prices_error.textContent = "";
                    createAddPriceBox();
                };
                reader.readAsDataURL(file);
            };

            input.click();
        });
    } else {
        // Existing image from database
        const img = document.createElement("img");
        img.src = existingSrc;
        img.style.objectFit = "cover";
        box.appendChild(img);

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "Remove";
        removeBtn.className = "products_button_remove";
        removeBtn.onclick = () => {
            wrapper.remove();
            createAddPriceBox();
        };
        wrapper.appendChild(box);
        wrapper.appendChild(removeBtn);
        return wrapper;
    }

    wrapper.appendChild(box);
    return wrapper;
}

function createAddPriceBox() {
    const hasAddBox = [...products_price_images_wrapper.children].some(
        (wrapper) =>
            wrapper.querySelector(".products_price_box").textContent === "+",
    );

    if (!hasAddBox) {
        products_price_images_wrapper.appendChild(createPriceBox());
    }
}

// ================= MODAL CONTROL =================
products_add_btn.addEventListener("click", () => {
    products_reset_modal();
    products_delete_btn.style.display = "none";
    products_modal.style.display = "flex";
});

window.addEventListener("click", (e) => {
    if (
        e.target === products_modal ||
        e.target === products_price_viewer_modal ||
        e.target === products_delete_confirm_modal
    ) {
        e.target.style.display = "none";
        if (e.target === products_modal) products_reset_modal();
    }
});

products_cancel_btn.addEventListener("click", () => {
    products_modal.style.display = "none";
    products_reset_modal();
});

// ================= SAVE PRODUCT =================
products_save_btn.addEventListener("click", async () => {
    let hasError = false;

    // Validation
    if (!products_title_input.value.trim()) {
        products_title_error.textContent = "Product title is required";
        hasError = true;
    } else {
        products_title_error.textContent = "";
    }

    if (!products_has_cover) {
        products_cover_error.textContent = "Cover image is required";
        hasError = true;
    } else {
        products_cover_error.textContent = "";
    }

    const priceImages = [
        ...products_price_images_wrapper.querySelectorAll("img"),
    ];
    if (!priceImages.length) {
        products_prices_error.textContent =
            "At least one price image is required";
        hasError = true;
    } else {
        products_prices_error.textContent = "";
    }

    if (hasError) return;

    // Disable save button during API call
    products_save_btn.disabled = true;
    products_save_btn.textContent = "Saving...";

    try {
        // Create FormData for multipart upload
        const formData = new FormData();
        formData.append("name", products_title_input.value.trim());
        formData.append("description", products_description_input?.value ?? "");

        if (products_main_file) {
            formData.append("cover_image", products_main_file);
        }

        // Add price images
        products_price_files.forEach((file, index) => {
            formData.append(`price_images[${index}]`, file);
        });

        if (products_edit_id !== null) {
            // Update existing product
            await ProductAPI.updateProduct(products_edit_id, formData);
            Toast.success("Product updated successfully!");
        } else {
            // Create new product
            await ProductAPI.createProduct(formData);
            Toast.success("Product created successfully!");
        }

        // Reload products from API
        await loadProductsFromAPI();

        // Close modal
        products_modal.style.display = "none";
        products_reset_modal();
    } catch (error) {
        console.error("Error saving product:", error);
        Toast.error(error.message || "Failed to save product");
    } finally {
        products_save_btn.disabled = false;
        products_save_btn.textContent = "Save";
    }
});

// ================= EDIT PRODUCT =================
async function editProduct(productId) {
    // Find product in list
    const product = products_list.find((p) => p.id === productId);
    if (!product) {
        Toast.error("Product not found");
        return;
    }

    // Set edit mode
    products_edit_id = productId;

    // Populate form with product data
    products_title_input.value = product.name || "";
    if (products_description_input) {
        products_description_input.value = product.description || "";
    }

    // Show cover image
    if (product.cover_image_path) {
        products_main_image_preview.src = `/storage/${product.cover_image_path}`;
        products_main_image_preview.style.display = "block";
        products_main_add_box.style.display = "none";
        products_remove_main_image_btn.style.display = "block";
        products_has_cover = true;
    }

    // Clear price images wrapper
    products_price_images_wrapper.innerHTML = "";

    // Show existing price images
    if (product.price_images && product.price_images.length > 0) {
        product.price_images.forEach((priceImage) => {
            const imagePath = priceImage.image_path || priceImage.path;
            products_price_images_wrapper.appendChild(
                createPriceBox(`/storage/${imagePath}`, true),
            );
        });
    }

    createAddPriceBox();

    // Show delete button and update modal title
    products_delete_btn.style.display = "block";
    document.getElementById("products_modal_title").textContent =
        "Edit Product";

    // Show modal
    products_modal.style.display = "flex";
}

// ================= DELETE PRODUCT =================
products_delete_btn.addEventListener("click", () => {
    if (products_edit_id === null) return;

    products_delete_confirm_modal.style.display = "flex";
});

products_confirm_delete_btn.addEventListener("click", async () => {
    if (products_edit_id === null) return;

    const productId = products_edit_id;

    // Disable button during API call
    products_confirm_delete_btn.disabled = true;
    products_confirm_delete_btn.textContent = "Deleting...";

    try {
        await ProductAPI.deleteProduct(productId);
        Toast.success("Product deleted successfully!");

        // Reload products
        await loadProductsFromAPI();

        // Close modals
        products_modal.style.display = "none";
        products_delete_confirm_modal.style.display = "none";
        products_reset_modal();
    } catch (error) {
        console.error("Error deleting product:", error);
        Toast.error(error.message || "Failed to delete product");
    } finally {
        products_confirm_delete_btn.disabled = false;
        products_confirm_delete_btn.textContent = "Delete Product";
    }
});

// ================= RENDER PRODUCTS =================
function renderProducts() {
    products_container.innerHTML = "";

    if (!products_list.length) {
        products_container.appendChild(products_no_items_text);
        return;
    }

    products_list.forEach((product) => {
        const card = document.createElement("div");
        card.className = "products_card";

        const img = document.createElement("img");
        img.className = "products_card_image";
        img.src = `/storage/${product.cover_image_path}`;
        img.alt = product.name;
        img.style.cursor = "pointer";
        img.onclick = () => products_view_price_images(product.id);

        const infoDiv = document.createElement("div");
        infoDiv.className = "products_card_info";

        const title = document.createElement("h3");
        title.textContent = product.name;

        const editBtn = document.createElement("button");
        editBtn.textContent = "Edit";
        editBtn.className = "products_button_save";
        editBtn.style.marginTop = "5px";
        editBtn.onclick = () => editProduct(product.id);

        infoDiv.append(title, editBtn);
        card.append(img, infoDiv);
        products_container.appendChild(card);
    });
}

// ================= VIEW PRODUCT PRICE IMAGES =================
function products_view_price_images(productId) {
    const product = products_list.find((p) => p.id === productId);
    if (!product || !product.price_images || !product.price_images.length) {
        Toast.info("No price images to display");
        return;
    }

    products_current_viewer_index = 0;
    products_viewer_title.textContent = `${product.name} - Price Images`;
    updatePriceImageViewer(product.price_images);
    products_price_viewer_modal.style.display = "flex";
}

function updatePriceImageViewer(priceImages) {
    if (!priceImages.length) return;

    const currentImage = priceImages[products_current_viewer_index];
    products_price_viewer_image.src = `/storage/${currentImage.image_path}`;
}

products_prev_price_image.addEventListener("click", () => {
    const product = products_list.find((p) => p.id === products_edit_id);
    if (!product || !product.price_images) return;

    products_current_viewer_index =
        (products_current_viewer_index - 1 + product.price_images.length) %
        product.price_images.length;
    updatePriceImageViewer(product.price_images);
});

products_next_price_image.addEventListener("click", () => {
    const product = products_list.find((p) => p.id === products_edit_id);
    if (!product || !product.price_images) return;

    products_current_viewer_index =
        (products_current_viewer_index + 1) % product.price_images.length;
    updatePriceImageViewer(product.price_images);
});

// ================= RESET MODAL =================
function products_reset_modal() {
    products_edit_id = null;
    products_title_input.value = "";
    if (products_description_input) {
        products_description_input.value = "";
    }
    products_main_image_preview.src = "";
    products_main_image_preview.style.display = "none";
    products_main_add_box.style.display = "flex";
    products_remove_main_image_btn.style.display = "none";
    products_price_images_wrapper.innerHTML = "";
    products_main_file = null;
    products_price_files = [];
    products_has_cover = false;

    document.getElementById("products_modal_title").textContent = "Add Product";

    // Clear error messages
    products_title_error.textContent = "";
    products_cover_error.textContent = "";
    products_prices_error.textContent = "";

    // Create initial add price box
    createAddPriceBox();
}

// Initialize add price box on load
createAddPriceBox();
