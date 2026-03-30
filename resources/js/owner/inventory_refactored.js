/**
 * Inventory Management - Materials CRUD
 * Handles material inventory management with database persistence via API
 */

// ================= IMPORTS =================
import MaterialAPI from "/resources/js/api/materialApi.js";
import ProductAPI from "/resources/js/api/productApi.js";
import Toast from "/resources/js/utils/toast.js";

// ================= ELEMENTS =================
const modalOverlay = document.getElementById("modalOverlay");
const addMaterialModal = document.getElementById("addMaterialModal");
const editMaterialModal = document.getElementById("editMaterialModal");
const openAddModalBtn = document.getElementById("openAddModalBtn");

const newMaterialInput = document.getElementById("newMaterialInput");
const newUnitsInput = document.getElementById("newUnitsInput");
const saveAddMaterialBtn = document.getElementById("saveSimulatedMaterialBtn");

const editMaterialName = document.getElementById("editMaterialName");
const editMaterialUnits = document.getElementById("editMaterialUnits");
const saveEditMaterialBtn = document.getElementById("saveEditMaterialBtn");

const inventoryTableBody = document.getElementById("inventoryTableBody");
const emptyInventoryRow = document.getElementById("emptyInventoryRow");

const lowStockList = document.getElementById("lowStockList");
const outOfStockList = document.getElementById("outOfStockList");

// ================= STATE =================
let materials_list = [];
let products_list = [];
let materials_edit_id = null;
let currentRowBeingEdited = null;

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    await loadMaterialsAndProducts();
    setupEventListeners();
});

/**
 * Load materials and products from API on page load
 */
async function loadMaterialsAndProducts() {
    try {
        // Show loading state
        if (emptyInventoryRow) {
            emptyInventoryRow.innerHTML =
                '<td colspan="3" class="text-center" style="padding: 60px; color: #682C7A; font-style: italic;">Loading inventory...</td>';
        }

        const [materials, products] = await Promise.all([
            MaterialAPI.getAllMaterials(),
            ProductAPI.getAllProducts(),
        ]);

        materials_list = materials;
        products_list = products;

        renderMaterials();
        updateProductCheckboxes();
        updateStatusCards();
    } catch (error) {
        console.error("Error loading inventory:", error);
        Toast.error("Failed to load inventory from database");
        if (emptyInventoryRow) {
            emptyInventoryRow.innerHTML =
                '<td colspan="3" class="text-center" style="padding: 60px; color: #999;">Error loading inventory</td>';
        }
    }
}

/**
 * Setup event listeners for modal and button interactions
 */
function setupEventListeners() {
    // Open Add Material Modal
    openAddModalBtn.addEventListener("click", () => {
        materials_edit_id = null;
        resetAddModal();
        modalOverlay.classList.add("active");
        addMaterialModal.classList.add("active");
    });

    // Save New Material
    saveAddMaterialBtn.addEventListener("click", async () => {
        await saveMaterial("add");
    });

    // Save Edit Material
    saveEditMaterialBtn.addEventListener("click", async () => {
        await saveMaterial("edit");
    });

    // Close modals on overlay click
    modalOverlay.addEventListener("click", (e) => {
        if (e.target === modalOverlay) {
            closeAllModals();
        }
    });
}

/**
 * Update product checkboxes dynamically from API products list
 */
function updateProductCheckboxes() {
    // Get all consumed lists (both add and edit modals)
    const addConsumedList = addMaterialModal.querySelector(".consumed_list");
    const editConsumedList = editMaterialModal.querySelector(".consumed_list");

    // Clear existing checkboxes
    addConsumedList.innerHTML = "";
    editConsumedList.innerHTML = "";

    // Add checkboxes for each product from database
    products_list.forEach((product) => {
        // Add modal checkbox
        const addItem = document.createElement("div");
        addItem.className = "consumed_item";
        addItem.innerHTML = `
            <label class="checkbox_label">
                <input type="checkbox" class="add_product_checkbox" value="${product.id}" data-product-id="${product.id}">
                ${product.name}
            </label>
            <input type="number" class="tiny_input add_quantity_input" data-product-id="${product.id}" placeholder="qty" min="0" value="0">
        `;
        addConsumedList.appendChild(addItem);

        // Edit modal checkbox
        const editItem = document.createElement("div");
        editItem.className = "consumed_item";
        editItem.innerHTML = `
            <label class="checkbox_label">
                <input type="checkbox" class="edit_product_checkbox" value="${product.id}" data-product-id="${product.id}">
                ${product.name}
            </label>
            <input type="number" class="tiny_input edit_quantity_input" data-product-id="${product.id}" placeholder="qty" min="0" value="0">
        `;
        editConsumedList.appendChild(editItem);
    });
}

