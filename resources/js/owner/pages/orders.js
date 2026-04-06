import OwnerOrderAPI from "/resources/js/api/ownerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

const listContainer = document.getElementById("ownerOrdersList");
const searchInput = document.getElementById("ownerOrdersSearch");
const filterButtons = document.querySelectorAll(".filter_btn");

const detailsModal = document.getElementById("orderDetailsModal");
const loadingModal = document.getElementById("ownerOrderLoadingModal");
const closeDetailsModalBtn = document.getElementById("closeDetailsModalBtn");
const detailStatusSelect = document.getElementById("detailStatusSelect");
const detailEditBtn = document.getElementById("detailEditBtn");
const detailSaveBtn = document.getElementById("detailSaveBtn");
const detailCancelBtn = document.getElementById("detailCancelBtn");
const detailDriveLinkInput = document.getElementById("detailDriveLinkInput");
const detailsModalBox = detailsModal?.querySelector(".details_modal_box");
const cancelConfirmModal = document.getElementById("cancelConfirmModal");
const abortCancelBtn = document.getElementById("abortCancelBtn");
const confirmCancelBtn = document.getElementById("confirmCancelBtn");

const detailOrderDate = document.getElementById("detailOrderDate");
const detailOrderId = document.getElementById("detailOrderId");
const detailCustomerName = document.getElementById("detailCustomerName");
const detailCustomerContact = document.getElementById("detailCustomerContact");
const detailCustomerEmail = document.getElementById("detailCustomerEmail");
const detailDriveLink = document.getElementById("detailDriveLink");
const detailDriveLinkHint = document.getElementById("detailDriveLinkHint");
const detailItemsBody = document.getElementById("detailItemsBody");
const detailOrderTotal = document.getElementById("detailOrderTotal");

let activeFilter = "all";
let activeSearch = "";
let currentDetailOrderId = null;
let currentDetailGroup = null;
let detailDraft = null;
let isDetailsEditMode = false;
let isSavingDetails = false;

const formatMoney = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));

const statusClass = (status) => {
    if (status === "waiting") return "status-yellow";
    if (status === "approved" || status === "completed") return "status-green";
    if (status === "preparing") return "status-blue";
    if (status === "ready") return "status-orange";
    if (status === "cancelled") return "status-red";
    return "status-yellow";
};

const statusLabel = {
    waiting: "Waiting for Order Approval",
    approved: "Order Approved",
    preparing: "Preparing Order",
    ready: "Ready for Shipping",
    completed: "Order Completed",
    cancelled: "Order Cancelled",
};

const NON_EDITABLE_STATUSES = ["completed", "cancelled"];

const normalizeDriveLink = (value) =>
    typeof value === "string" ? value.trim() : "";

const driveLinkIdPattern = /^[A-Za-z0-9_-]+$/;

function isValidGoogleDriveUrl(url) {
    try {
        const parsedUrl = new URL(url);

        if (parsedUrl.protocol !== "https:") {
            return false;
        }

        if (parsedUrl.hostname !== "drive.google.com") {
            return false;
        }

        const normalizedPath = parsedUrl.pathname.replace(/\/+$/, "") || "/";

        if (
            normalizedPath === "/" ||
            normalizedPath === "/drive" ||
            normalizedPath.startsWith("/drive/")
        ) {
            return true;
        }

        if (/^\/drive\/folders\/[A-Za-z0-9_-]+$/.test(normalizedPath)) {
            return true;
        }

        if (/^\/file\/d\/[A-Za-z0-9_-]+(?:\/.*)?$/.test(normalizedPath)) {
            return true;
        }

        if (normalizedPath === "/open" || normalizedPath === "/uc") {
            const id = parsedUrl.searchParams.get("id");
            return Boolean(id && driveLinkIdPattern.test(id));
        }

        return false;
    } catch {
        return false;
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    bindEvents();
    await loadOrders();
});

