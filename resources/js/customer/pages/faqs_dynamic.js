/**
 * FAQs Page - Dynamic Rendering
 * Fetches FAQs from API and renders accordion structure dynamically
 */

// ================= IMPORTS =================
import FaqAPI from '../../api/faqApi.js';

// ================= STATE =================
let faqs = {};
let currentOpenFaq = null;

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
  loadAndRenderFaqs();
});

// ================= LOAD AND RENDER FAQs =================
async function loadAndRenderFaqs() {
  try {
    // Fetch FAQs from API
    faqs = await FaqAPI.getAllFaqs();
    renderFaqAccordion();
    setupAccordionListeners();
    handleSmoothScroll();
  } catch (error) {
    console.error('Error loading FAQs:', error);
  }
}

// ================= RENDER FAQ ACCORDION =================
function renderFaqAccordion() {
  const container = document.querySelector('.faqs_accordion_wrapper');

  // Preserve the opening section and clear content after it
  const openingSection = container.querySelector('.faqs_opening');
  let contentToKeep = '';

  // If opening section exists, keep it
  if (openingSection) {
    contentToKeep = openingSection.outerHTML;
    container.innerHTML = contentToKeep;
  }

  // Create accordion items for each category
  Object.entries(faqs).forEach(([category, faqList]) => {
    // Create category header
    const categoryHeader = document.createElement('h2');
    categoryHeader.style.marginTop = '40px';
    categoryHeader.textContent = category;
    container.appendChild(categoryHeader);

    // Create FAQ items
    faqList.forEach((faq) => {
      const faqItem = createFaqItem(faq);
      container.appendChild(faqItem);
    });
  });
}

// ================= CREATE FAQ ITEM =================
function createFaqItem(faq) {
  const faqItem = document.createElement('div');
  faqItem.className = 'faq_item';

  const details = document.createElement('details');
  details.dataset.faqId = faq.id;

  const summary = document.createElement('summary');
  summary.textContent = faq.question;

  const content = document.createElement('div');
  content.className = 'faq_content';
  content.innerHTML = faq.answer.replace(/\n/g, '<br/>');

  details.appendChild(summary);
  details.appendChild(content);
  faqItem.appendChild(details);

  return faqItem;
}

// ================= SETUP ACCORDION LISTENERS =================
function setupAccordionListeners() {
  const faqItems = document.querySelectorAll('.faq_item details');

  faqItems.forEach((item) => {
    item.addEventListener('toggle', (e) => {
      // Optional: close other items when opening a new one (disabled by default)
      // if (e.target.open) {
      //     faqItems.forEach((otherItem) => {
      //         if (otherItem !== item) {
      //             otherItem.open = false;
      //         }
      //     });
      // }
    });
  });
}

// ================= HANDLE SMOOTH SCROLL =================
function handleSmoothScroll() {
  if (window.location.hash) {
    const target = document.querySelector(window.location.hash);
    if (target && target.closest('.faq_item')) {
      const details = target.closest('details');
      if (details) {
        details.open = true;
        setTimeout(() => {
          target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
      }
    }
  }
}
