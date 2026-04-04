/**
 * FAQ Management - Admin CRUD
 * Handles FAQ management with database persistence via API
 * Fixed: Double submission prevention
 */

// ================= IMPORTS =================
import FaqAPI from "../../api/faqApi.js";
import ManageCategoriesAPI from "../../api/manage_categories_api.js";
import Toast from "../../utils/toast.js";

// ================= STATE =================
let faqs = {};
let categories = [];
let editingFaqId = null;
let pendingDeleteId = null;
let isLoading = false;
let isSaving = false; // Prevent double submissions

// ================= ELEMENTS =================
const faqsContainer = document.getElementById("faqsContainer");
const emptyFaqsText = document.getElementById("emptyFaqsText");
const addFaqBtn = document.getElementById("add_faq_btn");
const faqModalOverlay = document.getElementById("faqModalOverlay");
const faqModal = document.getElementById("faqModal");
const faqModalTitle = document.getElementById("faqModalTitle");
const faqCategorySelect = document.getElementById("faqCategory");
const faqQuestionInput = document.getElementById("faqQuestion");
const faqAnswerInput = document.getElementById("faqAnswer");
const saveFaqBtn = faqModal.querySelector(".save_faq");
const cancelFaqBtn = faqModal.querySelector(".cancel_faq");
const deleteFaqBtn = document.getElementById("deleteFaqBtn");
const deleteFaqModalOverlay = document.getElementById("deleteFaqModalOverlay");
const deleteFaqConfirmBtn = document.getElementById("deleteFaqConfirmBtn");

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    await populateCategoryDropdown();
    loadFaqs();
});

// Listen for category changes from ManageCategoriesModal
window.addEventListener("categoriesChanged", async () => {
    await populateCategoryDropdown();
    loadFaqs();
});

addFaqBtn.addEventListener("click", () => {
    openFaqModal();
});

saveFaqBtn.addEventListener("click", () => {
    saveFaq();
});

cancelFaqBtn.addEventListener("click", () => {
    closeFaqModal();
});

deleteFaqBtn.addEventListener("click", () => {
    if (editingFaqId) {
        openDeleteConfirmModal();
    }
});

deleteFaqConfirmBtn.addEventListener("click", () => {
    confirmDeleteFaq();
});

faqModalOverlay.addEventListener("click", (e) => {
    if (e.target === faqModalOverlay) {
        closeFaqModal();
    }
});

deleteFaqModalOverlay.addEventListener("click", (e) => {
    if (e.target === deleteFaqModalOverlay) {
        closeDeleteConfirmModal();
    }
});

// ================= LOAD FAQS =================
async function loadFaqs() {
    try {
        isLoading = true;
        const response = await FaqAPI.getAllFaqs();
        faqs = response;
        renderFaqs();
    } catch (error) {
        console.error("Error loading FAQs:", error);
        Toast.error("Failed to load FAQs");
    } finally {
        isLoading = false;
    }
}

// ================= RENDER FAQS =================
function renderFaqs() {
    faqsContainer.innerHTML = "";

    // Check if we have any categories
    if (categories.length === 0) {
        emptyFaqsText.style.display = "block";
        return;
    }

    emptyFaqsText.style.display = "none";

    // Render each category as a box
    categories.forEach((category) => {
        const categoryBox = createCategoryBox(category);
        faqsContainer.appendChild(categoryBox);
    });
}

// ================= CREATE CATEGORY BOX =================
function createCategoryBox(category) {
    const box = document.createElement("div");
    box.className = "category_box";
    box.dataset.categoryId = category.id;

    const header = document.createElement("div");
    header.className = "category_box_header";

    const title = document.createElement("span");
    title.textContent = category.name;

    const sparkle = document.createElement("span");
    sparkle.className = "category_sparkle";
    sparkle.textContent = "✦";

    header.appendChild(title);
    header.appendChild(sparkle);

    const content = document.createElement("div");
    content.className = "category_box_content";

    const categoryFaqs = faqs[category.name] || [];

    if (categoryFaqs.length === 0) {
        const emptyMessage = document.createElement("p");
        emptyMessage.className = "category_box_empty";
        emptyMessage.textContent = "No Questions and Answers Yet.";
        content.appendChild(emptyMessage);
    } else {
        const faqList = document.createElement("div");
        faqList.className = "category_box_faq_list";

        categoryFaqs.forEach((faq, index) => {
            const faqItem = createFaqAccordionItem(faq, index);
            faqList.appendChild(faqItem);
        });

        content.appendChild(faqList);
    }

    box.appendChild(header);
    box.appendChild(content);

    return box;
}

// ================= CREATE FAQ ACCORDION ITEM =================
function createFaqAccordionItem(faq, index) {
    const item = document.createElement("div");
    item.className = "faq_accordion_item";
    item.dataset.faqId = faq.id;

    const header = document.createElement("div");
    header.className = "faq_accordion_header";

    const question = document.createElement("div");
    question.className = "faq_accordion_question";
    question.textContent = faq.question;

    const toggle = document.createElement("button");
    toggle.className = "faq_accordion_toggle";
    toggle.innerHTML = "▼";
    toggle.type = "button";

    header.appendChild(question);
    header.appendChild(toggle);

    const answerWrapper = document.createElement("div");
    answerWrapper.className = "faq_accordion_answer";

    const answer = document.createElement("p");
    answer.className = "faq_accordion_answer_text";
    answer.textContent = faq.answer;

    const editContainer = document.createElement("div");
    editContainer.className = "faq_edit_container";

    const editBtn = document.createElement("button");
    editBtn.className = "faq_item_edit_btn";
    editBtn.textContent = "Edit";
    editBtn.type = "button";
    editBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        editFaq(faq);
    });

    editContainer.appendChild(editBtn);
    answerWrapper.appendChild(answer);
    answerWrapper.appendChild(editContainer);

    item.appendChild(header);
    item.appendChild(answerWrapper);

    // Toggle accordion
    header.addEventListener("click", () => {
        item.classList.toggle("active");
    });

    return item;
}