function bindEvents() {
    filterButtons.forEach((button) => {
        button.addEventListener("click", async () => {
            filterButtons.forEach((item) => item.classList.remove("active"));
            button.classList.add("active");
            activeFilter = button.dataset.filter;
            await loadOrders();
        });
    });

    let debounceTimer = null;
    searchInput?.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            activeSearch = searchInput.value.trim();
            await loadOrders();
        }, 300);
    });

    listContainer.addEventListener("click", async (event) => {
        const detailsBtn = event.target.closest("button[data-view-order]");
        if (!detailsBtn) return;

        const orderId = Number(detailsBtn.dataset.viewOrder);
        if (!orderId) return;

        await openDetails(orderId);
    });

    listContainer.addEventListener("change", async (event) => {
        const statusSelect = event.target.closest("select[data-status-order]");
        if (!statusSelect) return;

        const orderId = Number(statusSelect.dataset.statusOrder);
        if (!orderId) return;

        const nextStatus = statusSelect.value;
        const previousStatus = statusSelect.dataset.currentStatus;

        // --- ADDED: CANCEL MODAL INTERCEPT ---
        if (nextStatus === "cancelled") {
            cancelConfirmModal.style.display = "flex";

            abortCancelBtn.onclick = () => {
                cancelConfirmModal.style.display = "none";
                statusSelect.value = previousStatus; // Revert visually
            };

            confirmCancelBtn.onclick = async () => {
                cancelConfirmModal.style.display = "none";
                const success = await updateStatus(orderId, nextStatus);
                if (!success) {
                    statusSelect.value = previousStatus;
                    applyStatusClass(statusSelect, previousStatus);
                } else {
                    statusSelect.dataset.currentStatus = nextStatus;
                    applyStatusClass(statusSelect, nextStatus);
                }
            };
            return; // Stop here!
        }
        // -------------------------------------

        const success = await updateStatus(orderId, nextStatus);
        if (!success) {
            statusSelect.value = previousStatus;
            applyStatusClass(statusSelect, previousStatus);
        } else {
            statusSelect.dataset.currentStatus = nextStatus;
            applyStatusClass(statusSelect, nextStatus);
        }
    });

    detailStatusSelect?.addEventListener("change", async () => {
        if (!currentDetailOrderId) return;

        if (isDetailsEditMode) {
            detailStatusSelect.value = detailStatusSelect.dataset.currentStatus;
            applyStatusClass(
                detailStatusSelect,
                detailStatusSelect.dataset.currentStatus,
            );
            Toast.error(
                "Save or cancel your line-item edits before changing order status.",
            );
            return;
        }

        const previousStatus = detailStatusSelect.dataset.currentStatus;
        const nextStatus = detailStatusSelect.value;

        // --- ADDED: CANCEL MODAL INTERCEPT ---
        if (nextStatus === "cancelled") {
            cancelConfirmModal.style.display = "flex";

            abortCancelBtn.onclick = () => {
                cancelConfirmModal.style.display = "none";
                detailStatusSelect.value = previousStatus; // Revert visually
            };

            confirmCancelBtn.onclick = async () => {
                cancelConfirmModal.style.display = "none";
                const success = await updateStatus(
                    currentDetailOrderId,
                    nextStatus,
                );
                if (!success) {
                    detailStatusSelect.value = previousStatus;
                    applyStatusClass(detailStatusSelect, previousStatus);
                    return;
                }
                await openDetails(currentDetailOrderId);
            };
            return; // Stop here so it doesn't instantly save!
        }
        // -------------------------------------

        const success = await updateStatus(currentDetailOrderId, nextStatus);

        if (!success) {
            detailStatusSelect.value = previousStatus;
            applyStatusClass(detailStatusSelect, previousStatus);
            return;
        }

        await openDetails(currentDetailOrderId);
    });

    closeDetailsModalBtn?.addEventListener("click", () => {
        closeDetailsModal();
    });

    detailEditBtn?.addEventListener("click", () => {
        if (
            !currentDetailGroup ||
            !isGroupEditable(currentDetailGroup.status)
        ) {
            Toast.error("Completed or cancelled orders cannot be edited.");
            return;
        }

        isDetailsEditMode = true;
        detailDraft = createDetailDraft(currentDetailGroup);
        renderDetails();
    });

    detailCancelBtn?.addEventListener("click", () => {
        if (!currentDetailGroup) return;

        isDetailsEditMode = false;
        detailDraft = createDetailDraft(currentDetailGroup);
        renderDetails();
    });

    detailSaveBtn?.addEventListener("click", async () => {
        await saveDetailsEdits();
    });

    detailDriveLinkInput?.addEventListener("input", () => {
        if (!isDetailsEditMode || !detailDraft) {
            return;
        }

        const normalizedDriveLink = normalizeDriveLink(
            detailDriveLinkInput.value,
        );

        if (detailDriveLinkInput.value !== normalizedDriveLink) {
            detailDriveLinkInput.value = normalizedDriveLink;
        }

        detailDraft.general_drive_link = normalizedDriveLink;
    });

    detailItemsBody?.addEventListener("input", handleDetailInputChange);
    detailItemsBody?.addEventListener("change", handleDetailInputChange);
}

