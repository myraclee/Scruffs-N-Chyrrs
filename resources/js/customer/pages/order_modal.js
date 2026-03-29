/**
 * Order Modal - Customer Ordering System
 * Handles modal interactions, pricing calculations, and order submission
 */

// ================= IMPORTS =================
import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

// ================= STATE =================
let templateData = null;
let selectedProductId = null;
let selectedOptions = {}; // { optionId: optionTypeId }
let isSubmitting = false;
let userAuthenticated = false;

// ================= DOM ELEMENTS =================
let modal = null;
let overlay = null;
let form = null;
let closeBtn = null;
let optionsContainer = null;
let quantityInput = null;
let qtyDecrement = null;
let qtyIncrement = null;
let rushFeeSelect = null;
let specialInstructions = null;
let instructionsCount = null;
let submitBtn = null;
let submitBtnText = null;
let submitBtnSpinner = null;
let priceDisplay = {};
let quantityInfo = null;
let authMessage = null;
let formMessage = null;
let productInfo = null;

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", () => {
    initializeModal();
    setupEventListeners();
    checkUserAuthentication();
});

/**
 * Initialize modal DOM references
 */
function initializeModal() {
    modal = document.getElementById("orderModal");
    overlay = modal; // Modal element IS the overlay (has class order_modal_overlay)
    form = modal?.querySelector("#orderForm");
    closeBtn = modal?.querySelector("#closeOrderModal");
    optionsContainer = modal?.querySelector("#optionsContainer");
    quantityInput = modal?.querySelector("#quantityInput");
    qtyDecrement = modal?.querySelector("#qtyDecrement");
    qtyIncrement = modal?.querySelector("#qtyIncrement");
    rushFeeSelect = modal?.querySelector("#rushFeeSelect");
    specialInstructions = modal?.querySelector("#specialInstructions");
    instructionsCount = modal?.querySelector("#instructionsCount");
    submitBtn = modal?.querySelector("#submitOrderBtn");
    submitBtnText = modal?.querySelector("#submitBtnText");
    submitBtnSpinner = modal?.querySelector("#submitBtnSpinner");
    quantityInfo = modal?.querySelector("#quantityInfo");
    authMessage = modal?.querySelector("#authMessage");
    formMessage = modal?.querySelector("#formMessage");
    productInfo = modal?.querySelector("#productInfo");

    // Price display elements
    priceDisplay = {
        base: modal?.querySelector("#basePriceDisplay"),
        discount: modal?.querySelector("#discountDisplay"),
        discountRow: modal?.querySelector("#discountRow"),
        layoutFee: modal?.querySelector("#layoutFeeDisplay"),
        layoutFeeRow: modal?.querySelector("#layoutFeeRow"),
        rushFee: modal?.querySelector("#rushFeeDisplay"),
        rushFeeRow: modal?.querySelector("#rushFeeRow"),
        total: modal?.querySelector("#totalPriceDisplay"),
    };

    if (!modal || !form) {
        console.warn("Order modal elements not found in DOM");
        return;
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Poka-yoke: if this module is initialized more than once, do not rebind listeners.
    if (!form || form.dataset.orderModalListenersBound === "true") {
        return;
    }

    // Close modal
    closeBtn?.addEventListener("click", closeOrderModal);
    overlay?.addEventListener("click", (e) => {
        if (e.target === overlay) {
            closeOrderModal();
        }
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && overlay?.classList.contains("active")) {
            closeOrderModal();
        }
    });

    // Quantity controls
    qtyDecrement?.addEventListener("click", (e) => {
        e.preventDefault();
        const current = parseInt(quantityInput.value) || 1;
        const minOrder = templateData?.template?.min_order || 1;
        if (current > minOrder) {
            quantityInput.value = current - 1;
            updatePriceDisplay();
        }
    });

    qtyIncrement?.addEventListener("click", (e) => {
        e.preventDefault();
        quantityInput.value = (parseInt(quantityInput.value) || 1) + 1;
        updatePriceDisplay();
    });

    // Quantity input
    quantityInput?.addEventListener("change", updatePriceDisplay);
    quantityInput?.addEventListener("input", () => {
        const value = parseInt(quantityInput.value) || 1;
        const minOrder = templateData?.template?.min_order || 1;
        if (value < minOrder) {
            quantityInput.value = minOrder;
        }
        updatePriceDisplay();
    });

    // Rush fee selection
    rushFeeSelect?.addEventListener("change", updatePriceDisplay);

    // Special instructions character counter
    specialInstructions?.addEventListener("input", (e) => {
        const count = e.target.value.length;
        instructionsCount.textContent = `${count}/1000`;
    });

    // Form submission
    form?.addEventListener("submit", handleFormSubmit);

    // Mark listeners as bound so duplicate module instances cannot add a second submit handler.
    form.dataset.orderModalListenersBound = "true";
}

