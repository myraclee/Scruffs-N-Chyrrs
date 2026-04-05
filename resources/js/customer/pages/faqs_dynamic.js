/**
 * FAQs Page - Dynamic Rendering
 * Fetches FAQs from API and renders accordion structure dynamically
 */

// ================= IMPORTS =================
import FaqAPI from "../../api/faqApi.js";

// ================= STATE =================
let faqs = {};

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    loadAndRenderFaqs();
});

// ================= LOAD AND RENDER FAQs =================
async function loadAndRenderFaqs() {
    try {
        faqs = await FaqAPI.getAllFaqs();
        renderFaqAccordion();
        setupAccordionListeners();
        handleSmoothScroll();
    } catch (error) {
        console.error("Error loading FAQs:", error);
    }
}

// ================= RENDER FAQ ACCORDION =================
function renderFaqAccordion() {
    const container = document.querySelector(".faqs_accordion_wrapper");
    container.innerHTML = "";

    Object.entries(faqs).forEach(([category, faqList]) => {
        const categoryHeader = document.createElement("h2");
        categoryHeader.textContent = category;
        container.appendChild(categoryHeader);

        faqList.forEach((faq, index) => {
            const faqItem = createFaqItem(faq, index);
            container.appendChild(faqItem);
        });
    });
}

// ================= CREATE FAQ ITEM =================
function createFaqItem(faq, index) {
    const faqItem = document.createElement("div");
    faqItem.className = "faq_item";
    faqItem.dataset.faqId = faq.id;
    faqItem.style.setProperty("--item-index", index);

    const summary = document.createElement("div");
    summary.className = "faq_summary";
    summary.setAttribute("role", "button");
    summary.setAttribute("aria-expanded", "false");
    summary.innerHTML = `
        <span class="faq_question">${faq.question}</span>
        <span class="faq_arrow">▼</span>
    `;

    const contentWrapper = document.createElement("div");
    contentWrapper.className = "faq_content_wrapper";

    const content = document.createElement("div");
    content.className = "faq_content";
    content.innerHTML = faq.answer.replace(/\n/g, "<br/>");

    contentWrapper.appendChild(content);
    faqItem.appendChild(summary);
    faqItem.appendChild(contentWrapper);

    return faqItem;
}

// ================= SETUP ACCORDION LISTENERS =================
function setupAccordionListeners() {
    const container = document.querySelector(".faqs_accordion_wrapper");

    container.addEventListener("click", (e) => {
        const summary = e.target.closest(".faq_summary");
        if (!summary) return;

        const faqItem = summary.closest(".faq_item");
        const wrapper = faqItem.querySelector(".faq_content_wrapper");
        const isOpen = faqItem.classList.contains("open");

        if (isOpen) {
            // --- CLOSE ---
            // Lock the current height first so the transition has a start point
            wrapper.style.maxHeight = wrapper.scrollHeight + "px";
            // Force reflow so the browser registers the value before we change it
            wrapper.offsetHeight;
            wrapper.style.maxHeight = "0";
            wrapper.style.opacity = "0";
            faqItem.classList.remove("open");
            summary.setAttribute("aria-expanded", "false");
        } else {
            // --- OPEN ---
            wrapper.style.maxHeight = wrapper.scrollHeight + "px";
            wrapper.style.opacity = "1";
            faqItem.classList.add("open");
            summary.setAttribute("aria-expanded", "true");

            // Clean up inline max-height after transition so content
            // can reflow naturally if the window resizes
            wrapper.addEventListener(
                "transitionend",
                () => {
                    if (faqItem.classList.contains("open")) {
                        wrapper.style.maxHeight = "none";
                    }
                },
                { once: true },
            );
        }
    });
}

// ================= HANDLE SMOOTH SCROLL =================
function handleSmoothScroll() {
    if (!window.location.hash) return;

    const target = document.querySelector(window.location.hash);
    if (!target) return;

    const faqItem = target.closest(".faq_item");
    if (!faqItem) return;

    const summary = faqItem.querySelector(".faq_summary");
    const wrapper = faqItem.querySelector(".faq_content_wrapper");

    wrapper.style.maxHeight = wrapper.scrollHeight + "px";
    wrapper.style.opacity = "1";
    faqItem.classList.add("open");
    summary.setAttribute("aria-expanded", "true");

    setTimeout(() => {
        target.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }, 100);
}