async function loadOrders() {
    listContainer.innerHTML = `
        <div class="empty_state_container" style="display: block;">
            <p class="empty_message">Loading orders...</p>
        </div>
    `;

    const response = await OwnerOrderAPI.getOrders({
        status: activeFilter,
        search: activeSearch,
        per_page: 50,
    });

    if (!response.success) {
        listContainer.innerHTML = `
            <div class="empty_state_container" style="display: block;">
                <p class="empty_message">${response.message || "Unable to load orders."}</p>
            </div>
        `;
        return;
    }

    const groups = response.data || [];

    if (groups.length === 0) {
        listContainer.innerHTML = `
            <div class="empty_state_container" style="display: block;">
                <p class="empty_message">No orders found for this status.</p>
            </div>
        `;
        return;
    }

    listContainer.innerHTML = groups
        .map((group) => {
            const itemSummary = (group.orders || [])
                .map((item) => `${item.product_name} x${item.quantity}`)
                .join(", ");

            return `
                <div class="order_card" data-status="${group.status}">
                    <div class="card_top">
                        <div class="customer_info">
                            <h3>${group.user?.name || "Unknown Customer"}</h3>
                            <p>${group.user?.email || "No email"}</p>
                        </div>
                        <button class="view_details_btn" data-view-order="${group.id}">View Details</button>
                    </div>

                    <div class="card_middle">
                        <div class="status_group">
                            <span class="status_label">Order Status:</span>
                            <select class="status_select ${statusClass(group.status)}" data-status-order="${group.id}" data-current-status="${group.status}">
                                ${Object.entries(statusLabel)
                    .map(
                        ([value, label]) =>
                            `<option value="${value}" ${group.status === value ? "selected" : ""}>${label}</option>`,
                    )
                    .join("")}
                            </select>
                        </div>

                        <div class="status_group">
                            <span class="status_label">Payment Status:</span>
                            <div class="payment_pill status-yellow">Deferred in this phase</div>
                        </div>
                    </div>

                    <hr class="card_divider">

                    <div class="card_bottom">
                        <div class="detail_group">
                            <span class="detail_label">Order ID:</span>
                            <span class="detail_value">#${group.id}</span>
                        </div>
                        <div class="detail_group">
                            <span class="detail_label">Order Items:</span>
                            <span class="detail_value">${group.items_count}</span>
                        </div>
                        <div class="detail_group">
                            <span class="detail_label">Order Price:</span>
                            <span class="detail_value">${formatMoney(group.totals?.total_price)}</span>
                        </div>
                        <div class="detail_group">
                            <span class="detail_label">Order Date:</span>
                            <span class="detail_value">${new Date(group.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="detail_group" style="grid-column: 1 / -1;">
                            <span class="detail_label">Summary:</span>
                            <span class="detail_value">${itemSummary || "No line items"}</span>
                        </div>
                    </div>
                </div>
            `;
        })
        .join("");
}

async function updateStatus(orderId, nextStatus) {
    showLoadingModal(true);

    const result = await OwnerOrderAPI.updateOrderStatus(orderId, nextStatus);

    showLoadingModal(false);

    if (!result.success) {
        Toast.error(result.message || "Status update failed.");
        return false;
    }

    Toast.success("Order status updated.");
    await loadOrders();
    return true;
}

