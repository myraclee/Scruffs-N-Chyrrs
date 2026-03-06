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
const products_image_notes_wrapper = document.getElementById(
    "products_image_notes_wrapper",
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
let products_list = [];
let products_edit_id = null;
let products_current_viewer_index = 0;
let products_has_cover = false;
let products_main_file = null;
let products_price_files = [];
let products_notes_files = [];

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    createAddPriceBox();
    initNotesUpload();
    await loadProductsFromAPI();
});

async function loadProductsFromAPI() {
    try {
        products_container.innerHTML =
            '<p style="color: #999;">Loading products...</p>';
        const products = await ProductAPI.getAllProducts();
        products_list = products;
        renderProducts();
        dispatchProductsUpdated();
    } catch (error) {
        console.error("Error loading products:", error);
        Toast.error("Failed to load products from database");
        products_container.innerHTML =
            '<p style="color: #999;">No products uploaded yet.</p>';
    }
}

function dispatchProductsUpdated() {
    window.dispatchEvent(
        new CustomEvent("productsUpdated", { detail: products_list }),
    );
}

// ================= VALIDATION HELPERS =================
function setFieldError(field, errorEl, message) {
    errorEl.textContent = message;
    field.classList.add("products_input_error");
}
function clearFieldError(field, errorEl) {
    errorEl.textContent = "";
    field.classList.remove("products_input_error");
}

products_title_input.addEventListener("input", () => {
    clearFieldError(products_title_input, products_title_error);
});

// ================= COVER IMAGE HANDLING =================
products_main_add_box.addEventListener("click", () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/png, image/jpg, image/jpeg";
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        products_main_file = file;
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
        products_main_add_box.classList.remove("products_input_error");
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

/**
 * Returns the single "+" add box currently in the price wrapper, or null.
 */
function getPriceAddBox() {
    return (
        products_price_images_wrapper.querySelector(
            ".products_price_add_box",
        ) || null
    );
}

function createPriceBox(existingSrc = null) {
    const wrapper = document.createElement("div");
    wrapper.className = "products_price_box_wrapper";

    const box = document.createElement("div");
    box.className = "products_price_box";

    if (!existingSrc) {
        // This is the "+" upload box
        box.classList.add("products_price_add_box");
        box.textContent = "+";
        box.addEventListener("click", () => {
            const input = document.createElement("input");
            input.type = "file";
            input.accept = "image/png, image/jpg, image/jpeg";
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (!file) return;
                products_price_files.push(file);
                const reader = new FileReader();
                reader.onload = (event) => {
                    // Build a filled wrapper and insert before the "+" box wrapper
                    const filledWrapper = buildFilledPriceWrapper(
                        event.target.result,
                        file,
                    );
                    const addBoxWrapper = getPriceAddBox()?.parentElement;
                    if (addBoxWrapper) {
                        products_price_images_wrapper.insertBefore(
                            filledWrapper,
                            addBoxWrapper,
                        );
                    } else {
                        products_price_images_wrapper.appendChild(
                            filledWrapper,
                        );
                    }
                    // Clear validation error
                    products_prices_error.textContent = "";
                    box.classList.remove("products_input_error");
                };
                reader.readAsDataURL(file);
            };
            input.click();
        });
        wrapper.appendChild(box);
        return wrapper;
    }

    // Filled box (existing DB image) — built via buildFilledPriceWrapper
    return null;
}

function buildFilledPriceWrapper(src, file = null) {
    const wrapper = document.createElement("div");
    wrapper.className = "products_price_box_wrapper";

    const box = document.createElement("div");
    box.className = "products_price_box";

    const img = document.createElement("img");
    img.src = src;
    img.style.objectFit = "cover";
    box.appendChild(img);

    const removeBtn = document.createElement("button");
    removeBtn.textContent = "Remove";
    removeBtn.className = "products_button_remove";
    removeBtn.type = "button";
    removeBtn.onclick = () => {
        if (file) {
            products_price_files = products_price_files.filter(
                (f) => f !== file,
            );
        }
        wrapper.remove();
        createAddPriceBox();
    };

    wrapper.appendChild(box);
    wrapper.appendChild(removeBtn);
    return wrapper;
}

function createAddPriceBox() {
    if (!getPriceAddBox()) {
        products_price_images_wrapper.appendChild(createPriceBox());
    }
}

// ================= IMAGE NOTES HANDLING =================

/**
 * Build a wrapper containing the note image + Remove button.
 * Used both for new uploads and for restoring existing DB images.
 */
function buildNoteWrapper(src, file = null) {
    const wrapper = document.createElement("div");
    wrapper.className = "products_notes_item_wrapper";

    const box = document.createElement("div");
    box.className = "products_notes_upload_box products_notes_display_box";

    const img = document.createElement("img");
    img.src = src;
    img.className = "products_notes_img";
    box.appendChild(img);

    const removeBtn = document.createElement("button");
    removeBtn.textContent = "Remove";
    removeBtn.className = "products_button_remove";
    removeBtn.type = "button";
    removeBtn.onclick = () => {
        if (file) {
            products_notes_files = products_notes_files.filter(
                (f) => f !== file,
            );
        }
        wrapper.remove();
    };

    wrapper.appendChild(box);
    wrapper.appendChild(removeBtn);
    return wrapper;
}

/**
 * Create the "+" upload box for notes and return it (does NOT append itself).
 */
