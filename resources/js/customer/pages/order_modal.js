import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

document.addEventListener("DOMContentLoaded", () => {
    const MAIN_DRIVE_LINK_STORAGE_KEY = "customer_main_drive_link";
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
    let inventoryConstraintRequestToken = 0;

    try {
        product = JSON.parse(productData);
    } catch (error) {
        console.error("Failed to parse product payload for order modal", error);
    }

    const closeBtn = document.getElementById("closeOrderModal");
    const addItemBtn = document.getElementById("addItemBtn");
    const quantityInput = document.getElementById("itemQuantity");
    const notesInput = document.getElementById("itemFileName");
    const rushFeeSelect = document.getElementById("rushFeeSelect");
    const driveLinkInput = document.getElementById("generalDriveLink");
    const driveLinkError = document.getElementById("generalDriveLinkError");
    const optionsContainer = document.getElementById("dynamicOptionsContainer");
    const cartContainer = document.getElementById("cartItemsContainer");
    const emptyMessage = document.getElementById("emptyCartMsg");
    const grandTotalDisplay = document.getElementById("grandTotalDisplay");
    const grandTotalLabelText = document.getElementById("grandTotalLabelText");
    const modalTitle = document.getElementById("dynamicModalTitle");
    const quantityHintElement = document.getElementById("itemQuantityHint");

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

    const normalizeDriveLink = (value) =>
        typeof value === "string" ? value.trim() : "";

    const driveLinkIdPattern = /^[A-Za-z0-9_-]+$/;

    function isValidGoogleDriveUrl(url) {
        try {
            const parsedUrl = new URL(url);

            if (parsedUrl.protocol !== "https:") return false;
            if (parsedUrl.hostname !== "drive.google.com") return false;

            const normalizedPath = parsedUrl.pathname.replace(/\/+$/, "") || "/";

            if (
                normalizedPath === "/" ||
                normalizedPath === "/drive" ||
                normalizedPath.startsWith("/drive/")
            ) {
                return true;
            }

            if (/^\/drive\/folders\/[A-Za-z0-9_-]+$/.test(normalizedPath)) return true;
            if (/^\/file\/d\/[A-Za-z0-9_-]+(?:\/.*)?$/.test(normalizedPath)) return true;

            if (normalizedPath === "/open" || normalizedPath === "/uc") {
                const id = parsedUrl.searchParams.get("id");
                return Boolean(id && driveLinkIdPattern.test(id));
            }

            return false;
        } catch { return false; }
    }

    function clearDriveLinkError() {
        if (!driveLinkInput || !driveLinkError) return;
        driveLinkInput.classList.remove("input_error");
        driveLinkInput.removeAttribute("aria-invalid");
        driveLinkError.hidden = true;
        driveLinkError.textContent = "";
    }

    function setDriveLinkError(message) {
        if (!driveLinkInput || !driveLinkError) return;
        driveLinkInput.classList.add("input_error");
        driveLinkInput.setAttribute("aria-invalid", "true");
        driveLinkError.hidden = false;
        driveLinkError.textContent = message;
    }

    function validateDriveLinkInput({ required = true } = {}) {
        const normalizedLink = normalizeDriveLink(driveLinkInput?.value || "");

        if (driveLinkInput && driveLinkInput.value !== normalizedLink) {
            driveLinkInput.value = normalizedLink;
        }

        if (!normalizedLink) {
            if (!required) { clearDriveLinkError(); return { valid: true, value: "" }; }
            setDriveLinkError("Please provide your main Google Drive link before adding to cart.");
            return { valid: false, value: "" };
        }

        if (!isValidGoogleDriveUrl(normalizedLink)) {
            setDriveLinkError("Enter a valid Google Drive URL (drive.google.com) using an accepted Drive format.");
            return { valid: false, value: normalizedLink };
        }

        clearDriveLinkError();
        return { valid: true, value: normalizedLink };
    }

    const resolveGrandTotalLabel = (items = []) => {
        const currentProductLabel = `${product?.name ?? "Product"} Total`;
        if (!Array.isArray(items) || items.length === 0) return currentProductLabel;
        const hasMixedProducts = items.some((item) => Number(item.product_id) !== Number(productId));
        return hasMixedProducts ? "Cart Total" : currentProductLabel;
    };

    const updateGrandTotalLabel = (items = []) => {
        if (grandTotalLabelText) grandTotalLabelText.textContent = resolveGrandTotalLabel(items);
    };

    function setButtonLoading(button, isLoading, loadingText) {
        if (!button) return;
        if (!button.dataset.originalHtml) button.dataset.originalHtml = button.innerHTML;

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
            if (!select.value) return null;
            selected[select.dataset.optionId] = Number(select.value);
        }
        return selected;
    }

    function getSelectedOptionTypeIds() {
        const selectedOptions = readSelectedOptions();
        if (!selectedOptions) {
            return [];
        }

        return Object.values(selectedOptions)
            .map((value) => Number(value))
            .filter((value) => Number.isInteger(value) && value > 0);
    }

    function applyInventoryConstraints(inventory = null) {
        const maxOrderQuantity = Number(inventory?.max_order_quantity);
        const hasMaxOrderQuantity =
            Number.isInteger(maxOrderQuantity) && maxOrderQuantity >= 0;

        if (!hasMaxOrderQuantity) {
            quantityInput.removeAttribute("max");

            if (quantityHintElement) {
                quantityHintElement.textContent = "";
                quantityHintElement.hidden = true;
            }

            return;
        }

        quantityInput.max = String(maxOrderQuantity);

        if (quantityHintElement) {
            quantityHintElement.textContent =
                maxOrderQuantity === 0
                    ? "This option set is currently unavailable due to inventory buffer limits."
                    : `Maximum allowed quantity right now: ${maxOrderQuantity}.`;
            quantityHintElement.hidden = false;
        }

        const currentQuantity = Number(quantityInput.value || 0);
        if (maxOrderQuantity > 0 && currentQuantity > maxOrderQuantity) {
            quantityInput.value = String(maxOrderQuantity);
        }
    }

    async function refreshInventoryConstraintsFromSelection() {
        const selectedOptionTypeIds = getSelectedOptionTypeIds();

        if (!templatePayload || selectedOptionTypeIds.length === 0) {
            applyInventoryConstraints(templatePayload?.inventory ?? null);
            return;
        }

        const requestToken = ++inventoryConstraintRequestToken;

        try {
            const refreshedTemplatePayload = await CustomerOrderAPI.getOrderTemplate(
                productId,
                {
                    selectedOptionTypeIds,
                },
            );

            if (requestToken !== inventoryConstraintRequestToken) {
                return;
            }

            templatePayload = {
                ...templatePayload,
                inventory: refreshedTemplatePayload?.inventory ?? null,
            };

            applyInventoryConstraints(templatePayload.inventory);
        } catch (error) {
            if (requestToken !== inventoryConstraintRequestToken) {
                return;
            }

            console.error("Failed to refresh inventory constraints", error);
            applyInventoryConstraints(templatePayload?.inventory ?? null);
        }
    }

    // 🚀 DYNAMIC RUSH FEE UPDATER 🚀
    function updateRushFeeDropdown(currentTotal) {
        if (!rushFeeSelect || !templatePayload?.rush_fees) return;

        const prevValue = rushFeeSelect.value;
        rushFeeSelect.innerHTML = '<option value="">Standard Processing (No Extra Fee)</option>';

        let activeTier = null;

        // Find the matching tier based on the total price
        for (const rf of templatePayload.rush_fees) {
            const lbl = (rf.label || "").toLowerCase();
            if (currentTotal < 3000 && lbl.includes("below")) activeTier = rf;
            else if (currentTotal >= 3000 && currentTotal <= 4000 && lbl.includes("3000")) activeTier = rf;
            else if (currentTotal >= 4001 && currentTotal <= 5000 && lbl.includes("4001")) activeTier = rf;
            else if (currentTotal >= 5001 && lbl.includes("5001")) activeTier = rf;
        }

        // Fallback to the first tier if matching fails
        if (!activeTier && templatePayload.rush_fees.length > 0) {
            activeTier = templatePayload.rush_fees[0];
        }

        // Apply ALL timeframes inside the chosen tier
        if (activeTier && activeTier.timeframes) {
            activeTier.timeframes.forEach(tf => {
                const optionNode = document.createElement("option");
                optionNode.value = tf.id || activeTier.id;

                // Remove the long "(may depend on order queue)" text to make it cleaner
                let cleanLabel = tf.label.replace(/\(may depend on order queue\)/gi, "").trim();

                // Format back to exactly what you requested
                if (Number(tf.percentage) === 0) {
                    optionNode.textContent = `${cleanLabel} (NO ADDED TOTAL)`;
                } else {
                    optionNode.textContent = `${cleanLabel} (+${tf.percentage}%)`;
                }

                rushFeeSelect.appendChild(optionNode);
            });
        }

        // Keep previously selected value if it still exists in the new list
        if (prevValue && [...rushFeeSelect.options].some(o => o.value === prevValue)) {
            rushFeeSelect.value = prevValue;
        }
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
            const text = document.createTextNode(option.label + " ");
            const star = document.createElement("span");
            star.className = "required_star";
            star.textContent = "*";
            label.appendChild(text);
            label.appendChild(star);

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

            // Clear error styling when option is picked
            select.addEventListener("change", async () => {
                select.classList.remove("input_error");
                if (select.nextElementSibling && select.nextElementSibling.classList.contains("field_validation_error")) {
                    select.nextElementSibling.hidden = true;
                }

                await refreshInventoryConstraintsFromSelection();
            });
        });

        quantityInput.min = templatePayload.template.min_order || 1;
        quantityInput.value = templatePayload.template.min_order || 1;

        // Initialize dropdown based on current cart value
        updateRushFeeDropdown(cartPayload?.totals?.total_price || 0);
        applyInventoryConstraints(templatePayload?.inventory ?? null);
    }

    function renderCart() {
        if (!cartPayload || !cartContainer) return;
        cartContainer.innerHTML = "";

        if (!cartPayload.items || cartPayload.items.length === 0) {
            cartContainer.appendChild(emptyMessage);
            updateGrandTotalLabel([]);
            grandTotalDisplay.textContent = formatMoney(0);
            updateRushFeeDropdown(0);
            return;
        }

        cartPayload.items.forEach((item) => {
            const optionSummary = item.formatted_options
                ?.map((option) => `${option.option_label}: ${option.selected_value}`)
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
                ${item.special_instructions ? `<div style="margin-top:8px;font-family:'Coolvetica',sans-serif;font-size:12px;color:#666;">Filename: ${item.special_instructions}</div>` : ""}
            `;
            cartContainer.appendChild(row);
        });

        const currentTotal = cartPayload.totals.total_price;
        updateGrandTotalLabel(cartPayload.items);
        grandTotalDisplay.textContent = formatMoney(currentTotal);

        // Update the rush dropdown whenever cart total changes
        updateRushFeeDropdown(currentTotal);
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
            if (error?.payload?.error_code === "template_not_configured") {
                Toast.error("This product is not yet available for ordering.");
                return;
            }
            Toast.error("Unable to load product order configuration.");
        }
    }

    window.closeOrderModal = function () {
        orderModal.classList.remove("active");
        orderModal.style.display = "none";
        document.body.style.overflow = "auto";
    };

    window.openOrderModal = async function () {
        orderModal.classList.add("active");
        orderModal.style.display = "flex";
        document.body.style.overflow = "hidden";

        if (driveLinkInput) {
            driveLinkInput.value = localStorage.getItem(MAIN_DRIVE_LINK_STORAGE_KEY) || "";
            validateDriveLinkInput({ required: false });
        }

        await ensureTemplateLoaded();
        await refreshInventoryConstraintsFromSelection();
        await refreshCart();
    };

    closeBtn.addEventListener("click", window.closeOrderModal);

    driveLinkInput?.addEventListener("input", () => {
        const validation = validateDriveLinkInput({ required: false });
        if (validation.valid && validation.value) {
            localStorage.setItem(MAIN_DRIVE_LINK_STORAGE_KEY, validation.value);
        }
    });

    driveLinkInput?.addEventListener("blur", () => { validateDriveLinkInput({ required: true }); });

    notesInput.addEventListener("input", () => {
        notesInput.classList.remove("input_error");
        const fnErrorElement = document.getElementById("itemFileNameError");
        if (fnErrorElement) fnErrorElement.hidden = true;
    });

    quantityInput.addEventListener("input", () => {
        quantityInput.classList.remove("input_error");
        const qtyErrorElement = document.getElementById("itemQuantityError");
        if (qtyErrorElement) qtyErrorElement.hidden = true;

        const maxQty = Number(quantityInput.max);
        if (Number.isInteger(maxQty) && maxQty > 0) {
            const currentQty = Number(quantityInput.value || 0);
            if (currentQty > maxQty) {
                quantityInput.value = String(maxQty);
            }
        }
    });

    cartContainer.addEventListener("click", async (event) => {
        const removeBtn = event.target.closest("button[data-remove-id]");
        if (!removeBtn) return;

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
        let hasError = false;

        const driveLinkValidation = validateDriveLinkInput({ required: true });
        if (!driveLinkValidation.valid) {
            hasError = true;
        } else {
            localStorage.setItem(MAIN_DRIVE_LINK_STORAGE_KEY, driveLinkValidation.value);
        }

        const selectedOptions = {};
        const selects = optionsContainer.querySelectorAll("select[data-option-id]");
        selects.forEach((select) => {
            let errNode = select.nextElementSibling;
            if (!errNode || !errNode.classList.contains('field_validation_error')) {
                errNode = document.createElement("p");
                errNode.className = "field_validation_error";
                select.after(errNode);
            }

            if (!select.value) {
                select.classList.add("input_error");
                errNode.textContent = "Please choose an option.";
                errNode.hidden = false;
                hasError = true;
            } else {
                select.classList.remove("input_error");
                errNode.hidden = true;
                selectedOptions[select.dataset.optionId] = Number(select.value);
            }
        });

        if (!hasError) {
            await refreshInventoryConstraintsFromSelection();
        }

        const filenameVal = notesInput.value.trim();
        let fnErrorElement = document.getElementById("itemFileNameError");

        if (!fnErrorElement) {
            fnErrorElement = document.createElement("p");
            fnErrorElement.id = "itemFileNameError";
            fnErrorElement.className = "field_validation_error";
            notesInput.after(fnErrorElement);
        }

        if (!filenameVal) {
            notesInput.classList.add("input_error");
            fnErrorElement.textContent = "Design Filename is required.";
            fnErrorElement.hidden = false;
            hasError = true;
        } else {
            notesInput.classList.remove("input_error");
            fnErrorElement.hidden = true;
        }

        const quantity = Number(quantityInput.value || 0);
        const minQty = Number(quantityInput.min || 1);
        let qtyErrorElement = document.getElementById("itemQuantityError");

        if (!qtyErrorElement) {
            qtyErrorElement = document.createElement("p");
            qtyErrorElement.id = "itemQuantityError";
            qtyErrorElement.className = "field_validation_error";
            quantityInput.after(qtyErrorElement);
        }

        if (quantity < minQty) {
            quantityInput.classList.add("input_error");
            qtyErrorElement.textContent = `Minimum quantity is ${minQty}.`;
            qtyErrorElement.hidden = false;
            hasError = true;
        } else {
            quantityInput.classList.remove("input_error");
            qtyErrorElement.hidden = true;
        }

        const maxQty = Number(quantityInput.max);
        const hasMaxQty = Number.isInteger(maxQty) && maxQty >= 0;

        if (hasMaxQty && quantity > maxQty) {
            quantityInput.classList.add("input_error");
            qtyErrorElement.textContent =
                maxQty === 0
                    ? "This option set is currently unavailable due to inventory buffer limits."
                    : `Maximum quantity allowed right now is ${maxQty}.`;
            qtyErrorElement.hidden = false;
            hasError = true;
        }

        if (hasError) return;

        if (!templatePayload) {
            Toast.error("Order options are still loading. Please wait.");
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
            if (Array.isArray(result.shortages) && result.shortages.length > 0) {
                const maxOrderQuantity = Number.isInteger(result.max_order_quantity)
                    ? result.max_order_quantity
                    : null;

                if (maxOrderQuantity !== null) {
                    applyInventoryConstraints({
                        max_order_quantity: maxOrderQuantity,
                    });

                    quantityInput.classList.add("input_error");
                    qtyErrorElement.textContent =
                        maxOrderQuantity === 0
                            ? "This option set is currently unavailable due to inventory buffer limits."
                            : `Maximum quantity allowed right now is ${maxOrderQuantity}.`;
                    qtyErrorElement.hidden = false;
                }

                const firstShortage = result.shortages[0];
                const materialLabel = firstShortage?.material_name
                    ? ` (${firstShortage.material_name})`
                    : "";
                const quantityMessage =
                    maxOrderQuantity === null
                        ? "Requested quantity exceeds inventory buffer limits"
                        : maxOrderQuantity === 0
                            ? "No quantity is currently allowed for this option set"
                            : `Maximum allowed quantity is ${maxOrderQuantity}`;

                Toast.error(`${quantityMessage}${materialLabel}.`);
                return;
            }

            const firstError = result.errors ? Object.values(result.errors).flat()[0] : null;
            Toast.error(firstError || result.message || "Failed to add item.");
            return;
        }

        notesInput.value = "";
        quantityInput.value = quantityInput.min || 1;
        cartPayload = result.data;
        renderCart();
        Toast.success("Item added to cart.");
    });
});