/**
 * Open order modal and load template for a product
 * @param {number} productId - Product to order
 */
export async function openOrderModal(productId) {
    try {
        if (!overlay) {
            console.error("Modal not initialized");
            return;
        }
        selectedProductId = productId;
        selectedOptions = {};
        formMessage.style.display = "none";

        // Show loading state
        overlay.classList.add("active");
        form.style.display = "none";
        authMessage.style.display = "none";
        optionsContainer.innerHTML =
            '<p style="text-align: center; color: #999;">Loading order configuration...</p>';

        // Fetch template
        templateData = await CustomerOrderAPI.getOrderTemplate(productId);

        // Render modal content
        renderProductInfo();
        renderOptions();
        renderRushFees();
        updateQuantityConstraints();
        updatePriceDisplay();

        // Show form or auth message
        if (userAuthenticated) {
            form.style.display = "block";
            authMessage.style.display = "none";
        } else {
            form.style.display = "none";
            authMessage.style.display = "block";
        }
    } catch (error) {
        console.error("Error opening order modal:", error);
        Toast.error("Failed to load order options. Please try again.");
        closeOrderModal();
    }
}

/**
 * Close order modal
 */
function closeOrderModal() {
    overlay?.classList.remove("active");
    form.reset();
    quantityInput.value = "1";
    specialInstructions.value = "";
    instructionsCount.textContent = "0/1000";
    selectedOptions = {};
    templateData = null;
    selectedProductId = null;
    isSubmitting = false;
}

/**
 * Check user authentication status
 */
function checkUserAuthentication() {
    // Check if user is authenticated by looking for a session indicator
    // This would typically come from the Blade template via a data attribute
    const userAuth = document.querySelector('meta[name="user-authenticated"]');
    userAuthenticated = userAuth?.getAttribute("content") === "true";
}

/**
 * Render product information (image, name, description)
 */
function renderProductInfo() {
    if (!templateData?.product) return;

    const product = templateData.product;
    const productImage = productInfo?.querySelector("#productInfoImage");
    const productName = productInfo?.querySelector("#productInfoName");
    const productDesc = productInfo?.querySelector("#productInfoDescription");

    if (productImage) {
        productImage.src = product.cover_image_path
            ? `/storage/${product.cover_image_path}`
            : "/images/placeholder.png";
        productImage.alt = product.name;
    }
    if (productName) productName.textContent = product.name;
    if (productDesc) productDesc.textContent = product.description;
}

/**
 * Render customization options from template
 */
function renderOptions() {
    if (!templateData?.template?.options) return;

    optionsContainer.innerHTML = "";

    templateData.template.options.forEach((option) => {
        const optionGroup = document.createElement("div");
        optionGroup.className = "order_modal_option_group";

        const optionLabel = document.createElement("div");
        optionLabel.className = "order_modal_option_label";
        optionLabel.textContent = option.label;
        optionGroup.appendChild(optionLabel);

        const typesContainer = document.createElement("div");
        typesContainer.className = "order_modal_option_types";

        option.option_types.forEach((optionType) => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "order_modal_option_type_btn";
            btn.textContent = optionType.type_name;
            btn.disabled = !optionType.is_available;

            if (optionType.is_available) {
                btn.addEventListener("click", () => {
                    selectOption(option.id, optionType.id, btn);
                });
            }

            typesContainer.appendChild(btn);
        });

        optionGroup.appendChild(typesContainer);
        optionsContainer.appendChild(optionGroup);
    });
}

/**
 * Handle option selection
 */
function selectOption(optionId, optionTypeId, btnElement) {
    // Deselect previous selection for this option
    const siblings = btnElement.parentElement.querySelectorAll(
        ".order_modal_option_type_btn",
    );
    siblings.forEach((btn) => btn.classList.remove("selected"));

    // Select current option
    btnElement.classList.add("selected");
    selectedOptions[optionId] = optionTypeId;

    // Update pricing
    updatePriceDisplay();

    // Mark required selections are made
    markFormTouched();
}

