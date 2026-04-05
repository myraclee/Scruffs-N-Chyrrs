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
const newThresholdInput = document.getElementById("newThresholdInput");
const saveAddMaterialBtn = document.getElementById("saveSimulatedMaterialBtn");

const editMaterialName = document.getElementById("editMaterialName");
const editMaterialUnits = document.getElementById("editMaterialUnits");
const editThresholdInput = document.getElementById("editThresholdInput");
const saveEditMaterialBtn = document.getElementById("saveEditMaterialBtn");

const inventoryTableBody = document.getElementById("inventoryTableBody");
const emptyInventoryRow = document.getElementById("emptyInventoryRow");

const lowStockCard = document.getElementById("lowStockCard");
const outOfStockCard = document.getElementById("outOfStockCard");
const lowStockList = document.getElementById("lowStockList");
const outOfStockList = document.getElementById("outOfStockList");

// ================= STATE =================
let materials_list = [];
let products_list = [];
let materials_edit_id = null;
let currentRowBeingEdited = null;
let isLoading = false;
let isSaving = false;
let lastFocusedElement = null;
let showAllLowStockNames = false;
let showAllOutOfStockNames = false;

const DEFAULT_LOW_STOCK_THRESHOLD = 5;
const STATUS_CARD_NAME_CAP = 3;

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    if (
        !modalOverlay ||
        !addMaterialModal ||
        !editMaterialModal ||
        !openAddModalBtn ||
        !saveAddMaterialBtn ||
        !saveEditMaterialBtn ||
        !inventoryTableBody ||
        !newThresholdInput ||
        !editThresholdInput ||
        !lowStockCard ||
        !outOfStockCard ||
        !lowStockList ||
        !outOfStockList
    ) {
        return;
    }

    setModalVisibility(false);
    await loadMaterialsAndProducts();
    setupEventListeners();
});

/**
 * Load materials and products from API on page load
 */
async function loadMaterialsAndProducts() {
    if (isLoading) {
        return;
    }

    try {
        isLoading = true;
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
    } finally {
        isLoading = false;
    }
}

/**
 * Setup event listeners for modal and button interactions
 */