async function openDetails(orderId) {
    const response = await OwnerOrderAPI.getOrder(orderId);
    if (!response.success) {
        Toast.error(response.message || "Unable to load order details.");
        return;
    }

    const group = response.data;
    currentDetailOrderId = group.id;
    currentDetailGroup = group;
    isDetailsEditMode = false;
    detailDraft = createDetailDraft(group);

    renderDetails();

    detailsModal.style.display = "flex";
}

function renderDetails() {
    if (!currentDetailGroup) {
        return;
    }

    detailOrderDate.textContent = formatDateTime(currentDetailGroup.created_at);
    detailOrderId.textContent = `#${currentDetailGroup.id}`;
    detailCustomerName.textContent = currentDetailGroup.user?.name || "Unknown";
    detailCustomerContact.textContent =
        currentDetailGroup.user?.contact_number || "Not provided";
    detailCustomerEmail.textContent =
        currentDetailGroup.user?.email || "Not provided";

    renderDriveLink();
    renderDetailRows();

    detailOrderTotal.textContent = formatMoney(
        currentDetailGroup.totals?.total_price,
    );
    detailStatusSelect.value = currentDetailGroup.status;
    detailStatusSelect.dataset.currentStatus = currentDetailGroup.status;
    detailStatusSelect.disabled = isDetailsEditMode;
    applyStatusClass(detailStatusSelect, currentDetailGroup.status);

    syncDetailActionButtons();
}

// --- ADDED: STATUS LOCK LOGIC ---
// Look for the payment text (defaults to 'awaiting' if it can't find it)
const paymentElement = document.getElementById("detailPaymentStatus");
const paymentStatusText = paymentElement
    ? paymentElement.innerText.toLowerCase()
    : "awaiting";
const isUnpaid =
    paymentStatusText.includes("awaiting") ||
    paymentStatusText.includes("deferred") ||
    paymentStatusText.includes("pending");

Array.from(detailStatusSelect.options).forEach((option) => {
    // If UNPAID: Only allow waiting, cancelled, or whatever it is currently stuck on
    if (isUnpaid) {
        if (
            option.value !== "waiting" &&
            option.value !== "cancelled" &&
            option.value !== currentDetailGroup.status
        ) {
            option.disabled = true;
            if (!option.text.includes("🔒")) option.text = option.text + " 🔒";
        } else {
            option.disabled = false;
            option.text = option.text.replace(" 🔒", "");
        }
    } else {
        // If PAID: Unlock everything
        option.disabled = false;
        option.text = option.text.replace(" 🔒", "");
    }
});
// --------------------------------

function renderDriveLink() {
    if (!isDetailsEditMode) {
        const driveLink = (currentDetailGroup?.general_drive_link || "").trim();
        detailDriveLink.style.display = "inline";
        detailDriveLinkInput.style.display = "none";
        if (detailDriveLinkHint) {
            detailDriveLinkHint.style.display = "none";
        }

        if (driveLink) {
            detailDriveLink.textContent = driveLink;
            detailDriveLink.href = driveLink;
            detailDriveLink.target = "_blank";
            detailDriveLink.rel = "noopener noreferrer";
            return;
        }

        detailDriveLink.textContent = "No drive link submitted";
        detailDriveLink.href = "#";
        detailDriveLink.removeAttribute("target");
        detailDriveLink.removeAttribute("rel");
        return;
    }

    detailDriveLink.style.display = "none";
    detailDriveLinkInput.style.display = "block";
    if (detailDriveLinkHint) {
        detailDriveLinkHint.style.display = "block";
    }
    detailDriveLinkInput.value = detailDraft?.general_drive_link || "";
}