/**
 * Save material (add or edit)
 */
async function saveMaterial(mode) {
    const isAdd = mode === "add";
    const nameInput = isAdd ? newMaterialInput : editMaterialName;
    const unitsInput = isAdd ? newUnitsInput : editMaterialUnits;
    const quantityInputs = isAdd
        ? Array.from(addMaterialModal.querySelectorAll(".add_quantity_input"))
        : Array.from(
              editMaterialModal.querySelectorAll(".edit_quantity_input"),
          );

    // Validation
    if (!nameInput.value.trim()) {
        Toast.error("Material name is required");
        return;
    }

    const units = parseInt(unitsInput.value);
    if (isNaN(units) || units < 0) {
        Toast.error("Units must be a valid non-negative number");
        return;
    }

    // Collect product associations
    const checkboxes = isAdd
        ? Array.from(addMaterialModal.querySelectorAll(".add_product_checkbox"))
        : Array.from(
              editMaterialModal.querySelectorAll(".edit_product_checkbox"),
          );

    const products = [];
    checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
            const productId = parseInt(
                checkbox.getAttribute("data-product-id"),
            );
            const quantityInput = isAdd
                ? addMaterialModal.querySelector(
                      `.add_quantity_input[data-product-id="${productId}"]`,
                  )
                : editMaterialModal.querySelector(
                      `.edit_quantity_input[data-product-id="${productId}"]`,
                  );

            const quantity = parseInt(quantityInput.value) || 0;
            if (quantity > 0) {
                products.push({
                    id: productId,
                    quantity: quantity,
                });
            }
        }
    });

    // Disable save button
    const saveBtn = isAdd ? saveAddMaterialBtn : saveEditMaterialBtn;
    saveBtn.disabled = true;
    saveBtn.textContent = "Saving...";

    try {
        const data = {
            name: nameInput.value.trim(),
            units: units,
            products: products,
        };

        if (isAdd) {
            // Create new material
            await MaterialAPI.createMaterial(data);
            Toast.success("Material added successfully!");
        } else {
            // Update existing material
            await MaterialAPI.updateMaterial(materials_edit_id, data);
            Toast.success("Material updated successfully!");
        }

        // Reload materials
        await loadMaterialsAndProducts();
        closeAllModals();
    } catch (error) {
        console.error("Error saving material:", error);
        Toast.error(error.message || "Failed to save material");
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = isAdd ? "Add Material" : "Save Changes";
    }
}

/**
 * Open edit modal with material data
 */
async function openEditModal(btn, materialId) {
    // Find material in list
    const material = materials_list.find((m) => m.id === materialId);
    if (!material) {
        Toast.error("Material not found");
        return;
    }

    // Set edit mode
    materials_edit_id = materialId;
    currentRowBeingEdited = btn.closest("tr");

    // Populate edit form
    editMaterialName.value = material.name;
    editMaterialUnits.value = material.units;

    // Reset all checkboxes
    editMaterialModal
        .querySelectorAll(".edit_product_checkbox")
        .forEach((checkbox) => {
            checkbox.checked = false;
            const quantityInput = editMaterialModal.querySelector(
                `.edit_quantity_input[data-product-id="${checkbox.getAttribute("data-product-id")}"]`,
            );
            if (quantityInput) {
                quantityInput.value = "0";
            }
        });

    // Check boxes for associated products and set quantities
    if (material.products && material.products.length > 0) {
        material.products.forEach((product) => {
            const checkbox = editMaterialModal.querySelector(
                `.edit_product_checkbox[data-product-id="${product.id}"]`,
            );
            const quantityInput = editMaterialModal.querySelector(
                `.edit_quantity_input[data-product-id="${product.id}"]`,
            );

            if (checkbox) {
                checkbox.checked = true;
            }
            if (quantityInput) {
                quantityInput.value = product.pivot?.quantity || 0;
            }
        });
    }

    // Show edit modal
    modalOverlay.classList.add("active");
    editMaterialModal.classList.add("active");
}

