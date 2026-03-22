/**
 * FAQ Management - Admin CRUD
 * Handles FAQ management with database persistence via API
 */

// ================= IMPORTS =================
import FaqAPI from '../../api/faqApi.js';
import Toast from '../../utils/toast.js';

// ================= STATE =================
let faqs = {};
let editingFaqId = null;
let pendingDeleteId = null;
let isLoading = false;

// ================= ELEMENTS =================
const faqsContainer = document.getElementById('faqsContainer');
const emptyFaqsText = document.getElementById('emptyFaqsText');
const addFaqBtn = document.getElementById('add_faq_btn');
const faqModalOverlay = document.getElementById('faqModalOverlay');
const faqModal = document.getElementById('faqModal');
const faqModalTitle = document.getElementById('faqModalTitle');
const faqCategorySelect = document.getElementById('faqCategory');
const faqQuestionInput = document.getElementById('faqQuestion');
const faqAnswerInput = document.getElementById('faqAnswer');
const saveFaqBtn = faqModal.querySelector('.save_faq');
const cancelFaqBtn = faqModal.querySelector('.cancel_faq');
const deleteFaqBtn = document.getElementById('deleteFaqBtn');
const deleteFaqModalOverlay = document.getElementById('deleteFaqModalOverlay');
const deleteFaqConfirmBtn = document.getElementById('deleteFaqConfirmBtn');

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
  loadFaqs();
});

addFaqBtn.addEventListener('click', () => {
  openFaqModal();
});

saveFaqBtn.addEventListener('click', () => {
  saveFaq();
});

cancelFaqBtn.addEventListener('click', () => {
  closeFaqModal();
});

deleteFaqBtn.addEventListener('click', () => {
  if (editingFaqId) {
    openDeleteConfirmModal();
  }
});

deleteFaqConfirmBtn.addEventListener('click', () => {
  confirmDeleteFaq();
});

faqModalOverlay.addEventListener('click', (e) => {
  if (e.target === faqModalOverlay) {
    closeFaqModal();
  }
});

deleteFaqModalOverlay.addEventListener('click', (e) => {
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
    console.error('Error loading FAQs:', error);
    Toast.error('Failed to load FAQs');
  } finally {
    isLoading = false;
  }
}

// ================= RENDER FAQS =================
function renderFaqs() {
  faqsContainer.innerHTML = '';

  // Check if we have any FAQs
  const totalFaqs = Object.values(faqs).reduce((sum, category) => sum + category.length, 0);

  if (totalFaqs === 0) {
    emptyFaqsText.style.display = 'block';
    return;
  }

  emptyFaqsText.style.display = 'none';

  // Render each category with its FAQs
  Object.entries(faqs).forEach(([category, faqList]) => {
    const categoryGroup = document.createElement('div');
    categoryGroup.className = 'faq_category_group';

    const categoryHeader = document.createElement('h3');
    categoryHeader.className = 'faq_category_header';
    categoryHeader.textContent = category;
    categoryGroup.appendChild(categoryHeader);

    const faqList_ul = document.createElement('div');
    faqList_ul.className = 'faq_list';

    faqList.forEach((faq) => {
      const faqCard = createFaqCard(faq);
      faqList_ul.appendChild(faqCard);
    });

    categoryGroup.appendChild(faqList_ul);
    faqsContainer.appendChild(categoryGroup);
  });
}

// ================= CREATE FAQ CARD =================
function createFaqCard(faq) {
  const card = document.createElement('div');
  card.className = 'faq_card';
  card.dataset.faqId = faq.id;

  const questionLabel = document.createElement('h4');
  questionLabel.className = 'faq_card_question';
  questionLabel.textContent = faq.question;

  const answerPreview = document.createElement('p');
  answerPreview.className = 'faq_card_answer_preview';
  answerPreview.textContent = truncateText(faq.answer, 100);

  const actionButtons = document.createElement('div');
  actionButtons.className = 'faq_card_actions';

  const editBtn = document.createElement('button');
  editBtn.className = 'faq_card_edit_btn';
  editBtn.textContent = 'Edit';
  editBtn.addEventListener('click', () => {
    editFaq(faq);
  });

  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'faq_card_delete_btn';
  deleteBtn.textContent = 'Delete';
  deleteBtn.addEventListener('click', () => {
    editingFaqId = faq.id;
    pendingDeleteId = faq.id;
    openDeleteConfirmModal();
  });

  actionButtons.appendChild(editBtn);
  actionButtons.appendChild(deleteBtn);

  card.appendChild(questionLabel);
  card.appendChild(answerPreview);
  card.appendChild(actionButtons);

  return card;
}