function renderDetailRows() {
    const rowsSource = isDetailsEditMode
        ? detailDraft?.orders || []
        : currentDetailGroup?.orders || [];

    if (rowsSource.length === 0) {
        detailItemsBody.innerHTML = `
      <tr>
        <td colspan="6">No line items.</td>
      </tr>
    `;
        return;
    }

    detailItemsBody.innerHTML = rowsSource
        .map((item, index) => {
            if (!isDetailsEditMode) {
                const optionsSummary = (item.formatted_options || [])
                    .map(
                        (opt) =>
                            `${escapeHtml(opt.option_label || "Option")}: ${escapeHtml(opt.selected_value || "-")}`,
                    )
                    .join(" | ");

                const rushSummary = item.rush_fee_label
                    ? `<br><small>Rush: ${escapeHtml(item.rush_fee_label)}</small>`
                    : "";

                return `
          <tr>
            <td>${index + 1}</td>
            <td>${escapeHtml(item.product_name || "Product")}<br><small>${optionsSummary || "No options"}</small>${rushSummary}</td>
            <td>${escapeHtml(item.special_instructions || "No file note")}</td>
            <td>${Number(item.quantity || 0)}</td>
            <td>${formatMoney(item.discount_amount)}</td>
            <td>${formatMoney(item.total_price)}</td>
          </tr>
        `;
            }

            return `
        <tr>
          <td>${index + 1}</td>
          <td>
            <div class="detail_item_edit_stack">
              <div class="detail_item_title">${escapeHtml(item.product_name || "Product")}</div>
              ${renderOptionEditors(item)}
              ${renderRushEditor(item)}
            </div>
          </td>
          <td>
            <textarea class="detail_note_input" data-edit-note-order-id="${item.id}" placeholder="e.g. front.png,back.png">${escapeHtml(item.special_instructions || "")}</textarea>
          </td>
          <td>
            <input
              class="detail_qty_input"
              type="number"
              data-edit-quantity-order-id="${item.id}"
              min="${Number(item.min_order_quantity || 1)}"
              step="1"
              value="${Number(item.quantity || 1)}"
            >
          </td>
          <td>${formatMoney(item.discount_amount)}</td>
          <td>
            ${formatMoney(item.total_price)}
            <div class="detail_readonly_hint">Recalculated on save</div>
          </td>
        </tr>
      `;
        })
        .join("");
}

function renderOptionEditors(orderDraft) {
    const optionSchema = Array.isArray(orderDraft.option_schema)
        ? orderDraft.option_schema
        : [];

    if (optionSchema.length === 0) {
        return `<small>No option schema available.</small>`;
    }

    return `
    <div class="detail_option_fields">
      ${optionSchema
            .map((option) => {
                const optionId = Number(option.id);
                const selectedOptionTypeId = Number(
                    orderDraft.selected_options?.[String(optionId)] ??
                    orderDraft.selected_options?.[optionId] ??
                    option.selected_type_id ??
                    "",
                );

                const typeOptions = (option.types || [])
                    .map((type) => {
                        const typeId = Number(type.id);
                        const isSelected = selectedOptionTypeId === typeId;

                        return `<option value="${typeId}" ${isSelected ? "selected" : ""}>${escapeHtml(type.type_name || "Option")}</option>`;
                    })
                    .join("");

                return `
        <label class="detail_option_field">
          <span class="detail_option_field_label">${escapeHtml(option.label || "Option")}</span>
          <select class="detail_option_select" data-edit-order-id="${orderDraft.id}" data-edit-option-id="${optionId}">
            ${typeOptions}
          </select>
        </label>
      `;
            })
            .join("")}
    </div>
  `;
}

function renderRushEditor(orderDraft) {
    const rushOptions = detailDraft?.rush_fee_options || [];
    const selectedRushId =
        orderDraft.rush_fee_id === null || orderDraft.rush_fee_id === undefined
            ? ""
            : String(orderDraft.rush_fee_id);

    return `
    <label class="detail_option_field">
      <span class="detail_option_field_label">Rush Processing</span>
      <select class="detail_rush_select" data-edit-rush-order-id="${orderDraft.id}">
        <option value="" ${selectedRushId === "" ? "selected" : ""}>No rush processing</option>
        ${rushOptions
            .map((rushFee) => {
                const rushId = String(rushFee.id);
                const firstTimeframe = Array.isArray(rushFee.timeframes)
                    ? rushFee.timeframes[0]
                    : null;
                const timeframeLabel = firstTimeframe
                    ? `${firstTimeframe.label} ${Number(firstTimeframe.percentage)}%`
                    : "No timeframe";

                return `<option value="${rushId}" ${selectedRushId === rushId ? "selected" : ""}>${escapeHtml(rushFee.label)} (${escapeHtml(timeframeLabel)})</option>`;
            })
            .join("")}
      </select>
    </label>
  `;
}