/**
 * Render rush fee options
 */
function renderRushFees() {
    if (!templateData?.rush_fees) return;

    rushFeeSelect.innerHTML = '<option value="">Standard Processing</option>';

    templateData.rush_fees.forEach((rushFee) => {
        const option = document.createElement("option");
        option.value = rushFee.id;

        const mainTimeframe = rushFee.timeframes?.[0];
        const timeframeLabel = mainTimeframe?.label || "Rush Processing";
        const timeframePercentage =
            mainTimeframe?.percentage || rushFee.percentage_increase || 0;

        option.textContent = `${rushFee.label} (${timeframeLabel}) +${timeframePercentage}%`;
        rushFeeSelect.appendChild(option);
    });
}

/**
 * Update quantity constraints based on minimum order
 */
function updateQuantityConstraints() {
    const minOrder = templateData?.template?.min_order || 1;
    quantityInput.min = minOrder;
    quantityInput.value = minOrder;

    if (minOrder > 1) {
        quantityInfo.innerHTML = `<br><small style="color: #999;">Minimum: ${minOrder}</small>`;
    }
}

/**
 * Calculate and display price in real time
 */
function updatePriceDisplay() {
    if (!templateData) return;

    // Skip price calculation if not all options are selected yet
    const template = templateData.template;
    if (template.options.length !== Object.keys(selectedOptions).length) {
        // Not all options selected - show placeholder or skip
        return;
    }

    try {
        const pricing = calculateOrderPrice();

        // Update price displays
        priceDisplay.base.textContent = `$${pricing.basePrice.toFixed(2)}`;

        if (pricing.discountAmount > 0) {
            priceDisplay.discountRow.style.display = "flex";
            priceDisplay.discount.textContent = `-$${pricing.discountAmount.toFixed(2)}`;
        } else {
            priceDisplay.discountRow.style.display = "none";
        }

        if (pricing.layoutFeeAmount > 0) {
            priceDisplay.layoutFeeRow.style.display = "flex";
            priceDisplay.layoutFee.textContent = `$${pricing.layoutFeeAmount.toFixed(2)}`;
        } else {
            priceDisplay.layoutFeeRow.style.display = "none";
        }

        if (pricing.rushFeeAmount > 0) {
            priceDisplay.rushFeeRow.style.display = "flex";
            priceDisplay.rushFee.textContent = `$${pricing.rushFeeAmount.toFixed(2)}`;
        } else {
            priceDisplay.rushFeeRow.style.display = "none";
        }

        priceDisplay.total.textContent = `$${pricing.totalPrice.toFixed(2)}`;
    } catch (error) {
        console.error("Error calculating price:", error);
    }
}

/**
 * Calculate final order price based on selected options, quantity, and rush fee
 * @returns {Object} Pricing breakdown
 */
function calculateOrderPrice() {
    const template = templateData.template;
    const quantity = parseInt(quantityInput.value) || 1;
    const selectedRushFeeId = rushFeeSelect.value;

    // 1. Build combination key from selected options
    const combinationKey = buildCombinationKey();

    // 2. Find matching pricing
    const pricingEntry = template.pricings.find(
        (p) => p.combination_key === combinationKey,
    );
    if (!pricingEntry) {
        throw new Error("No pricing found for selected options");
    }

    let basePrice = pricingEntry.price * quantity;

    // 3. Apply bulk discount
    let discountAmount = 0;
    const applicableDiscount = template.discounts.find(
        (d) => d.min_quantity <= quantity,
    );
    if (applicableDiscount) {
        discountAmount = applicableDiscount.price_reduction * quantity;
    }

    // 4. Add layout fee
    const layoutFeeAmount = template.layout_fee || 0;

    // 5. Add rush fee if selected
    let rushFeeAmount = 0;
    if (selectedRushFeeId) {
        const rushFee = templateData.rush_fees.find(
            (rf) => rf.id == selectedRushFeeId,
        );
        if (rushFee && rushFee.timeframes && rushFee.timeframes.length > 0) {
            // Use the first timeframe's percentage (matching what's displayed in the select)
            const timeframePercentage = rushFee.timeframes[0].percentage || 0;
            const orderValue = basePrice - discountAmount + layoutFeeAmount;
            rushFeeAmount = orderValue * (timeframePercentage / 100);
        }
    }

    const totalPrice =
        basePrice - discountAmount + layoutFeeAmount + rushFeeAmount;

    return {
        basePrice: Math.round(basePrice * 100) / 100,
        discountAmount: Math.round(discountAmount * 100) / 100,
        layoutFeeAmount: Math.round(layoutFeeAmount * 100) / 100,
        rushFeeAmount: Math.round(rushFeeAmount * 100) / 100,
        totalPrice: Math.round(totalPrice * 100) / 100,
    };
}

