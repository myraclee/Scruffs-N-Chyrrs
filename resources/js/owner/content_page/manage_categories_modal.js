/**
 * Manage Categories Modal - Admin CRUD
 * Handles category management with database persistence via API
 * Fixed: Double submission prevention
 */

import ManageCategoriesAPI from "../../api/manage_categories_api.js";
import Toast from "../../utils/toast.js";

// ================= STATE =================
let categories = [];
let editingCategoryId = null;
let pendingDeleteId = null;
let isLoading = false;
let isSaving = false; // Prevent double submissions
let formMode = "idle"; // idle | create | edit

// ================= DOM ELEMENTS =================
let modalOverlay,
    categoryModal,
    categoryForm,
    categoryNameInput,
    sortOrderInput,
    submitBtn,
    createModeBtn;
let categoryTableBody, addCategoryBtn, deleteConfirmOverlay;
let deleteConfirmMessage, deleteConfirmYesBtn, deleteConfirmNoBtn;

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    initializeElements();
    setupEventListeners();
    await loadCategories();
});

function initializeElements() {
    modalOverlay = document.getElementById("manageCategoriesOverlay");
    categoryModal = document.getElementById("manageCategoriesModal");
    categoryForm = document.getElementById("categoryForm");
    categoryNameInput = document.getElementById("categoryNameInput");
    sortOrderInput = document.getElementById("sortOrderInput");
    categoryTableBody = document.getElementById("categoryTableBody");
    addCategoryBtn = document.getElementById("addCategoryBtn");
    deleteConfirmOverlay = document.getElementById(
        "deleteCategoryConfirmOverlay",
    );
    deleteConfirmMessage = document.getElementById(
        "deleteCategoryConfirmMessage",
    );
    deleteConfirmYesBtn = document.getElementById("deleteCategoryConfirmYes");
    deleteConfirmNoBtn = document.getElementById("deleteCategoryConfirmNo");
    createModeBtn = document.getElementById("enterCreateModeBtn");
    submitBtn = categoryForm.querySelector(".submit_btn");

    // Inputs are intentionally read-only until edit mode is activated.
    setInputsEnabled(false);
    setSaveButtonState(true, "Save");
}

function setupEventListeners() {
    addCategoryBtn?.addEventListener("click", () => openCategoryModal(null));

    // Form cancel button
    const closeCategoryFormBtn = document.getElementById(
        "closeCategoryFormBtn",
    );
    closeCategoryFormBtn?.addEventListener("click", closeCategoryModal);
    createModeBtn?.addEventListener("click", enterCreateMode);

    categoryForm?.addEventListener("submit", saveCategory);

    // Sort order input - allow only numbers 1-99
    sortOrderInput?.addEventListener("input", (e) => {
        let value = e.target.value.replace(/[^0-9]/g, "");
        if (value && parseInt(value) > 99) {
            value = "99";
        }
        e.target.value = value;
    });

    deleteConfirmYesBtn?.addEventListener("click", confirmDelete);
    deleteConfirmNoBtn?.addEventListener("click", cancelDelete);

    deleteConfirmOverlay?.addEventListener("click", (e) => {
        if (e.target === deleteConfirmOverlay) {
            cancelDelete();
        }
    });
}

// ================= LOAD CATEGORIES =================
async function loadCategories() {
    try {
        isLoading = true;
        categories = await ManageCategoriesAPI.getAllCategories();
        renderCategoryList();
    } catch (error) {
        console.error("Failed to load categories:", error);
        Toast.error("Failed to load categories");
    } finally {
        isLoading = false;
    }
}

// ================= RENDER CATEGORY LIST =================
function renderCategoryList() {
    if (!categoryTableBody) return;

    categoryTableBody.innerHTML = "";

    if (categories.length === 0) {
        categoryTableBody.innerHTML = `
      <tr>
        <td colspan="3" style="text-align: center; padding: 20px; color: #999;">
                    No categories yet. Click "Create New" then "Save" to add one.
        </td>
      </tr>
    `;
        return;
    }

    categories.forEach((category) => {
        const row = document.createElement("tr");
        row.className = "category_row";

        row.innerHTML = `
      <td class="category_name">${escapeHtml(category.name)}</td>
      <td class="category_sort_order">${category.sort_order}</td>
      <td class="category_actions">
                <button type="button" class="action_btn edit_btn" data-id="${category.id}" title="Edit">Edit</button>
                <button type="button" class="action_btn delete_btn" data-id="${category.id}" title="Delete">Delete</button>
      </td>
    `;

        const editBtn = row.querySelector(".edit_btn");
        const deleteBtn = row.querySelector(".delete_btn");

        editBtn.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            openCategoryModal(category.id);
        });
        deleteBtn.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            openDeleteConfirm(category.id);
        });

        categoryTableBody.appendChild(row);
    });
}

// ================= MODAL OPERATIONS =================
function openCategoryModal(categoryId = null) {
    editingCategoryId = categoryId;
    isSaving = false; // Reset saving state

    clearFieldErrors();

    if (categoryId === null) {
        // Default modal state: view-only until user selects Edit.
        formMode = "idle";
        categoryForm.reset();
        setInputsEnabled(false);
        setSaveButtonState(true, "Save");
    } else {
        // Edit existing category
        const category = categories.find((c) => c.id === categoryId);
        if (category) {
            formMode = "edit";
            categoryNameInput.value = category.name;
            sortOrderInput.value = category.sort_order;
            setInputsEnabled(true);
            setSaveButtonState(false, "Save");
            categoryNameInput.focus();
        }
    }

    showModal();
}