/**
 * Delete material
 * Makes the function available globally for onclick handler
 */
window.deleteMaterial = async function (materialId) {
    if (
        !confirm(
            "Are you sure you want to delete this material? This action cannot be undone.",
        )
    ) {
        return;
    }

    try {
        await MaterialAPI.deleteMaterial(materialId);
        Toast.success("Material deleted successfully!");
        await loadMaterialsAndProducts();
    } catch (error) {
        console.error("Error deleting material:", error);
        Toast.error(error.message || "Failed to delete material");
    }
};

/**
 * Make openEditModal available globally for onclick handler
 */
window.openEditModal = openEditModal;

/**
 * Render materials table from API data
 */
function renderMaterials() {
    inventoryTableBody.innerHTML = "";

    if (!materials_list || materials_list.length === 0) {
        const emptyRow = document.createElement("tr");
        emptyRow.id = "emptyInventoryRow";
        emptyRow.innerHTML = `
            <td colspan="3" class="text-center" style="padding: 60px; color: #682C7A; font-style: italic;">
                No materials found. Start by adding new stock below!
            </td>
        `;
        inventoryTableBody.appendChild(emptyRow);
        return;
    }

    materials_list.forEach((material) => {
        const row = document.createElement("tr");

        // Get associated products display text
        let productText = "None assigned";
        if (material.products && material.products.length > 0) {
            productText = material.products
                .map((p) => `${p.name} (×${p.pivot?.quantity || 0})`)
                .join(", ");
        }

        row.innerHTML = `
            <td>${material.name}</td>
            <td class="text-center">${material.units}</td>
            <td>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>${productText}</span>
                    <div style="display: flex; gap: 10px;">
                        <button class="edit_btn" onclick="openEditModal(this, ${material.id})" title="Edit material">✏️</button>
                        <button class="edit_btn" onclick="deleteMaterial(${material.id})" title="Delete material" style="color: #D94848;">🗑️</button>
                    </div>
                </div>
            </td>
        `;
        inventoryTableBody.appendChild(row);
    });
}

/**
 * Update status cards based on material data
 */
function updateStatusCards() {
    let lowStocks = [];
    let outOfStocks = [];

    materials_list.forEach((material) => {
        if (material.units === 0) {
            outOfStocks.push(material.name);
        } else if (material.units <= 5) {
            lowStocks.push(material.name);
        }
    });

    // Update low stock display
    if (lowStocks.length > 0) {
        lowStockList.innerHTML = lowStocks
            .map((name) => `<p>${name}</p>`)
            .join("");
    } else {
        lowStockList.innerHTML =
            '<p class="empty_status">All levels healthy!</p>';
    }

    // Update out of stock display
    if (outOfStocks.length > 0) {
        outOfStockList.innerHTML = outOfStocks
            .map((name) => `<p>${name}</p>`)
            .join("");
    } else {
        outOfStockList.innerHTML =
            '<p class="empty_status">All items are in stock.</p>';
    }
}

/**
 * Reset add material form
 */
function resetAddModal() {
    newMaterialInput.value = "";
    newUnitsInput.value = "";

    addMaterialModal
        .querySelectorAll(".add_product_checkbox")
        .forEach((checkbox) => {
            checkbox.checked = false;
        });

    addMaterialModal
        .querySelectorAll(".add_quantity_input")
        .forEach((input) => {
            input.value = "0";
        });
}

/**
 * Close all modals
 */
function closeAllModals() {
    modalOverlay.classList.remove("active");
    addMaterialModal.classList.remove("active");
    editMaterialModal.classList.remove("active");
    resetAddModal();
    materials_edit_id = null;
    currentRowBeingEdited = null;
}

// Make closeAllModals available globally
window.closeAllModals = closeAllModals;