/**
 * Build combination key from selected options
 * Format: "1,2,3" where numbers are option type IDs
 */
function buildCombinationKey() {
    const ids = Object.values(selectedOptions);
    ids.sort((a, b) => a - b);
    return ids.join(",");
}

/**
 * Mark form as touched for validation purposes
 */
function markFormTouched() {
    // Could expand this for showing validation errors
}

/**
 * Validate all form inputs before submission
 * KAIZEN: Error proofing - guard against null templateData
 */
function validateForm() {
    // GUARD 1: Verify templateData exists (prevents Vite HMR issues)
    if (!templateData || !templateData.template) {
        showFormMessage(
            "Form data lost. Please refresh and try again.",
            "error",
        );
        return false;
    }

    const template = templateData.template;

    // Check all options are selected
    if (template.options.length !== Object.keys(selectedOptions).length) {
        showFormMessage("Please select all customization options", "error");
        return false;
    }

    // Check quantity is valid
    const quantity = parseInt(quantityInput.value) || 0;
    if (quantity < (template.min_order || 1)) {
        showFormMessage(
            `Minimum quantity is ${template.min_order || 1}`,
            "error",
        );
        return false;
    }

    return true;
}

/**
 * Show validation or status messages in the form
 */
function showFormMessage(message, type = "info") {
    formMessage.textContent = message;
    formMessage.className = `order_modal_message ${type}`;
    formMessage.style.display = "block";

    if (type !== "error") {
        setTimeout(() => {
            formMessage.style.display = "none";
        }, 3000);
    }
}

/**
 * Handle form submission
 */
async function handleFormSubmit(e) {
    e.preventDefault();

    // Cross-instance guard: if duplicate module instances exist, share lock via DOM dataset.
    if (form?.dataset.orderModalSubmitting === "true") {
        return;
    }

    // ========== KAIZEN POKA-YOKE: Authentication Guard ==========
    if (!userAuthenticated) {
        return;
    }

    // ========== KAIZEN POKA-YOKE: Submission Lock (SET FIRST!) ==========
    // CRITICAL: Set isSubmitting IMMEDIATELY to prevent race conditions.
    // Do NOT allow any code (even a few lines) between the check and set!
    // This prevents concurrent form submissions Completely.
    if (isSubmitting) {
        return;
    }
    isSubmitting = true; // ✓ SET IMMEDIATELY (Error-proofing)
    if (form) {
        form.dataset.orderModalSubmitting = "true";
    }

    // Disable button immediately to provide visual feedback
    submitBtn.disabled = true;
    submitBtnText.style.display = "none";
    submitBtnSpinner.style.display = "inline-block";

    // Wrap ALL logic in try/catch to prevent unhandled promise rejections
    try {
        // Validate form (moved inside try to catch validation errors)
        const isFormValid = validateForm();

        if (!isFormValid) {
            return;
        }

        // GUARD: Verify templateData still exists (preventing race conditions)
        if (!templateData || !templateData.template) {
            throw new Error(
                "Form data lost (templateData is null). Please refresh the page and try again.",
            );
        }

        const pricing = calculateOrderPrice();
        const orderData = {
            product_id: selectedProductId,
            order_template_id: templateData.template.id,
            selected_options: selectedOptions,
            quantity: parseInt(quantityInput.value),
            rush_fee_id: rushFeeSelect.value || null,
            special_instructions: specialInstructions.value.trim() || null,
        };

        // Submit order
        const result = await CustomerOrderAPI.submitOrder(orderData);

        if (result.success) {
            Toast.success(result.message || "Order placed successfully!");
            closeOrderModal();
        } else {
            showFormMessage(result.message || "Failed to place order", "error");
        }
    } catch (error) {
        console.error("Order submission failed:", error);
        showFormMessage("An error occurred while placing your order", "error");
    } finally {
        isSubmitting = false;
        if (form) {
            form.dataset.orderModalSubmitting = "false";
        }
        submitBtn.disabled = false;
        submitBtnSpinner.style.display = "none";
        submitBtnText.style.display = "inline";
    }
}