function createNotesAddBox() {
    const box = document.createElement("div");
    box.className = "products_notes_upload_box";

    const plus = document.createElement("span");
    plus.className = "products_notes_plus";
    plus.textContent = "+";
    box.appendChild(plus);

    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/png, image/jpeg";
    fileInput.style.display = "none";
    document.body.appendChild(fileInput);

    box.addEventListener("click", () => fileInput.click());

    fileInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (!file) return;
        products_notes_files.push(file);
        const reader = new FileReader();
        reader.onload = (ev) => {
            fileInput.remove();
            // Insert the new image wrapper just before the "+" box
            const noteWrapper = buildNoteWrapper(ev.target.result, file);
            products_image_notes_wrapper.insertBefore(noteWrapper, box);
        };
        reader.readAsDataURL(file);
    });

    return box;
}

function initNotesUpload() {
    if (!products_image_notes_wrapper) return;
    products_image_notes_wrapper.innerHTML = "";
    products_notes_files = [];
    products_image_notes_wrapper.appendChild(createNotesAddBox());
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

    // Validate product name
    if (!products_title_input.value.trim()) {
        setFieldError(
            products_title_input,
            products_title_error,
            "Product name is required",
        );
        hasError = true;
    } else {
        clearFieldError(products_title_input, products_title_error);
    }

    // Validate cover image
    if (!products_has_cover) {
        products_cover_error.textContent = "Cover image is required";
        products_main_add_box.classList.add("products_input_error");
        hasError = true;
    } else {
        products_cover_error.textContent = "";
        products_main_add_box.classList.remove("products_input_error");
    }

    // Validate price images — mark the "+" add box red if empty
    const priceImages = [
        ...products_price_images_wrapper.querySelectorAll("img"),
    ];
    const priceAddBox = getPriceAddBox();
    if (!priceImages.length) {
        products_prices_error.textContent =
            "At least one price image is required";
        if (priceAddBox) priceAddBox.classList.add("products_input_error");
        hasError = true;
    } else {
        products_prices_error.textContent = "";
        if (priceAddBox) priceAddBox.classList.remove("products_input_error");
    }

    if (hasError) return;

    products_save_btn.disabled = true;
    products_save_btn.textContent = "Saving...";

    try {
        const formData = new FormData();
        formData.append("name", products_title_input.value.trim());
        formData.append("description", products_description_input?.value ?? "");

        if (products_main_file) {
            formData.append("cover_image", products_main_file);
        }

        products_price_files.forEach((file, index) => {
            formData.append(`price_images[${index}]`, file);
        });

        products_notes_files.forEach((file, index) => {
            formData.append(`note_images[${index}]`, file);
        });

        if (products_edit_id !== null) {
            await ProductAPI.updateProduct(products_edit_id, formData);
            Toast.success("Product updated successfully!");
        } else {
            await ProductAPI.createProduct(formData);
            Toast.success("Product created successfully!");
        }

        await loadProductsFromAPI();
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
    const product = products_list.find((p) => p.id === productId);
    if (!product) {
        Toast.error("Product not found");
        return;
    }

    products_edit_id = productId;
    products_title_input.value = product.name || "";
    if (products_description_input) {
        products_description_input.value = product.description || "";
    }

    if (product.cover_image_path) {
        products_main_image_preview.src = `/storage/${product.cover_image_path}`;
        products_main_image_preview.style.display = "block";
        products_main_add_box.style.display = "none";
        products_remove_main_image_btn.style.display = "block";
        products_has_cover = true;
    }

    // Restore price images
    products_price_images_wrapper.innerHTML = "";
    products_price_files = [];
    if (product.price_images && product.price_images.length > 0) {
        product.price_images.forEach((priceImage) => {
            const imagePath = priceImage.image_path || priceImage.path;
            products_price_images_wrapper.appendChild(
                buildFilledPriceWrapper(`/storage/${imagePath}`),
            );
        });
    }
    createAddPriceBox();

    // Restore image notes
    products_image_notes_wrapper.innerHTML = "";
    products_notes_files = [];
    if (product.note_images && product.note_images.length > 0) {
        product.note_images.forEach((noteImg) => {
            const notePath = noteImg.image_path || noteImg.path;
            products_image_notes_wrapper.appendChild(
                buildNoteWrapper(`/storage/${notePath}`),
            );
        });
    }
    products_image_notes_wrapper.appendChild(createNotesAddBox());

    products_delete_btn.style.display = "block";
    document.getElementById("products_modal_title").textContent =
        "Edit Product";
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
    products_confirm_delete_btn.disabled = true;
    products_confirm_delete_btn.textContent = "Deleting...";
    try {
        await ProductAPI.deleteProduct(productId);
        Toast.success("Product deleted successfully!");
        await loadProductsFromAPI();
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
    products_title_input.classList.remove("products_input_error");
    products_title_error.textContent = "";

    if (products_description_input) products_description_input.value = "";

    products_main_image_preview.src = "";
    products_main_image_preview.style.display = "none";
    products_main_add_box.style.display = "flex";
    products_main_add_box.classList.remove("products_input_error");
    products_remove_main_image_btn.style.display = "none";
    products_main_file = null;
    products_has_cover = false;

    products_price_images_wrapper.innerHTML = "";
    products_price_files = [];
    products_cover_error.textContent = "";
    products_prices_error.textContent = "";

    document.getElementById("products_modal_title").textContent = "Add Product";

    initNotesUpload();
    createAddPriceBox();
}