function enterCreateMode() {
    formMode = "create";
    editingCategoryId = null;
    isSaving = false;

    clearFieldErrors();
    categoryForm.reset();
    setInputsEnabled(true);
    setSaveButtonState(false, "Create");
    categoryNameInput.focus();
}

function closeCategoryModal() {
    hideModal();
    formMode = "idle";
    editingCategoryId = null;
    isSaving = false;
    categoryForm.reset();
    setInputsEnabled(false);
    setSaveButtonState(true, "Save");
}

function setInputsEnabled(enabled) {
    categoryNameInput.disabled = !enabled;
    sortOrderInput.disabled = !enabled;
}

function showModal() {
    modalOverlay?.classList.add("active");
    categoryModal?.classList.add("active");
}

function hideModal() {
    modalOverlay?.classList.remove("active");
    categoryModal?.classList.remove("active");
}

// ================= SET SAVE BUTTON STATE =================
function setSaveButtonState(disabled, label = "Save") {
    if (submitBtn) {
        submitBtn.disabled = disabled;
        submitBtn.textContent = label;
    }
}

// ================= FORM SUBMISSION =================
async function saveCategory(e) {
    e.preventDefault();

    // Poka-yoke: ignore submit events from non-save controls.
    const submitter = e.submitter;
    if (submitter && !submitter.classList.contains("submit_btn")) {
        return;
    }

    // Prevent double submission
    if (isSaving) {
        return;
    }

    if (formMode === "idle") {
        Toast.error("Choose Create New or Edit an existing category first.");
        return;
    }

    if (formMode === "edit" && editingCategoryId === null) {
        Toast.error("Select a category to edit first.");
        return;
    }

    const name = categoryNameInput.value.trim();
    const sortOrder = parseInt(sortOrderInput.value) || 1;

    // Validation
    if (!name) {
        showFieldError(categoryNameInput, "Category name is required");
        return;
    }

    if (sortOrder < 1 || sortOrder > 99) {
        showFieldError(sortOrderInput, "Sort order must be between 1 and 99");
        return;
    }

    const hasDuplicateSortOrder = categories.some(
        (category) => {
            if (Number(category.sort_order) !== sortOrder) {
                return false;
            }

            if (formMode !== "edit") {
                return true;
            }

            return Number(category.id) !== Number(editingCategoryId);
        },
    );

    if (hasDuplicateSortOrder) {
        showFieldError(
            sortOrderInput,
            "This sort order is already assigned to another category.",
        );
        return;
    }

    try {
        isSaving = true;
        setSaveButtonState(true, formMode === "create" ? "Create" : "Save");
        clearFieldErrors();

        const categoryData = { name, sort_order: sortOrder };

        if (formMode === "create") {
            // Create new
            await ManageCategoriesAPI.createCategory(categoryData);
            Toast.success("Category created successfully");
        } else {
            // Update existing
            await ManageCategoriesAPI.updateCategory(
                editingCategoryId,
                categoryData,
            );
            Toast.success("Category updated successfully");
        }

        closeCategoryModal();
        await loadCategories();

        // Notify parent that categories changed
        window.dispatchEvent(new CustomEvent("categoriesChanged"));
    } catch (error) {
        handleSaveError(error);
        isSaving = false;
        setSaveButtonState(false, formMode === "create" ? "Create" : "Save");
    }
}

// ================= DELETE OPERATIONS =================
function openDeleteConfirm(categoryId) {
    pendingDeleteId = categoryId;
    const category = categories.find((c) => c.id === categoryId);

    if (deleteConfirmMessage) {
        deleteConfirmMessage.textContent = `Are you sure you want to delete the category "${escapeHtml(category.name)}"?`;
    }

    deleteConfirmOverlay?.classList.add("active");
}

function cancelDelete() {
    pendingDeleteId = null;
    deleteConfirmOverlay?.classList.remove("active");
}

async function confirmDelete() {
    if (pendingDeleteId === null) return;

    try {
        isLoading = true;
        await ManageCategoriesAPI.deleteCategory(pendingDeleteId);

        cancelDelete();
        Toast.success("Category deleted successfully");
        await loadCategories();

        // Notify parent that categories changed
        window.dispatchEvent(new CustomEvent("categoriesChanged"));
    } catch (error) {
        cancelDelete();

        if (error.status === 409) {
            Toast.error(
                `Cannot delete: ${error.message} (${error.faq_count} FAQ(s) in this category)`,
            );
        } else {
            Toast.error("Failed to delete category");
        }
    } finally {
        isLoading = false;
    }
}

// ================= NOTIFICATION SYSTEM =================
function showFieldError(field, message) {
    const errorElement = field.nextElementSibling;
    if (errorElement && errorElement.classList.contains("field_error")) {
        errorElement.textContent = message;
        errorElement.classList.remove("hidden");
    }
    field.classList.add("input_error");
}

function clearFieldErrors() {
    document.querySelectorAll(".field_error").forEach((el) => {
        el.classList.add("hidden");
    });
    document.querySelectorAll(".input_error").forEach((el) => {
        el.classList.remove("input_error");
    });
}

function handleSaveError(error) {
    if (error.errors) {
        // Field-specific errors
        Object.keys(error.errors).forEach((field) => {
            const input =
                document.getElementById(field + "Input") ||
                document.getElementById(field);
            if (input) {
                showFieldError(input, error.errors[field][0]);
            }
        });
    } else if (error.message) {
        Toast.error(error.message);
    } else {
        Toast.error("Failed to save category");
    }
}

// ================= UTILITY FUNCTIONS =================
function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

// ================= PUBLIC API =================
window.ManageCategoriesModal = {
    open: () => openCategoryModal(),
    close: () => closeCategoryModal(),
    reload: () => loadCategories(),
    getCategories: () => [...categories],
};