// ================= TRUNCATE TEXT =================
function truncateText(text, maxLength) {
  if (text.length > maxLength) {
    return text.substring(0, maxLength) + '...';
  }
  return text;
}

// ================= OPEN FAQ MODAL =================
function openFaqModal(faq = null) {
  editingFaqId = faq ? faq.id : null;

  if (faq) {
    faqModalTitle.textContent = 'Edit FAQ';
    faqCategorySelect.value = faq.category;
    faqQuestionInput.value = faq.question;
    faqAnswerInput.value = faq.answer;
    deleteFaqBtn.classList.remove('btn_hidden');
  } else {
    faqModalTitle.textContent = 'Add New FAQ';
    faqCategorySelect.value = '';
    faqQuestionInput.value = '';
    faqAnswerInput.value = '';
    deleteFaqBtn.classList.add('btn_hidden');
  }

  // Clear error messages
  clearErrors();

  faqModalOverlay.classList.add('active');
  faqModal.classList.add('active');
}

// ================= CLOSE FAQ MODAL =================
function closeFaqModal() {
  faqModalOverlay.classList.remove('active');
  faqModal.classList.remove('active');
  editingFaqId = null;
  clearErrors();
}

// ================= SAVE FAQ =================
async function saveFaq() {
  // Validate inputs
  if (!validateFaqForm()) {
    return;
  }

  const faqData = {
    category: faqCategorySelect.value,
    question: faqQuestionInput.value,
    answer: faqAnswerInput.value,
    is_active: true,
  };

  try {
    if (editingFaqId) {
      // Update existing FAQ
      await FaqAPI.updateFaq(editingFaqId, faqData);
      Toast.success('FAQ updated successfully');
    } else {
      // Create new FAQ
      await FaqAPI.createFaq(faqData);
      Toast.success('FAQ created successfully');
    }

    closeFaqModal();
    loadFaqs();
  } catch (error) {
    console.error('Error saving FAQ:', error);
    Toast.error('Failed to save FAQ');
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
    Toast.success('FAQ deleted successfully');
    closeDeleteConfirmModal();
    editingFaqId = null;
    pendingDeleteId = null;
    loadFaqs();
  } catch (error) {
    console.error('Error deleting FAQ:', error);
    Toast.error('Failed to delete FAQ');
  }
}

// ================= OPEN DELETE CONFIRM MODAL =================
function openDeleteConfirmModal() {
  deleteFaqModalOverlay.classList.add('active');
}

// ================= CLOSE DELETE CONFIRM MODAL =================
function closeDeleteConfirmModal() {
  deleteFaqModalOverlay.classList.remove('active');
  pendingDeleteId = null;
}

// ================= VALIDATE FAQ FORM =================
function validateFaqForm() {
  clearErrors();
  let isValid = true;

  if (!faqCategorySelect.value) {
    document.getElementById('faqCategoryError').classList.remove('hidden');
    isValid = false;
  }

  if (!faqQuestionInput.value.trim()) {
    document.getElementById('faqQuestionError').classList.remove('hidden');
    isValid = false;
  }

  if (!faqAnswerInput.value.trim()) {
    document.getElementById('faqAnswerError').classList.remove('hidden');
    isValid = false;
  }

  return isValid;
}

// ================= CLEAR ERRORS =================
function clearErrors() {
  document.getElementById('faqCategoryError').classList.add('hidden');
  document.getElementById('faqQuestionError').classList.add('hidden');
  document.getElementById('faqAnswerError').classList.add('hidden');
}
