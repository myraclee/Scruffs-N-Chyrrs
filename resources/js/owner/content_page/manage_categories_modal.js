/**
 * Manage Categories Modal - Admin CRUD
 * Handles category management with database persistence via API
 */

import ManageCategoriesAPI from '../../api/manage_categories_api.js';

// ================= STATE =================
let categories = [];
let editingCategoryId = null;
let pendingDeleteId = null;
let isLoading = false;

// ================= DOM ELEMENTS =================
let modalOverlay, categoryModal, categoryForm, categoryNameInput, sortOrderInput;
let categoryTableBody, addCategoryBtn, closeModalBtn, deleteConfirmOverlay;
let deleteConfirmMessage, deleteConfirmYesBtn, deleteConfirmNoBtn;

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
  initializeElements();
  setupEventListeners();
  await loadCategories();
});

function initializeElements() {
  modalOverlay = document.getElementById('manageCategoriesOverlay');
  categoryModal = document.getElementById('manageCategoriesModal');
  categoryForm = document.getElementById('categoryForm');
  categoryNameInput = document.getElementById('categoryNameInput');
  sortOrderInput = document.getElementById('sortOrderInput');
  categoryTableBody = document.getElementById('categoryTableBody');
  addCategoryBtn = document.getElementById('addCategoryBtn');
  closeModalBtn = document.getElementById('closeCategoryModal');
  deleteConfirmOverlay = document.getElementById('deleteCategoryConfirmOverlay');
  deleteConfirmMessage = document.getElementById('deleteCategoryConfirmMessage');
  deleteConfirmYesBtn = document.getElementById('deleteCategoryConfirmYes');
  deleteConfirmNoBtn = document.getElementById('deleteCategoryConfirmNo');
}

function setupEventListeners() {
  addCategoryBtn?.addEventListener('click', () => openCategoryModal(null));
  closeModalBtn?.addEventListener('click', closeCategoryModal);

  // Form cancel button
  const closeCategoryFormBtn = document.getElementById('closeCategoryFormBtn');
  closeCategoryFormBtn?.addEventListener('click', closeCategoryModal);

  categoryForm?.addEventListener('submit', saveCategory);

  // Sort order input - allow only numbers 1-99
  sortOrderInput?.addEventListener('input', (e) => {
    let value = e.target.value.replace(/[^0-9]/g, ''); // Remove non-digits
    if (value && parseInt(value) > 99) {
      value = '99';
    }
    e.target.value = value;
  });

  deleteConfirmYesBtn?.addEventListener('click', confirmDelete);
  deleteConfirmNoBtn?.addEventListener('click', cancelDelete);
}

// ================= LOAD CATEGORIES =================
async function loadCategories() {
  try {
    isLoading = true;
    categories = await ManageCategoriesAPI.getAllCategories();
    renderCategoryList();
  } catch (error) {
    console.error('Failed to load categories:', error);
    showError('Failed to load categories');
  } finally {
    isLoading = false;
  }
}

// ================= RENDER CATEGORY LIST =================
function renderCategoryList() {
  if (!categoryTableBody) return;

  categoryTableBody.innerHTML = '';

  if (categories.length === 0) {
    categoryTableBody.innerHTML = `
      <tr>
        <td colspan="3" style="text-align: center; padding: 20px; color: #999;">
          No categories yet. Click "Add Category" to create one.
        </td>
      </tr>
    `;
    return;
  }

  categories.forEach((category) => {
    const row = document.createElement('tr');
    row.className = 'category_row';

    row.innerHTML = `
      <td class="category_name">${escapeHtml(category.name)}</td>
      <td class="category_sort_order">${category.sort_order}</td>
      <td class="category_actions">
        <button class="action_btn edit_btn" data-id="${category.id}" title="Edit">Edit</button>
        <button class="action_btn delete_btn" data-id="${category.id}" title="Delete">Delete</button>
      </td>
    `;

    const editBtn = row.querySelector('.edit_btn');
    const deleteBtn = row.querySelector('.delete_btn');

    editBtn.addEventListener('click', () => openCategoryModal(category.id));
    deleteBtn.addEventListener('click', () => openDeleteConfirm(category.id));

    categoryTableBody.appendChild(row);
  });
}