function createDetailDraft(group) {
    const orders = Array.isArray(group?.orders) ? group.orders : [];

    return {
        id: Number(group?.id || 0),
        general_drive_link: (group?.general_drive_link || "").trim(),
        rush_fee_options: Array.isArray(group?.rush_fee_options)
            ? group.rush_fee_options
            : [],
        orders: orders.map((order) => {
            const normalizedSelectedOptions = normalizeSelectedOptions(
                order?.selected_options,
                order?.option_schema,
            );

            return {
                id: Number(order.id),
                product_name: order.product_name || "Product",
                option_schema: Array.isArray(order.option_schema)
                    ? order.option_schema
                    : [],
                selected_options: normalizedSelectedOptions,
                quantity: Number(order.quantity || 1),
                min_order_quantity: Number(order.min_order_quantity || 1),
                rush_fee_id:
                    order.rush_fee_id === null ||
                        order.rush_fee_id === undefined
                        ? null
                        : Number(order.rush_fee_id),
                special_instructions: order.special_instructions || "",
                discount_amount: Number(order.discount_amount || 0),
                total_price: Number(order.total_price || 0),
            };
        }),
    };
}

function normalizeSelectedOptions(selectedOptions, optionSchema) {
    const normalized = {};
    const source =
        selectedOptions && typeof selectedOptions === "object"
            ? selectedOptions
            : {};

    const schema = Array.isArray(optionSchema) ? optionSchema : [];
    schema.forEach((option) => {
        const optionId = String(option.id);
        const value =
            source[optionId] ??
            source[Number(option.id)] ??
            option.selected_type_id;

        if (value !== null && value !== undefined && value !== "") {
            normalized[optionId] = Number(value);
        }
    });

    return normalized;
}

function handleDetailInputChange(event) {
    if (!isDetailsEditMode || !detailDraft) {
        return;
    }

    const optionSelect = event.target.closest(
        "select[data-edit-order-id][data-edit-option-id]",
    );
    if (optionSelect) {
        const orderId = Number(optionSelect.dataset.editOrderId);
        const optionId = String(optionSelect.dataset.editOptionId);
        const orderDraft = getDraftOrder(orderId);
        if (!orderDraft) {
            return;
        }

        orderDraft.selected_options[optionId] = Number(optionSelect.value);
        return;
    }

    const rushSelect = event.target.closest("select[data-edit-rush-order-id]");
    if (rushSelect) {
        const orderId = Number(rushSelect.dataset.editRushOrderId);
        const orderDraft = getDraftOrder(orderId);
        if (!orderDraft) {
            return;
        }

        orderDraft.rush_fee_id = rushSelect.value
            ? Number(rushSelect.value)
            : null;
        return;
    }

    const noteInput = event.target.closest("textarea[data-edit-note-order-id]");
    if (noteInput) {
        const orderId = Number(noteInput.dataset.editNoteOrderId);
        const orderDraft = getDraftOrder(orderId);
        if (!orderDraft) {
            return;
        }

        orderDraft.special_instructions = noteInput.value;
        return;
    }

    const quantityInput = event.target.closest(
        "input[data-edit-quantity-order-id]",
    );
    if (quantityInput) {
        const orderId = Number(quantityInput.dataset.editQuantityOrderId);
        const orderDraft = getDraftOrder(orderId);
        if (!orderDraft) {
            return;
        }

        const minQuantity = Number(orderDraft.min_order_quantity || 1);
        const rawQuantity = Number(quantityInput.value || minQuantity);
        const normalizedQuantity = Number.isFinite(rawQuantity)
            ? Math.max(minQuantity, Math.floor(rawQuantity))
            : minQuantity;

        orderDraft.quantity = normalizedQuantity;
        quantityInput.value = String(normalizedQuantity);
    }
}