function setupEventListeners() {
    // Open Add Material Modal
    openAddModalBtn.addEventListener("click", () => {
        openAddModal();
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

    document.addEventListener("keydown", handleOverlayKeydown);

    // Clear field errors while user edits inputs
    [
        newMaterialInput,
        newUnitsInput,
        newThresholdInput,
        editMaterialName,
        editMaterialUnits,
        editThresholdInput,
    ].forEach((input) => {
        input?.addEventListener("input", () => clearFieldError(input));
    });

    [lowStockList, outOfStockList].forEach((listEl) => {
        listEl?.addEventListener("click", handleStatusToggleClick);
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
                ${escapeHtml(product.name)}
            </label>
            <input type="number" class="tiny_input add_quantity_input" data-product-id="${product.id}" placeholder="qty" min="0" step="1" inputmode="numeric" value="0" aria-label="Quantity for ${escapeHtml(product.name)}">
        `;
        addConsumedList.appendChild(addItem);

        // Edit modal checkbox
        const editItem = document.createElement("div");
        editItem.className = "consumed_item";
        editItem.innerHTML = `
            <label class="checkbox_label">
                <input type="checkbox" class="edit_product_checkbox" value="${product.id}" data-product-id="${product.id}">
                ${escapeHtml(product.name)}
            </label>
            <input type="number" class="tiny_input edit_quantity_input" data-product-id="${product.id}" placeholder="qty" min="0" step="1" inputmode="numeric" value="0" aria-label="Quantity for ${escapeHtml(product.name)}">
        `;
        editConsumedList.appendChild(editItem);
    });

    bindConsumedListEvents(
        addConsumedList,
        ".add_product_checkbox",
        ".add_quantity_input",
    );
    bindConsumedListEvents(
        editConsumedList,
        ".edit_product_checkbox",
        ".edit_quantity_input",
    );
}

/**
 * Save material (add or edit)
 */
async function saveMaterial(mode) {
    if (isSaving) {
        return;
    }

    const isAdd = mode === "add";
    const nameInput = isAdd ? newMaterialInput : editMaterialName;
    const unitsInput = isAdd ? newUnitsInput : editMaterialUnits;
    const thresholdInput = isAdd ? newThresholdInput : editThresholdInput;
    const modal = isAdd ? addMaterialModal : editMaterialModal;

    clearModalErrors(modal);
    clearFieldError(nameInput);
    clearFieldError(unitsInput);
    clearFieldError(thresholdInput);

    const name = nameInput.value.trim();
    if (!name) {
        setFieldError(nameInput, "Material name is required");
        return;
    }

    const unitsRaw = unitsInput.value.trim();
    if (!/^\d+$/.test(unitsRaw)) {
        setFieldError(unitsInput, "Units must be a whole number 0 or greater");
        return;
    }

    const units = Number(unitsRaw);
    if (!Number.isInteger(units) || units < 0) {
        setFieldError(unitsInput, "Units must be a whole number 0 or greater");
        return;
    }

    const thresholdRaw = thresholdInput.value.trim();
    if (!/^\d+$/.test(thresholdRaw)) {
        setFieldError(
            thresholdInput,
            "Low stock threshold must be a whole number 1 or greater",
        );
        return;
    }

    const lowStockThreshold = Number(thresholdRaw);
    if (!Number.isInteger(lowStockThreshold) || lowStockThreshold < 1) {
        setFieldError(
            thresholdInput,
            "Low stock threshold must be a whole number 1 or greater",
        );
        return;
    }

    // Collect product associations with quantity validation
    const checkboxes = isAdd
        ? Array.from(modal.querySelectorAll(".add_product_checkbox"))
        : Array.from(modal.querySelectorAll(".edit_product_checkbox"));

    const products = [];
    let quantitiesValid = true;

    checkboxes.forEach((checkbox) => {
        const productId = Number(checkbox.getAttribute("data-product-id"));
        const quantityInput = isAdd
            ? modal.querySelector(
                `.add_quantity_input[data-product-id="${productId}"]`,
            )
            : modal.querySelector(
                `.edit_quantity_input[data-product-id="${productId}"]`,
            );

        if (!quantityInput) {
            return;
        }

        clearFieldError(quantityInput);

        if (!checkbox.checked) {
            quantityInput.value = "0";
            return;
        }

        const quantityRaw = quantityInput.value.trim();
        if (!/^\d+$/.test(quantityRaw) || Number(quantityRaw) < 1) {
            setFieldError(quantityInput, "Required (1+)");
            quantitiesValid = false;
            return;
        }

        const quantity = Number(quantityRaw);

        if (checkbox.checked) {
            products.push({
                id: productId,
                quantity,
            });
        }
    });

    if (!quantitiesValid) {
        Toast.error("Set quantity to 1 or higher for each selected product.");
        return;
    }

    // Disable save button
    const saveBtn = isAdd ? saveAddMaterialBtn : saveEditMaterialBtn;
    isSaving = true;
    saveBtn.disabled = true;
    saveBtn.textContent = "Saving...";

    try {
        const data = {
            name,
            units: units,
            low_stock_threshold: lowStockThreshold,
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
        if (error.errors?.name?.length) {
            setFieldError(nameInput, error.errors.name[0]);
        }

        if (error.errors?.units?.length) {
            setFieldError(unitsInput, error.errors.units[0]);
        }

        if (error.errors?.low_stock_threshold?.length) {
            setFieldError(thresholdInput, error.errors.low_stock_threshold[0]);
        }

        if (error.errors && Object.keys(error.errors).length > 0) {
            const firstError = Object.values(error.errors).flat()[0];
            Toast.error(firstError || "Failed to save material");
        } else {
            Toast.error(error.message || "Failed to save material");
        }
    } finally {
        isSaving = false;
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

    lastFocusedElement = btn || document.activeElement;

    // Set edit mode
    materials_edit_id = materialId;
    currentRowBeingEdited = btn.closest("tr");

    // Populate edit form
    clearModalErrors(editMaterialModal);
    editMaterialName.value = material.name;
    editMaterialUnits.value = material.units;
    editThresholdInput.value = String(
        normalizeLowStockThreshold(material.low_stock_threshold),
    );

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
    setModalVisibility(true, editMaterialModal);
    editMaterialName.focus();
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
        if (error?.statusCode === 419) {
            Toast.error("Session expired. Refresh this page and sign in again.");
            return;
        }
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
        const safeMaterialName = escapeHtml(material.name);

        // Get associated products display text
        let productText = "None assigned";
        if (material.products && material.products.length > 0) {
            productText = material.products
                .map(
                    (p) =>
                        `${escapeHtml(p.name)} (x${Number(p.pivot?.quantity || 0)})`,
                )
                .join(", ");
        }

        row.innerHTML = `
            <td>${safeMaterialName}</td>
            <td class="text-center">${material.units}</td>
            <td>
                <div class="product_cell">
                    <span>${productText}</span>
                    <div class="product_actions">
                        <button type="button" class="action_btn edit_btn" onclick="openEditModal(this, ${material.id})" title="Edit material" aria-label="Edit ${safeMaterialName}">Edit</button>
                        <button type="button" class="action_btn delete_btn" onclick="deleteMaterial(${material.id})" title="Delete material" aria-label="Delete ${safeMaterialName}">Delete</button>
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
    const lowStocks = [];
    const outOfStocks = [];

    materials_list.forEach((material) => {
        const units = Number(material.units || 0);
        const lowStockThreshold = normalizeLowStockThreshold(
            material.low_stock_threshold,
        );

        if (units === 0) {
            outOfStocks.push(material.name);
        } else if (units <= lowStockThreshold) {
            lowStocks.push(material.name);
        }
    });

    if (lowStocks.length <= STATUS_CARD_NAME_CAP) {
        showAllLowStockNames = false;
    }

    if (outOfStocks.length <= STATUS_CARD_NAME_CAP) {
        showAllOutOfStockNames = false;
    }

    renderStatusCard(
        lowStockCard,
        lowStockList,
        lowStocks,
        "Stock Items High",
        "low",
        showAllLowStockNames,
    );

    renderStatusCard(
        outOfStockCard,
        outOfStockList,
        outOfStocks,
        "All Items In Stock",
        "out",
        showAllOutOfStockNames,
    );
}

function renderStatusCard(
    cardElement,
    listElement,
    names,
    healthyMessage,
    toggleType,
    isExpanded,
) {
    const hasAlerts = names.length > 0;

    cardElement.classList.toggle("status_alert", hasAlerts);
    cardElement.classList.toggle("status_healthy", !hasAlerts);
    cardElement.classList.toggle("pulse_glow", hasAlerts);

    listElement.innerHTML = buildStatusListMarkup(
        names,
        healthyMessage,
        toggleType,
        isExpanded,
    );
}

function buildStatusListMarkup(names, healthyMessage, toggleType, isExpanded) {
    if (names.length === 0) {
        return `<p class="empty_status">${escapeHtml(healthyMessage)}</p>`;
    }

    const visibleNames = isExpanded ? names : names.slice(0, STATUS_CARD_NAME_CAP);
    const namesMarkup = visibleNames
        .map((name) => `<p class="status_item">${escapeHtml(name)}</p>`)
        .join("");

    if (names.length <= STATUS_CARD_NAME_CAP) {
        return namesMarkup;
    }

    const hiddenCount = names.length - STATUS_CARD_NAME_CAP;
    const toggleText = isExpanded
        ? "Show Less"
        : `Show More (${hiddenCount} more)`;

    return `${namesMarkup}<button type="button" class="status_more_btn" data-status-toggle="${toggleType}" aria-expanded="${isExpanded ? "true" : "false"}">${toggleText}</button>`;
}

function handleStatusToggleClick(event) {
    const toggleButton = event.target.closest(".status_more_btn");
    if (!toggleButton) {
        return;
    }

    const toggleType = toggleButton.getAttribute("data-status-toggle");
    if (toggleType === "low") {
        showAllLowStockNames = !showAllLowStockNames;
    }

    if (toggleType === "out") {
        showAllOutOfStockNames = !showAllOutOfStockNames;
    }

    updateStatusCards();
}

/**
 * Reset add material form
 */
function resetAddModal() {
    clearModalErrors(addMaterialModal);
    newMaterialInput.value = "";
    newUnitsInput.value = "";
    newThresholdInput.value = String(DEFAULT_LOW_STOCK_THRESHOLD);

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

function resetEditModal() {
    clearModalErrors(editMaterialModal);
    editMaterialName.value = "";
    editMaterialUnits.value = "";
    editThresholdInput.value = String(DEFAULT_LOW_STOCK_THRESHOLD);

    editMaterialModal
        .querySelectorAll(".edit_product_checkbox")
        .forEach((checkbox) => {
            checkbox.checked = false;
        });

    editMaterialModal
        .querySelectorAll(".edit_quantity_input")
        .forEach((input) => {
            input.value = "0";
        });
}

/**
 * Close all modals
 */
function closeAllModals() {
    setModalVisibility(false);
    resetAddModal();
    resetEditModal();
    materials_edit_id = null;
    currentRowBeingEdited = null;

    if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
        lastFocusedElement.focus();
    }

    lastFocusedElement = null;
}

// Make closeAllModals available globally
window.closeAllModals = closeAllModals;

function openAddModal() {
    lastFocusedElement = document.activeElement;
    materials_edit_id = null;
    resetAddModal();
    setModalVisibility(true, addMaterialModal);
    newMaterialInput.focus();
}

function setModalVisibility(isVisible, targetModal = null) {
    if (!isVisible) {
        modalOverlay.classList.remove("active");
        addMaterialModal.classList.remove("active");
        editMaterialModal.classList.remove("active");

        modalOverlay.setAttribute("aria-hidden", "true");
        addMaterialModal.setAttribute("aria-hidden", "true");
        editMaterialModal.setAttribute("aria-hidden", "true");
        return;
    }

    modalOverlay.classList.add("active");
    modalOverlay.setAttribute("aria-hidden", "false");

    [addMaterialModal, editMaterialModal].forEach((modal) => {
        const isTarget = modal === targetModal;
        modal.classList.toggle("active", isTarget);
        modal.setAttribute("aria-hidden", isTarget ? "false" : "true");
    });
}

function handleOverlayKeydown(event) {
    if (!modalOverlay.classList.contains("active")) {
        return;
    }

    if (event.key === "Escape") {
        event.preventDefault();
        closeAllModals();
        return;
    }

    if (event.key === "Tab") {
        trapFocus(event);
    }
}

function trapFocus(event) {
    const activeModal = getActiveModal();
    if (!activeModal) {
        return;
    }

    const focusableElements = Array.from(
        activeModal.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    ).filter((element) => element.offsetParent !== null);

    if (focusableElements.length === 0) {
        return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
        return;
    }

    if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
    }
}

function getActiveModal() {
    if (addMaterialModal.classList.contains("active")) {
        return addMaterialModal;
    }

    if (editMaterialModal.classList.contains("active")) {
        return editMaterialModal;
    }

    return null;
}

function bindConsumedListEvents(list, checkboxSelector, quantitySelector) {
    list.addEventListener("change", (event) => {
        const checkbox = event.target.closest(checkboxSelector);
        if (!checkbox) {
            return;
        }

        const productId = checkbox.getAttribute("data-product-id");
        const quantityInput = list.querySelector(
            `${quantitySelector}[data-product-id="${productId}"]`,
        );

        if (!quantityInput) {
            return;
        }

        if (!checkbox.checked) {
            quantityInput.value = "0";
            clearFieldError(quantityInput);
        }
    });

    list.addEventListener("input", (event) => {
        const quantityInput = event.target.closest(quantitySelector);
        if (!quantityInput) {
            return;
        }

        if (/^\d+$/.test(quantityInput.value.trim())) {
            clearFieldError(quantityInput);
        }
    });
}

function clearModalErrors(modal) {
    modal.querySelectorAll(".field_error").forEach((errorEl) => errorEl.remove());

    modal.querySelectorAll("[aria-invalid='true']").forEach((input) => {
        input.removeAttribute("aria-invalid");
        input.removeAttribute("aria-describedby");
    });
}

function setFieldError(input, message) {
    if (!input) {
        return;
    }

    clearFieldError(input);

    const error = document.createElement("small");
    error.className = "field_error";
    error.textContent = message;

    const errorId = getErrorId(input);
    error.id = errorId;

    input.setAttribute("aria-invalid", "true");
    input.setAttribute("aria-describedby", errorId);
    input.insertAdjacentElement("afterend", error);
}

function clearFieldError(input) {
    if (!input) {
        return;
    }

    const errorId = getErrorId(input);
    const existingError = document.getElementById(errorId);
    existingError?.remove();

    input.removeAttribute("aria-invalid");
    input.removeAttribute("aria-describedby");
}

function getErrorId(input) {
    if (input.id) {
        return `${input.id}_error`;
    }

    const productId = input.getAttribute("data-product-id") || "unknown";
    return `${input.className.replace(/\s+/g, "_")}_${productId}_error`;
}

function normalizeLowStockThreshold(value) {
    const parsedValue = Number(value);
    if (Number.isInteger(parsedValue) && parsedValue >= 1) {
        return parsedValue;
    }

    return DEFAULT_LOW_STOCK_THRESHOLD;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