// ================= POPULATE CATEGORY DROPDOWN =================
async function populateCategoryDropdown() {
    try {
        categories = await ManageCategoriesAPI.getAllCategories();

        // Clear existing options (keep the empty placeholder)
        const existingOptions = Array.from(faqCategorySelect.options);
        for (let i = existingOptions.length - 1; i >= 1; i--) {
            faqCategorySelect.removeChild(faqCategorySelect.options[i]);
        }

        // Add categories from API
        categories.forEach((category) => {
            const option = document.createElement("option");
            option.value = category.id;
            option.textContent = category.name;
            faqCategorySelect.appendChild(option);
        });
    } catch (error) {
        console.error("Error populating category dropdown:", error);
        Toast.error("Failed to load categories");
    }
}

// ================= OPEN FAQ MODAL =================
function openFaqModal(faq = null) {
    editingFaqId = faq ? faq.id : null;
    isSaving = false; // Reset saving state

    if (faq) {
        faqModalTitle.textContent = "Edit FAQ";
        faqCategorySelect.value = faq.faq_category_id || "";
        faqQuestionInput.value = faq.question;
        faqAnswerInput.value = faq.answer;
        deleteFaqBtn.classList.remove("btn_hidden");
    } else {
        faqModalTitle.textContent = "Add New FAQ";
        faqCategorySelect.value = "";
        faqQuestionInput.value = "";
        faqAnswerInput.value = "";
        deleteFaqBtn.classList.add("btn_hidden");
    }

    // Clear error messages and re-enable save button
    clearErrors();
    setSaveButtonState(false);

    faqModalOverlay.classList.add("active");
    faqModal.classList.add("active");
}

// ================= CLOSE FAQ MODAL =================
function closeFaqModal() {
    faqModalOverlay.classList.remove("active");
    faqModal.classList.remove("active");
    editingFaqId = null;
    isSaving = false;
    clearErrors();
    setSaveButtonState(false);
}

// ================= SET SAVE BUTTON STATE =================
function setSaveButtonState(disabled) {
    saveFaqBtn.disabled = disabled;
    if (disabled) {
        saveFaqBtn.textContent = "Saving...";
    } else {
        saveFaqBtn.textContent = "Save";
    }
}

// ================= SAVE FAQ =================
async function saveFaq() {
    // Prevent double submission
    if (isSaving) {
        return;
    }

    // Validate inputs
    if (!validateFaqForm()) {
        return;
    }

    const faqData = {
        faq_category_id: parseInt(faqCategorySelect.value),
        question: faqQuestionInput.value.trim(),
        answer: faqAnswerInput.value.trim(),
        is_active: true,
    };

    try {
        isSaving = true;
        setSaveButtonState(true);

        if (editingFaqId) {
            // Update existing FAQ
            await FaqAPI.updateFaq(editingFaqId, faqData);
            Toast.success("FAQ updated successfully");
        } else {
            // Create new FAQ
            await FaqAPI.createFaq(faqData);
            Toast.success("FAQ created successfully");
        }

        closeFaqModal();
        loadFaqs();
    } catch (error) {
        console.error("Error saving FAQ:", error);
        Toast.error("Failed to save FAQ");
        isSaving = false;
        setSaveButtonState(false);
    }
}

// ================= EDIT FAQ =================
function editFaq(faq) {
    editingFaqId = faq.id;
    openFaqModal(faq);
}

// ================= DELETE FAQ =================
async function confirmDeleteFaq() {
    if (!pendingDeleteId) return;

    try {
        await FaqAPI.deleteFaq(pendingDeleteId);
        Toast.success("FAQ deleted successfully");
        closeDeleteConfirmModal();
        closeFaqModal();
        editingFaqId = null;
        pendingDeleteId = null;
        loadFaqs();
    } catch (error) {
        console.error("Error deleting FAQ:", error);
        Toast.error("Failed to delete FAQ");
    }
}

// ================= OPEN DELETE CONFIRM MODAL =================
function openDeleteConfirmModal() {
    pendingDeleteId = editingFaqId;
    deleteFaqModalOverlay.classList.add("active");
}

// ================= CLOSE DELETE CONFIRM MODAL =================
function closeDeleteConfirmModal() {
    deleteFaqModalOverlay.classList.remove("active");
    pendingDeleteId = null;
}

// ================= VALIDATE FAQ FORM =================
function validateFaqForm() {
    clearErrors();
    let isValid = true;

    if (!faqCategorySelect.value) {
        document.getElementById("faqCategoryError").classList.remove("hidden");
        isValid = false;
    }

    if (!faqQuestionInput.value.trim()) {
        document.getElementById("faqQuestionError").classList.remove("hidden");
        isValid = false;
    }

    if (!faqAnswerInput.value.trim()) {
        document.getElementById("faqAnswerError").classList.remove("hidden");
        isValid = false;
    }

    return isValid;
}

// ================= CLEAR ERRORS =================
function clearErrors() {
    document.getElementById("faqCategoryError").classList.add("hidden");
    document.getElementById("faqQuestionError").classList.add("hidden");
    document.getElementById("faqAnswerError").classList.add("hidden");
}