async function saveDetailsEdits() {
    if (!currentDetailOrderId || !detailDraft || isSavingDetails) {
        return;
    }

    const validationError = validateDetailDraft(detailDraft);
    if (validationError) {
        Toast.error(validationError);
        return;
    }

    isSavingDetails = true;
    syncDetailActionButtons();
    showLoadingModal(true);

    const payload = {
        general_drive_link: detailDraft.general_drive_link || null,
        orders: detailDraft.orders.map((order) => ({
            id: Number(order.id),
            selected_options: order.selected_options,
            quantity: Number(order.quantity),
            rush_fee_id: order.rush_fee_id,
            special_instructions:
                (order.special_instructions || "").trim() || null,
        })),
    };

    const result = await OwnerOrderAPI.updateOrderDetails(
        currentDetailOrderId,
        payload,
    );

    showLoadingModal(false);
    isSavingDetails = false;

    if (!result.success) {
        if (Array.isArray(result.shortages) && result.shortages.length > 0) {
            const firstShortage = result.shortages[0];
            Toast.error(
                `${firstShortage.material_name}: required ${firstShortage.required}, available ${firstShortage.available}.`,
            );
        } else {
            Toast.error(
                result.message || "Unable to save order detail changes.",
            );
        }

        syncDetailActionButtons();
        return;
    }

    Toast.success("Order details updated.");

    currentDetailGroup = result.data;
    isDetailsEditMode = false;
    detailDraft = createDetailDraft(currentDetailGroup);
    renderDetails();

    await loadOrders();
}

function validateDetailDraft(draft) {
    if (!draft || !Array.isArray(draft.orders) || draft.orders.length === 0) {
        return "At least one order line is required.";
    }

    const normalizedDriveLink = normalizeDriveLink(draft.general_drive_link);
    draft.general_drive_link = normalizedDriveLink;

    if (normalizedDriveLink && !isValidGoogleDriveUrl(normalizedDriveLink)) {
        return "Main Drive Link must be a valid Google Drive URL.";
    }

    for (const order of draft.orders) {
        const minQuantity = Number(order.min_order_quantity || 1);
        if (Number(order.quantity) < minQuantity) {
            return `Quantity for ${order.product_name} must be at least ${minQuantity}.`;
        }

        const optionSchema = Array.isArray(order.option_schema)
            ? order.option_schema
            : [];
        for (const option of optionSchema) {
            const selectedValue =
                order.selected_options?.[String(option.id)] ??
                order.selected_options?.[Number(option.id)];

            if (!selectedValue) {
                return `Select a value for ${option.label} in ${order.product_name}.`;
            }
        }
    }

    return null;
}

function getDraftOrder(orderId) {
    if (!detailDraft || !Array.isArray(detailDraft.orders)) {
        return null;
    }

    return (
        detailDraft.orders.find(
            (order) => Number(order.id) === Number(orderId),
        ) || null
    );
}

function syncDetailActionButtons() {
    const canEdit =
        currentDetailGroup && isGroupEditable(currentDetailGroup.status);

    detailEditBtn.style.display =
        canEdit && !isDetailsEditMode ? "inline-flex" : "none";
    detailSaveBtn.style.display =
        canEdit && isDetailsEditMode ? "inline-flex" : "none";
    detailCancelBtn.style.display =
        canEdit && isDetailsEditMode ? "inline-flex" : "none";

    detailSaveBtn.disabled = isSavingDetails;
    detailCancelBtn.disabled = isSavingDetails;
    closeDetailsModalBtn.disabled = isSavingDetails;

    if (detailsModalBox) {
        detailsModalBox.classList.toggle("edit_mode", isDetailsEditMode);
    }
}

function closeDetailsModal() {
    detailsModal.style.display = "none";
    currentDetailOrderId = null;
    currentDetailGroup = null;
    detailDraft = null;
    isDetailsEditMode = false;
    isSavingDetails = false;
}

function formatDateTime(value) {
    if (!value) {
        return "-";
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return "-";
    }

    return parsed.toLocaleString();
}

function isGroupEditable(status) {
    return !NON_EDITABLE_STATUSES.includes(String(status || ""));
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function applyStatusClass(selectElement, status) {
    if (!selectElement) return;

    selectElement.classList.remove(
        "status-yellow",
        "status-green",
        "status-blue",
        "status-orange",
        "status-red",
    );

    selectElement.classList.add(statusClass(status));
    selectElement.dataset.currentStatus = status;
}

function showLoadingModal(isVisible) {
    if (!loadingModal) return;
    loadingModal.style.display = isVisible ? "flex" : "none";
}