// ================= MODAL OPERATIONS =================
function openCategoryModal(categoryId = null) {
  editingCategoryId = categoryId;

  if (categoryId === null) {
    // Create new category
    categoryForm.reset();
    categoryNameInput.focus();
  } else {
    // Edit existing category
    const category = categories.find(c => c.id === categoryId);
    if (category) {
      categoryNameInput.value = category.name;
      sortOrderInput.value = category.sort_order;
    }
  }

  showModal();
}

function closeCategoryModal() {
  hideModal();
  editingCategoryId = null;
  categoryForm.reset();
}

function showModal() {
  modalOverlay?.classList.add('active');
  categoryModal?.classList.add('active');
}

function hideModal() {
  modalOverlay?.classList.remove('active');
  categoryModal?.classList.remove('active');
}

// ================= FORM SUBMISSION =================
async function saveCategory(e) {
  e.preventDefault();

  const name = categoryNameInput.value.trim();
  const sortOrder = parseInt(sortOrderInput.value) || 1;

  // Validation
  if (!name) {
    showFieldError(categoryNameInput, 'Category name is required');
    return;
  }

  if (sortOrder < 1 || sortOrder > 99) {
    showFieldError(sortOrderInput, 'Sort order must be between 1 and 99');
    return;
  }

  try {
    isLoading = true;
    clearFieldErrors();

    const categoryData = { name, sort_order: sortOrder };

    if (editingCategoryId === null) {
      // Create new
      await ManageCategoriesAPI.createCategory(categoryData);
      showSuccess('Category created successfully');
    } else {
      // Update existing
      await ManageCategoriesAPI.updateCategory(editingCategoryId, categoryData);
    }

    closeCategoryModal();
    await loadCategories();

    // Notify parent that categories changed
    window.dispatchEvent(new CustomEvent('categoriesChanged'));
  } catch (error) {
    handleSaveError(error);
  } finally {
    isLoading = false;
  }
}

// ================= DELETE OPERATIONS =================
function openDeleteConfirm(categoryId) {
  pendingDeleteId = categoryId;
  const category = categories.find(c => c.id === categoryId);

  if (deleteConfirmMessage) {
    deleteConfirmMessage.textContent = `Are you sure you want to delete the category "${escapeHtml(category.name)}"?`;
  }

  deleteConfirmOverlay?.classList.add('active');
}

function cancelDelete() {
  pendingDeleteId = null;
  deleteConfirmOverlay?.classList.remove('active');
}

async function confirmDelete() {
  if (pendingDeleteId === null) return;

  try {
    isLoading = true;
    await ManageCategoriesAPI.deleteCategory(pendingDeleteId);

    cancelDelete();
    showSuccess('Category deleted successfully');
    await loadCategories();

    // Notify parent that categories changed
    window.dispatchEvent(new CustomEvent('categoriesChanged'));
  } catch (error) {
    cancelDelete();

    if (error.status === 409) {
      showError(`Cannot delete: ${error.message} (${error.faq_count} FAQ(s) in this category)`);
    } else {
      showError('Failed to delete category');
    }
  } finally {
    isLoading = false;
  }
}

// ================= NOTIFICATION SYSTEM =================
function showSuccess(message) {
  // Using the existing toast system if available
  if (window.Toast && typeof window.Toast.success === 'function') {
    window.Toast.success(message);
  } else {
    alert(message); // Fallback
  }
}

function showError(message) {
  if (window.Toast && typeof window.Toast.error === 'function') {
    window.Toast.error(message);
  } else {
    alert(`Error: ${message}`);
  }
}

function showFieldError(field, message) {
  const errorElement = field.nextElementSibling;
  if (errorElement && errorElement.classList.contains('field_error')) {
    errorElement.textContent = message;
    errorElement.classList.remove('hidden');
  }
  field.classList.add('input_error');
}

function clearFieldErrors() {
  document.querySelectorAll('.field_error').forEach(el => {
    el.classList.add('hidden');
  });
  document.querySelectorAll('.input_error').forEach(el => {
    el.classList.remove('input_error');
  });
}

function handleSaveError(error) {
  if (error.errors) {
    // Field-specific errors
    Object.keys(error.errors).forEach((field) => {
      const input = document.getElementById(field + 'Input') || document.getElementById(field);
      if (input) {
        showFieldError(input, error.errors[field][0]);
      }
    });
  } else if (error.message) {
    showError(error.message);
  } else {
    showError('Failed to save category');
  }
}

// ================= UTILITY FUNCTIONS =================
function escapeHtml(text) {
  const div = document.createElement('div');
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
