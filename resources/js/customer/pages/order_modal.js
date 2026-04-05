import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

document.addEventListener("DOMContentLoaded", () => {
    const orderModal = document.getElementById("orderModal");
    if (!orderModal) return;

    const container = document.querySelector(".product_detail_container");
    const productData = container?.getAttribute("data-product");
    const productId = Number(container?.getAttribute("data-product-id"));

    if (!productId || !productData) {
        console.warn("Product detail context not found for order modal.");
        return;
    }

    let product = null;
    let templatePayload = null;
    let cartPayload = null;

    try {
        product = JSON.parse(productData);
    } catch (error) {
        console.error("Failed to parse product payload for order modal", error);
    }

    const closeBtn = document.getElementById("closeOrderModal");
    const addItemBtn = document.getElementById("addItemBtn");
    const submitBtn = document.getElementById("submitMasterOrderBtn");
    const quantityInput = document.getElementById("itemQuantity");
    const notesInput = document.getElementById("itemFileName");
    const rushFeeSelect = document.getElementById("rushFeeSelect");
    const driveLinkInput = document.getElementById("generalDriveLink");
    const optionsContainer = document.getElementById("dynamicOptionsContainer");
    const cartContainer = document.getElementById("cartItemsContainer");
    const emptyMessage = document.getElementById("emptyCartMsg");
    const grandTotalDisplay = document.getElementById("grandTotalDisplay");
    const modalTitle = document.getElementById("dynamicModalTitle");
    const orderPlacementFeedback = document.getElementById(
        "orderPlacementFeedback",
    );

    const formatMoney = (amount) =>
        new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP",
        }).format(Number(amount || 0));

    const escapeHtml = (value) =>
        String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");

    function clearOrderPlacementFeedback() {
        if (!orderPlacementFeedback) {
            return;
        }

        orderPlacementFeedback.hidden = true;
        orderPlacementFeedback.classList.remove("active");
        orderPlacementFeedback.innerHTML = "";
    }

    function renderShortageFeedback(shortages, fallbackMessage) {
        if (!orderPlacementFeedback) {
            return false;
        }

        const validShortages = Array.isArray(shortages)
            ? shortages.filter((item) => item && item.material_name)
            : [];

        const shortageListItems = validShortages.map((item) => {
            const materialName = escapeHtml(item.material_name);
            const required = Number(item.required || 0);
            const available = Number(item.available || 0);
            const deficit = Math.max(
                0,
                Number(item.deficit ?? required - available),
            );

            return `<li><strong>${materialName}</strong>: Required ${required}, Available ${available}, Short by ${deficit}</li>`;
        });

        const message = fallbackMessage || "Insufficient material stock for this order.";

        orderPlacementFeedback.innerHTML = `
            <p class="order_modal_message_title">Unable to place order due to material shortage.</p>
            <p class="order_modal_message_copy">${escapeHtml(message)}</p>
            ${shortageListItems.length > 0 ? `<ul class="order_modal_shortage_list">${shortageListItems.join("")}</ul>` : ""}
        `;
        orderPlacementFeedback.hidden = false;
        orderPlacementFeedback.classList.add("active");
        orderPlacementFeedback.focus();

        return true;
    }

    function setButtonLoading(button, isLoading, loadingText) {
        if (!button) return;

        if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
        }

        if (isLoading) {
            button.innerHTML = `<span class="spinner"></span> ${loadingText}`;
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.originalHtml;
            button.disabled = false;
        }
    }

    function readSelectedOptions() {
        const selected = {};
        const selects = optionsContainer.querySelectorAll("select[data-option-id]");

        for (const select of selects) {
            if (!select.value) {
                return null;
            }
            selected[select.dataset.optionId] = Number(select.value);
        }

        return selected;
    }

    function renderTemplateControls() {
        if (!templatePayload?.template || !optionsContainer) return;

        modalTitle.textContent = `${product?.name ?? "Product"} Order`;

        optionsContainer.innerHTML = "";

        templatePayload.template.options.forEach((option) => {
            const wrapper = document.createElement("div");
            wrapper.className = "file_spec_field";

            const label = document.createElement("label");
            label.className = "file_spec_label";
            label.textContent = `${option.label} *`;

            const select = document.createElement("select");
            select.className = "order_modal_select";
            select.dataset.optionId = String(option.id);

            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = "Choose an option";
            select.appendChild(placeholder);

            option.option_types
                .filter((itemType) => itemType.is_available)
                .sort((a, b) => a.position - b.position)
                .forEach((itemType) => {
                    const optionNode = document.createElement("option");
                    optionNode.value = itemType.id;
                    optionNode.textContent = itemType.type_name;
                    select.appendChild(optionNode);
                });

            wrapper.appendChild(label);
            wrapper.appendChild(select);
            optionsContainer.appendChild(wrapper);
        });

        quantityInput.min = templatePayload.template.min_order || 1;
        quantityInput.value = templatePayload.template.min_order || 1;

        rushFeeSelect.innerHTML =
            '<option value="">Standard Processing (No Extra Fee)</option>';

        templatePayload.rush_fees.forEach((rushFee) => {
            const firstTimeframe = rushFee.timeframes?.[0];
            const label = firstTimeframe
                ? `${rushFee.label} (${firstTimeframe.label} +${firstTimeframe.percentage}%)`
                : rushFee.label;

            const optionNode = document.createElement("option");
            optionNode.value = rushFee.id;
            optionNode.textContent = label;
            rushFeeSelect.appendChild(optionNode);
        });
    }

    function renderCart() {
        if (!cartPayload || !cartContainer) return;

        cartContainer.innerHTML = "";

        if (!cartPayload.items || cartPayload.items.length === 0) {
            cartContainer.appendChild(emptyMessage);
            grandTotalDisplay.textContent = formatMoney(0);
            return;
        }

        cartPayload.items.forEach((item) => {
            const optionSummary =
                item.formatted_options
                    ?.map(
                        (option) =>
                            `${option.option_label}: ${option.selected_value}`,
                    )
                    .join(" | ") || "No option summary";

            const row = document.createElement("div");
            row.className = "file_spec_row";
            row.innerHTML = `
                <div class="file_spec_row_header">
                    <span class="file_spec_number">${item.product_name} x${item.quantity}</span>
                    <button type="button" class="remove_file_spec_btn" data-remove-id="${item.id}">✕</button>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;font-family:'Coolvetica',sans-serif;font-size:13px;flex-wrap:wrap;">
                    <span>${optionSummary}</span>
                    <span style="font-weight:bold;color:#682c7a;">${formatMoney(item.total_price)}</span>
                </div>
                ${item.special_instructions ? `<div style="margin-top:8px;font-family:'Coolvetica',sans-serif;font-size:12px;color:#666;">Notes: ${item.special_instructions}</div>` : ""}
            `;

            cartContainer.appendChild(row);
        });

        grandTotalDisplay.textContent = formatMoney(cartPayload.totals.total_price);
    }

    async function refreshCart() {
        const response = await CustomerOrderAPI.getCart();
        if (!response.success) {
            Toast.error(response.message || "Failed to load cart.");
            return;
        }

        cartPayload = response.data;
        renderCart();
    }

    async function ensureTemplateLoaded() {
        if (templatePayload) return;

        try {
            templatePayload = await CustomerOrderAPI.getOrderTemplate(productId);
            renderTemplateControls();
        } catch (error) {
            Toast.error("Unable to load product order configuration.");
        }
    }

    window.closeOrderModal = function () {
        orderModal.classList.remove("active");
        orderModal.style.display = "none";
        document.body.style.overflow = "auto";
        clearOrderPlacementFeedback();
    };

    window.openOrderModal = async function () {
        clearOrderPlacementFeedback();
        orderModal.classList.add("active");
        orderModal.style.display = "flex";
        document.body.style.overflow = "hidden";

        await ensureTemplateLoaded();
        await refreshCart();
    };

    closeBtn.addEventListener("click", window.closeOrderModal);

    cartContainer.addEventListener("click", async (event) => {
        const removeBtn = event.target.closest("button[data-remove-id]");
        if (!removeBtn) return;

        clearOrderPlacementFeedback();

        const removeId = Number(removeBtn.dataset.removeId);
        if (!removeId) return;

        const result = await CustomerOrderAPI.removeCartItem(removeId);
        if (!result.success) {
            Toast.error(result.message || "Unable to remove cart item.");
            return;
        }

        Toast.success("Item removed from cart.");
        cartPayload = result.data;
        renderCart();
    });

    addItemBtn.addEventListener("click", async () => {
        clearOrderPlacementFeedback();

        if (!templatePayload) {
            Toast.error("Order options are still loading. Please wait.");
            return;
        }

        const selectedOptions = readSelectedOptions();
        if (!selectedOptions) {
            Toast.warning("Please choose a value for each option.");
            return;
        }

        const quantity = Number(quantityInput.value || 0);
        if (quantity < Number(quantityInput.min || 1)) {
            Toast.warning(
                `Minimum quantity is ${quantityInput.min || 1} for this product.`,
            );
            return;
        }

        setButtonLoading(addItemBtn, true, "Adding...");

        const result = await CustomerOrderAPI.addCartItem({
            product_id: productId,
            order_template_id: templatePayload.template.id,
            selected_options: selectedOptions,
            quantity,
            rush_fee_id: rushFeeSelect.value ? Number(rushFeeSelect.value) : null,
            special_instructions: notesInput.value.trim() || null,
        });

        setButtonLoading(addItemBtn, false);

        if (!result.success) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;
            Toast.error(firstError || result.message || "Failed to add item.");
            return;
        }

        notesInput.value = "";
        quantityInput.value = quantityInput.min || 1;
        cartPayload = result.data;
        renderCart();
        Toast.success("Item added to persistent cart.");
    });

    submitBtn.addEventListener("click", async () => {
        clearOrderPlacementFeedback();

        const driveLink = driveLinkInput.value.trim();
        if (!driveLink) {
            Toast.warning("Please provide your main drive link before checkout.");
            return;
        }

        if (!cartPayload?.items || cartPayload.items.length === 0) {
            Toast.warning("Your cart is empty. Add at least one item.");
            return;
        }

        setButtonLoading(submitBtn, true, "Processing...");

        const result = await CustomerOrderAPI.checkoutCart(driveLink);

        setButtonLoading(submitBtn, false);

        if (!result.success) {
            if (Array.isArray(result.shortages) && result.shortages.length > 0) {
                renderShortageFeedback(result.shortages, result.message);
                Toast.error(
                    "Inventory shortage detected. Review the material details below.",
                );
                return;
            }

            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;
            Toast.error(firstError || result.message || "Checkout failed.");
            return;
        }

        clearOrderPlacementFeedback();
        Toast.success("Checkout complete! Redirecting to your orders...");
        window.closeOrderModal();
        window.location.href = "/account/orders";
    });
});
