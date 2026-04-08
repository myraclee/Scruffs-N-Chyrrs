import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

const cartContent = document.getElementById("cartContent");
const currentOrdersContent = document.getElementById("currentOrdersContent");
const completedOrdersContent = document.getElementById(
    "completedOrdersContent",
);
const currentOrdersCount = document.getElementById("currentOrdersCount");
const completedOrdersCount = document.getElementById("completedOrdersCount");

const orderEditModal = document.getElementById("orderEditModal");
const orderEditTitle = document.getElementById("orderEditTitle");
const orderEditSubtitle = document.getElementById("orderEditSubtitle");
const orderEditCloseBtn = document.getElementById("orderEditCloseBtn");
const orderEditCancelBtn = document.getElementById("orderEditCancelBtn");
const orderEditSaveBtn = document.getElementById("orderEditSaveBtn");
const orderEditDriveLink = document.getElementById("orderEditDriveLink");
const orderEditItems = document.getElementById("orderEditItems");

const PLACE_ORDER_BUTTON_ID = "cartPlaceOrderBtn";
const MAIN_DRIVE_LINK_STORAGE_KEY = "customer_main_drive_link";

const CURRENT_STATUSES = new Set(["waiting", "approved", "preparing", "ready"]);
const COMPLETED_STATUSES = new Set(["completed", "cancelled"]);

let isPlacingOrder = false;
let activeDetailsGroup = null;

const money = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));

const formatPhpAmount = (value) => {
    const normalized = Number(value || 0);
    return `Php ${new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(normalized)}`;
};

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

window.toggleOrdersCategory = function toggleOrdersCategory(category) {
    const content = document.getElementById(`${category}OrdersContent`);
    if (!content) return;

    const header = content.previousElementSibling;
    const isOpen = content.classList.contains("open");

    if (isOpen) {
        content.classList.remove("open");
        header.classList.remove("active");
    } else {
        content.classList.add("open");
        header.classList.add("active");
    }
};

document.addEventListener("DOMContentLoaded", async () => {
<<<<<<< Updated upstream
  openCurrentOrdersByDefault();
  bindOrderActionEvents();
  bindOrderDetailsModalEvents();
  bindCartEvents();
  await loadPageData();
=======
    openCurrentOrdersByDefault();
    bindOrderEditEvents();
    await loadPageData();
    bindCartEvents();
>>>>>>> Stashed changes
});

function openCurrentOrdersByDefault() {
    currentOrdersContent?.classList.add("open");
    const currentHeaderButton = document.querySelector(
        "[onclick=\"toggleOrdersCategory('current')\"]",
    );
    currentHeaderButton?.classList.add("active");
}

async function loadPageData() {
    const [cartResponse, ordersResponse] = await Promise.all([
        CustomerOrderAPI.getCart(),
        CustomerOrderAPI.getMyOrders({ per_page: 50 }),
    ]);

    if (!cartResponse.success) {
        Toast.error(cartResponse.message || "Could not load your cart.");
        renderEmptyCart();
    } else {
        renderCart(cartResponse.data);
    }

    if (!ordersResponse.success) {
        Toast.error(ordersResponse.message || "Could not load your orders.");
        renderOrderPlaceholders(
            currentOrdersContent,
            "Unable to load current orders right now.",
        );
        renderOrderPlaceholders(
            completedOrdersContent,
            "Unable to load completed orders right now.",
        );
        currentOrdersCount.textContent = "0";
        completedOrdersCount.textContent = "0";
        return;
    }

    const groups = ordersResponse.data || [];
    const current = groups.filter((group) =>
        CURRENT_STATUSES.has(group.status),
    );
    const completed = groups.filter((group) =>
        COMPLETED_STATUSES.has(group.status),
    );

    currentOrdersCount.textContent = String(current.length);
    completedOrdersCount.textContent = String(completed.length);

    renderOrderGroups(currentOrdersContent, current, "No current orders yet.");
    renderOrderGroups(
        completedOrdersContent,
        completed,
        "No completed orders yet.",
    );
}

function renderEmptyCart() {
<<<<<<< Updated upstream
  cartContent.innerHTML = `
    <div class="empty_state">
      <div class="empty_sparkles" aria-hidden="true">
        <span>✦</span><span>✧</span><span>✦</span>
      </div>
      <div class="empty_icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
      </div>
      <p class="empty_message">Empty as a blank page... ready for your magic touch!</p>
      <a href="/products" class="browse_products_btn">
        <span class="btn_sparkle">✦</span>
        <span>Browse Products</span>
      </a>
    </div>
  `;
=======
    cartContent.innerHTML = `
		<div class="empty_state">
			<div class="empty_sparkles" aria-hidden="true">
				<span>✦</span><span>✧</span><span>✦</span>
			</div>
			<div class="empty_icon">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<circle cx="9" cy="21" r="1"></circle>
					<circle cx="20" cy="21" r="1"></circle>
					<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
				</svg>
			</div>
			<p class="empty_message">Empty as a blank page... ready for your magic touch!</p>
			<a href="/products" class="browse_products_btn">
				<span class="btn_sparkle">✦</span>
				<span>Browse Products</span>
			</a>
		</div>
	`;
>>>>>>> Stashed changes
}

function renderCart(cart) {
    if (!cart?.items || cart.items.length === 0) {
        renderEmptyCart();
        return;
    }

    const rows = cart.items
        .map((item) => {
            const options = (item.formatted_options || [])
                .map((opt) => `${opt.option_label}: ${opt.selected_value}`)
                .join(" | ");

            return `
        <div class="cart_item_card" data-cart-item-id="${item.id}">
          <div class="cart_item_header">
            <span class="cart_item_title">${item.product_name} x${item.quantity}</span>
            <button
              type="button"
              class="remove_file_spec_btn"
              data-remove-cart-id="${item.id}"
              aria-label="Remove ${item.product_name} from cart"
            >
              Remove
            </button>
          </div>
          <div class="cart_item_details_row">
            <span class="cart_item_options">${options || "No option summary"}</span>
            <strong class="cart_item_price">${money(item.total_price)}</strong>
          </div>
          ${item.special_instructions ? `<div class="cart_item_notes">Notes: ${item.special_instructions}</div>` : ""}
        </div>
<<<<<<< Updated upstream
      `;
    })
    .join("");
=======
			`;
        })
        .join("");
>>>>>>> Stashed changes

    cartContent.innerHTML = `
    <div class="cart_content_shell">
      ${rows}
      <div class="cart_total_row">
        <span class="cart_total_label">Cart Total</span>
        <strong class="cart_total_value">${money(cart.totals?.total_price)}</strong>
      </div>
      <div class="cart_actions_row">
        <a href="/products" class="browse_products_btn">
          <span class="btn_sparkle">✦</span>
          <span>Add More Items</span>
        </a>
        <button type="button" class="browse_products_btn place_order_btn" id="${PLACE_ORDER_BUTTON_ID}">
          <span class="btn_sparkle">✦</span>
          <span>Place Order</span>
        </button>
      </div>
    </div>
  `;
}

function setPlaceOrderLoading(isLoading) {
    const placeOrderBtn = document.getElementById(PLACE_ORDER_BUTTON_ID);
    if (!placeOrderBtn) return;

    placeOrderBtn.disabled = isLoading;

    if (isLoading) {
        placeOrderBtn.dataset.originalHtml = placeOrderBtn.innerHTML;
        placeOrderBtn.innerHTML =
            '<span class="spinner" aria-hidden="true"></span><span>Processing...</span>';
        return;
    }

    if (placeOrderBtn.dataset.originalHtml) {
        placeOrderBtn.innerHTML = placeOrderBtn.dataset.originalHtml;
        delete placeOrderBtn.dataset.originalHtml;
    }
}

function getStoredDriveLinkValidation() {
    const savedDriveLink = normalizeDriveLink(
        localStorage.getItem(MAIN_DRIVE_LINK_STORAGE_KEY) || "",
    );

    if (!savedDriveLink) {
        return {
            valid: false,
            value: "",
            message:
                "Main Drive Link is required. Set it in Product Order before checkout.",
        };
    }

    if (!isValidGoogleDriveUrl(savedDriveLink)) {
        return {
            valid: false,
            value: savedDriveLink,
            message:
                "Saved Main Drive Link is invalid. Update it from Product Order before checkout.",
        };
    }

    return {
        valid: true,
        value: savedDriveLink,
        message: "",
    };
}

async function placeCartOrder() {
<<<<<<< Updated upstream
  if (isPlacingOrder) return false;
=======
    if (isPlacingOrder) return;
>>>>>>> Stashed changes

    const validation = getStoredDriveLinkValidation();
    if (!validation.valid) {
        Toast.warning(validation.message);
        return false;
    }

    isPlacingOrder = true;
    setPlaceOrderLoading(true);

    const result = await CustomerOrderAPI.checkoutCart(validation.value);

    isPlacingOrder = false;
    setPlaceOrderLoading(false);

    if (!result.success) {
        if (Array.isArray(result.shortages) && result.shortages.length > 0) {
            Toast.error(
                "Inventory shortage detected. Please adjust your cart and try again.",
            );
            return false;
        }

        if (
            Array.isArray(result.configuration_issues) &&
            result.configuration_issues.length > 0
        ) {
            Toast.error(
                "Inventory configuration issue detected. Please contact support or try again later.",
            );
            return false;
        }

        const firstError = result.errors
            ? Object.values(result.errors).flat()[0]
            : null;

        Toast.error(firstError || result.message || "Checkout failed.");
        return false;
    }

<<<<<<< Updated upstream
    const firstError = result.errors
      ? Object.values(result.errors).flat()[0]
      : null;

    Toast.error(firstError || result.message || "Checkout failed.");
    return false;
  }

  Toast.success("Order placed. Waiting for owner approval.");
  await loadPageData();
  return true;
=======
    Toast.success("Checkout complete! Your order is now waiting for approval.");
    await loadPageData();
    return true;
>>>>>>> Stashed changes
}

function renderOrderGroups(container, groups, emptyMessage) {
    if (!container) return;

    if (!groups || groups.length === 0) {
        renderOrderPlaceholders(container, emptyMessage);
        return;
    }

<<<<<<< Updated upstream
  container.innerHTML = groups
    .map((group) => {
      const lines = (group.orders || [])
        .map(
          (item) => `
=======
    container.innerHTML = groups
        .map((group) => {
            const canEdit = Boolean(
                group?.is_editable ?? group?.status === EDITABLE_STATUS,
            );

            // 1. Existing Edit Button (Acts as "View Details" / "Edit" for waiting orders)
            const editAction = canEdit
                ? `<button type="button" class="order_group_edit_btn" data-edit-order-group="${group.id}">Edit</button>`
                : `<button type="button" class="order_group_edit_btn" data-view-order="${group.id}">View Details</button>`;

            // 2. SA Flow Dynamic Buttons Logic
            let paymentAction = "";
            let cancelAction = "";

            // Assume 'Awaiting Payment' if backend hasn't supplied it yet
            const paymentStatus = group.payment_status || "Awaiting Payment";

            if (group.status === "waiting") {
                // SA FLOW 1: Waiting Approval -> Can Cancel
                cancelAction = `<button type="button" class="cancel_order_btn" data-cancel-order="${group.id}" style="background: #d94848; color: white; padding: 6px 15px; border-radius: 20px; border: none; cursor: pointer; font-family: 'Coolvetica', sans-serif;">Cancel Order</button>`;
            } else if (
                group.status === "approved" &&
                paymentStatus === "Awaiting Payment"
            ) {
                // SA FLOW 2: Approved but unpaid -> Can Pay, Can Cancel
                paymentAction = `<button type="button" class="pay_now_btn" data-pay-order="${group.id}" data-amount="${formatPhpAmount(group.totals?.total_price)}" style="background: #4caf50; color: white; padding: 6px 15px; border-radius: 20px; border: none; cursor: pointer; font-family: 'Coolvetica', sans-serif;">Pay Now</button>`;
                cancelAction = `<button type="button" class="cancel_order_btn" data-cancel-order="${group.id}" style="background: #d94848; color: white; padding: 6px 15px; border-radius: 20px; border: none; cursor: pointer; font-family: 'Coolvetica', sans-serif;">Cancel Order</button>`;
            }

            const lines = (group.orders || [])
                .map(
                    (item) => `
>>>>>>> Stashed changes
          <li class="order_group_item">
            <span class="order_group_item_label">${escapeHtml(item.product_name)} x${item.quantity}</span>
            <strong class="order_group_item_price">${money(item.total_price)}</strong>
          </li>
        `,
<<<<<<< Updated upstream
        )
        .join("");

      return `
        <article class="order_group_card" id="order-group-${group.id}">
          <div class="order_group_header">
            <span class="order_group_number">Order #${group.id}</span>
            <div class="order_group_header_actions">
              <span class="order_group_status_chip" title="Order: ${escapeHtml(group.status_label)}">Order: ${escapeHtml(group.status_label)}</span>
              <span class="order_group_status_chip order_group_payment_chip" title="Payment: ${escapeHtml(group.payment_status_label || "")}">Payment: ${escapeHtml(group.payment_status_label || "-")}</span>
              ${buildOrderActionButtons(group)}
=======
                )
                .join("");

            return `
        <article class="order_group_card">
          <div class="order_group_header">
            <span class="order_group_number">Order #${group.id}</span>
            <div class="order_group_header_actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
              
              <span class="order_group_status_chip" title="${group.status_label}">${group.status_label}</span>
              
              <span class="order_group_status_chip" style="background: #fffdf5; color: #d4af37; border: 1px solid #d4af37;">${paymentStatus}</span>
              
              ${editAction}
              ${paymentAction}
              ${cancelAction}

>>>>>>> Stashed changes
            </div>
          </div>
          <ul class="order_group_items">
            ${lines}
          </ul>
          <div class="order_group_footer">
            <span>Placed: ${new Date(group.created_at).toLocaleString()}</span>
            <strong>Total: ${money(group.totals?.total_price)}</strong>
          </div>
        </article>
      `;
<<<<<<< Updated upstream
    })
    .join("");
=======
        })
        .join("");
>>>>>>> Stashed changes
}

function buildOrderActionButtons(group) {
  const buttons = [];

  if (group?.can_pay_now) {
    buttons.push(`
      <button
        type="button"
        class="order_group_action_btn order_group_action_btn_pay"
        data-pay-order-group="${group.id}"
        data-payable-amount="${Number(group?.totals?.total_price || 0)}"
      >
        Pay Now
      </button>
    `);
  }

  buttons.push(`
    <button
      type="button"
      class="order_group_action_btn order_group_action_btn_view"
      data-view-order-group="${group.id}"
    >
      View Details
    </button>
  `);

  if (group?.can_cancel) {
    buttons.push(`
      <button
        type="button"
        class="order_group_action_btn order_group_action_btn_cancel"
        data-cancel-order-group="${group.id}"
      >
        Cancel Order
      </button>
    `);
  }

  return buttons.join("");
}

function renderOrderPlaceholders(container, message) {
    container.innerHTML = `<div class="orders_placeholder"><p>${message}</p></div>`;
}

<<<<<<< Updated upstream
function bindOrderActionEvents() {
  const handleActionClick = async (event) => {
    const payBtn = event.target.closest("button[data-pay-order-group]");
    if (payBtn) {
      const orderGroupId = Number(payBtn.dataset.payOrderGroup);
      const payableAmount = Number(payBtn.dataset.payableAmount || 0);
      await openPaymentModalForOrder(event, orderGroupId, payableAmount);
      return;
    }

    const viewBtn = event.target.closest("button[data-view-order-group]");
    if (viewBtn) {
      const orderGroupId = Number(viewBtn.dataset.viewOrderGroup);
      await openOrderDetailsModal(orderGroupId);
      return;
    }

    const cancelBtn = event.target.closest("button[data-cancel-order-group]");
    if (cancelBtn) {
      const orderGroupId = Number(cancelBtn.dataset.cancelOrderGroup);
      await cancelOrderGroup(orderGroupId);
    }
  };

  currentOrdersContent?.addEventListener("click", handleActionClick);
  completedOrdersContent?.addEventListener("click", handleActionClick);
}

async function openPaymentModalForOrder(event, orderGroupId, payableAmount) {
  if (!orderGroupId) {
    return;
  }

  if (typeof window.openPaymentModal !== "function") {
    Toast.error("Payment modal is unavailable right now.");
    return;
  }

  if (!Number.isFinite(payableAmount) || payableAmount <= 0) {
    Toast.error("Unable to resolve payable amount for this order.");
    return;
  }

  window.openPaymentModal(event, {
    payableAmount: formatPhpAmount(payableAmount),
    onConfirm: async (paymentData) =>
      await submitOrderPaymentProof(orderGroupId, paymentData),
  });
}

async function submitOrderPaymentProof(orderGroupId, paymentData) {
  if (!paymentData?.file) {
    Toast.error("Please upload your payment proof image.");
    return false;
  }

  const result = await CustomerOrderAPI.submitPaymentProof(orderGroupId, {
    payment_method: paymentData.paymentMethod,
    payment_reference_number: paymentData.referenceNo,
    payment_proof: paymentData.file,
  });

  if (!result.success) {
    const firstError = result.errors
      ? Object.values(result.errors).flat()[0]
      : null;

    Toast.error(
      firstError ||
      result.message ||
      "Unable to submit payment proof. Please try again.",
    );
    return false;
  }

  Toast.success("Payment proof submitted. Waiting for owner confirmation.");
  await loadPageData();
  return true;
}

async function cancelOrderGroup(orderGroupId) {
  if (!orderGroupId) {
    return;
  }

  const shouldCancel = window.confirm(
    "Cancel this order? This action cannot be undone.",
  );

  if (!shouldCancel) {
    return;
  }

  const result = await CustomerOrderAPI.cancelOrder(orderGroupId);

  if (!result.success) {
    Toast.error(result.message || "Unable to cancel order.");
    return;
  }

  Toast.success("Order cancelled.");
  await loadPageData();
}

function bindOrderDetailsModalEvents() {
  orderEditCloseBtn?.addEventListener("click", () => {
    closeOrderDetailsModal();
  });

  orderEditCancelBtn?.addEventListener("click", () => {
    closeOrderDetailsModal();
  });

  orderEditModal?.addEventListener("click", (event) => {
    if (event.target === orderEditModal) {
      closeOrderDetailsModal();
    }
  });
}

function setOrderDetailsModalVisible(isVisible) {
  if (!orderEditModal) return;
=======
function bindOrderEditEvents() {
    currentOrdersContent?.addEventListener("click", async (event) => {
        const editBtn = event.target.closest("button[data-edit-order-group]");
        if (!editBtn) return;

        const orderGroupId = Number(editBtn.dataset.editOrderGroup);
        if (!orderGroupId) return;

        await openOrderEditModal(orderGroupId);
    });

    orderEditCloseBtn?.addEventListener("click", () => {
        closeOrderEditModal();
    });

    orderEditCancelBtn?.addEventListener("click", () => {
        closeOrderEditModal();
    });

    orderEditModal?.addEventListener("click", (event) => {
        if (event.target === orderEditModal) {
            closeOrderEditModal();
        }
    });

    orderEditDriveLink?.addEventListener("input", () => {
        if (!activeEditDraft) return;

        activeEditDraft.general_drive_link = (
            orderEditDriveLink.value || ""
        ).trim();
        if (orderEditDriveLink.value !== activeEditDraft.general_drive_link) {
            orderEditDriveLink.value = activeEditDraft.general_drive_link;
        }
    });

    orderEditItems?.addEventListener("change", handleOrderEditItemMutation);
    orderEditItems?.addEventListener("input", handleOrderEditItemMutation);

    orderEditSaveBtn?.addEventListener("click", async () => {
        await saveOrderEdit();
    });
}

function setOrderEditModalVisible(isVisible) {
    if (!orderEditModal) return;
>>>>>>> Stashed changes

    orderEditModal.classList.toggle("open", isVisible);
    orderEditModal.setAttribute("aria-hidden", isVisible ? "false" : "true");
}

<<<<<<< Updated upstream
async function openOrderDetailsModal(orderGroupId) {
  if (!orderGroupId) {
    return;
  }

  const response = await CustomerOrderAPI.getOrderGroup(orderGroupId);

  if (!response.success) {
    Toast.error(response.message || "Unable to load order details.");
    return;
  }

  activeDetailsGroup = response.data;
  renderOrderDetailsModal(activeDetailsGroup);
  setOrderDetailsModalVisible(true);
}

function closeOrderDetailsModal() {
  setOrderDetailsModalVisible(false);
  activeDetailsGroup = null;

  if (orderEditTitle) {
    orderEditTitle.textContent = "Order Details";
  }

  if (orderEditSubtitle) {
    orderEditSubtitle.textContent = "Review your order items and payment status.";
  }

  if (orderEditDriveLink) {
    orderEditDriveLink.value = "";
    orderEditDriveLink.readOnly = false;
    orderEditDriveLink.disabled = false;
  }
=======
function closeOrderEditModal() {
    if (isSavingOrderEdit) return;

    setOrderEditModalVisible(false);
    activeEditGroupId = null;
    activeEditDraft = null;
>>>>>>> Stashed changes

    if (orderEditItems) {
        orderEditItems.innerHTML = "";
    }

<<<<<<< Updated upstream
  if (orderEditSaveBtn) {
    orderEditSaveBtn.style.display = "none";
  }

  if (orderEditCancelBtn) {
    orderEditCancelBtn.textContent = "Close";
  }
}

function renderOrderDetailsModal(group) {
  if (!group || !orderEditItems) {
    return;
  }

  if (orderEditTitle) {
    orderEditTitle.textContent = `Order #${group.id} Details`;
  }

  if (orderEditSubtitle) {
    orderEditSubtitle.textContent = `${group.status_label} | ${group.payment_status_label}`;
  }

  if (orderEditDriveLink) {
    const driveLink = normalizeDriveLink(group.general_drive_link || "");
    orderEditDriveLink.readOnly = true;
    orderEditDriveLink.disabled = true;
    orderEditDriveLink.value = driveLink || "No drive link submitted";
    orderEditDriveLink.title = driveLink || "No drive link submitted";
  }

  orderEditItems.innerHTML = (group.orders || [])
    .map((order, index) => {
      const options = (order.formatted_options || [])
        .map(
          (option) =>
            `<li>${escapeHtml(option.option_label)}: ${escapeHtml(option.selected_value)}</li>`,
        )
        .join("");

      return `
        <section class="order_view_item">
          <div class="order_view_item_header">
            <h4>Item ${index + 1}: ${escapeHtml(order.product_name || "Product")}</h4>
=======
    if (orderEditDriveLink) {
        orderEditDriveLink.value = "";
    }
}

async function openOrderEditModal(orderGroupId) {
    const response = await CustomerOrderAPI.getOrderGroup(orderGroupId);

    if (!response.success) {
        Toast.error(
            response.message || "Unable to load order details for editing.",
        );
        return;
    }

    const group = response.data;
    if (!group || group.status !== EDITABLE_STATUS) {
        Toast.error("Only orders waiting for approval can be edited.");
        await loadPageData();
        return;
    }

    activeEditGroupId = group.id;
    activeEditDraft = createOrderEditDraft(group);

    renderOrderEditDraft();
    setOrderEditModalVisible(true);
}

function createOrderEditDraft(group) {
    const rushFeeOptions = Array.isArray(group?.rush_fee_options)
        ? group.rush_fee_options
        : [];

    const orders = Array.isArray(group?.orders)
        ? group.orders.map((order) => {
              const optionSchema = Array.isArray(order?.option_schema)
                  ? order.option_schema
                  : [];

              return {
                  id: Number(order?.id || 0),
                  product_name: order?.product_name || "Unnamed Product",
                  quantity: Number(order?.quantity || 1),
                  min_order_quantity: Number(order?.min_order_quantity || 1),
                  rush_fee_id:
                      order?.rush_fee_id === null ||
                      order?.rush_fee_id === undefined
                          ? null
                          : Number(order.rush_fee_id),
                  special_instructions: order?.special_instructions || "",
                  options: optionSchema.map((option) => {
                      const types = Array.isArray(option?.types)
                          ? option.types
                          : [];
                      const fallbackTypeId =
                          types.length > 0 ? Number(types[0].id) : null;
                      const selectedTypeId =
                          option?.selected_type_id === null ||
                          option?.selected_type_id === undefined
                              ? fallbackTypeId
                              : Number(option.selected_type_id);

                      return {
                          id: Number(option?.id || 0),
                          label: option?.label || "Option",
                          selected_type_id: selectedTypeId,
                          types: types.map((type) => ({
                              id: Number(type?.id || 0),
                              type_name: type?.type_name || "",
                          })),
                      };
                  }),
              };
          })
        : [];

    return {
        general_drive_link: (group?.general_drive_link || "").trim(),
        rush_fee_options: rushFeeOptions,
        orders,
    };
}

function escapeHtml(value) {
    return String(value || "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

function renderOrderEditDraft() {
    if (!activeEditDraft || !orderEditItems || !orderEditDriveLink) {
        return;
    }

    orderEditDriveLink.value = activeEditDraft.general_drive_link || "";

    const rushFeeOptions = activeEditDraft.rush_fee_options || [];

    orderEditItems.innerHTML = activeEditDraft.orders
        .map((order, index) => {
            const optionFields = order.options
                .map((option) => {
                    const optionChoices = option.types
                        .map((type) => {
                            const selected =
                                Number(type.id) ===
                                Number(option.selected_type_id);
                            return `<option value="${type.id}" ${selected ? "selected" : ""}>${escapeHtml(type.type_name)}</option>`;
                        })
                        .join("");

                    return `
            <label class="order_edit_field">
              <span class="order_edit_field_label">${escapeHtml(option.label)}</span>
              <select class="order_edit_select" data-edit-order-id="${order.id}" data-edit-option-id="${option.id}">
                ${optionChoices}
              </select>
            </label>
          `;
                })
                .join("");

            const rushFeeChoices = [
                `<option value="">No rush fee</option>`,
                ...rushFeeOptions.map((rushFee) => {
                    const primaryTimeframe =
                        Array.isArray(rushFee?.timeframes) &&
                        rushFee.timeframes.length > 0
                            ? ` (${rushFee.timeframes[0].label})`
                            : "";
                    const selected =
                        Number(order.rush_fee_id) === Number(rushFee.id);

                    return `<option value="${rushFee.id}" ${selected ? "selected" : ""}>${escapeHtml(rushFee.label)}${escapeHtml(primaryTimeframe)}</option>`;
                }),
            ].join("");

            return `
        <section class="order_edit_item" data-edit-order-item-id="${order.id}">
          <div class="order_edit_item_header">
            <h4>Item ${index + 1}: ${escapeHtml(order.product_name)}</h4>
>>>>>>> Stashed changes
          </div>
          <div class="order_view_item_body">
            <p><strong>Quantity:</strong> ${Number(order.quantity || 0)}</p>
            <p><strong>Item Status:</strong> ${escapeHtml(order.status_label || order.status || "-")}</p>
            <p><strong>Special Instructions:</strong> ${escapeHtml(order.special_instructions || "None")}</p>
            <p><strong>Total:</strong> ${money(order.total_price)}</p>
            <div class="order_view_item_options">
              <strong>Options:</strong>
              ${options ? `<ul>${options}</ul>` : '<p>None</p>'}
            </div>
          </div>
        </section>
      `;
<<<<<<< Updated upstream
    })
    .join("");

  if (orderEditSaveBtn) {
    orderEditSaveBtn.style.display = "none";
  }

  if (orderEditCancelBtn) {
    orderEditCancelBtn.textContent = "Close";
  }
}

function bindCartEvents() {
  cartContent.addEventListener("click", async (event) => {
    const placeOrderBtn = event.target.closest(`#${PLACE_ORDER_BUTTON_ID}`);
    if (placeOrderBtn) {
      await placeCartOrder();
      return;
    }
=======
        })
        .join("");
}

function handleOrderEditItemMutation(event) {
    if (!activeEditDraft) return;

    const target = event.target;
    const orderId = Number(target.dataset.editOrderId);
    if (!orderId) return;

    const draftOrder = activeEditDraft.orders.find(
        (item) => item.id === orderId,
    );
    if (!draftOrder) return;

    if (target.dataset.editOptionId) {
        const optionId = Number(target.dataset.editOptionId);
        const draftOption = draftOrder.options.find(
            (option) => option.id === optionId,
        );
        if (draftOption) {
            draftOption.selected_type_id = Number(target.value || 0);
        }
        return;
    }

    if (target.dataset.editQty) {
        const minValue = Number(draftOrder.min_order_quantity || 1);
        const parsed = Number(target.value);
        draftOrder.quantity =
            Number.isFinite(parsed) && parsed >= minValue ? parsed : minValue;
        if (Number(target.value) !== draftOrder.quantity) {
            target.value = String(draftOrder.quantity);
        }
        return;
    }

    if (target.dataset.editRush) {
        draftOrder.rush_fee_id = target.value ? Number(target.value) : null;
        return;
    }

    if (target.dataset.editNotes) {
        draftOrder.special_instructions = target.value || "";
    }
}

function buildOrderEditPayload() {
    if (!activeEditDraft) {
        return null;
    }

    const orders = activeEditDraft.orders.map((order) => {
        if (!Array.isArray(order.options) || order.options.length === 0) {
            return null;
        }

        const selectedOptions = {};

        for (const option of order.options) {
            if (!option.selected_type_id || option.selected_type_id <= 0) {
                return null;
            }

            selectedOptions[String(option.id)] = Number(
                option.selected_type_id,
            );
        }

        const minOrder = Number(order.min_order_quantity || 1);
        const quantity = Number(order.quantity || minOrder);

        if (!Number.isFinite(quantity) || quantity < minOrder) {
            return null;
        }

        return {
            id: Number(order.id),
            selected_options: selectedOptions,
            quantity,
            rush_fee_id: order.rush_fee_id ? Number(order.rush_fee_id) : null,
            special_instructions:
                (order.special_instructions || "").trim() || null,
        };
    });

    if (orders.some((entry) => entry === null)) {
        return null;
    }

    return {
        general_drive_link:
            (activeEditDraft.general_drive_link || "").trim() || null,
        orders,
    };
}

async function saveOrderEdit() {
    if (isSavingOrderEdit || !activeEditGroupId) {
        return;
    }

    const payload = buildOrderEditPayload();
    if (!payload) {
        Toast.error(
            "Please complete all required edit fields with valid values.",
        );
        return;
    }

    isSavingOrderEdit = true;
    if (orderEditSaveBtn) {
        orderEditSaveBtn.disabled = true;
        orderEditSaveBtn.textContent = "Saving...";
    }

    const response = await CustomerOrderAPI.updateOrderDetails(
        activeEditGroupId,
        payload,
    );

    isSavingOrderEdit = false;
    if (orderEditSaveBtn) {
        orderEditSaveBtn.disabled = false;
        orderEditSaveBtn.textContent = "Save Changes";
    }

    if (!response.success) {
        if (response.error_code === "customer_order_not_editable") {
            Toast.error(
                response.message || "This order is no longer editable.",
            );
            closeOrderEditModal();
            await loadPageData();
            return;
        }

        if (
            Array.isArray(response.errors?.general_drive_link) &&
            response.errors.general_drive_link.length > 0
        ) {
            Toast.error(response.errors.general_drive_link[0]);
            return;
        }

        if (
            Array.isArray(response.shortages) &&
            response.shortages.length > 0
        ) {
            Toast.error(
                "Unable to apply changes because inventory stock is insufficient.",
            );
            return;
        }

        Toast.error(response.message || "Unable to update order details.");
        return;
    }

    Toast.success("Order details updated successfully.");
    closeOrderEditModal();
    await loadPageData();
}

function bindCartEvents() {
    cartContent.addEventListener("click", async (event) => {
        const placeOrderBtn = event.target.closest(`#${PLACE_ORDER_BUTTON_ID}`);
        if (placeOrderBtn) {
            const cartState = await CustomerOrderAPI.getCart();
            if (
                !cartState.success ||
                !Array.isArray(cartState.data?.items) ||
                cartState.data.items.length === 0
            ) {
                Toast.warning("Your cart is empty. Add at least one item.");
                return;
            }

            const payableTotal = Number(
                cartState.data?.totals?.total_price ?? NaN,
            );
            if (!Number.isFinite(payableTotal) || payableTotal <= 0) {
                Toast.error(
                    "Unable to resolve the payable amount. Please refresh your cart and try again.",
                );
                return;
            }
            await placeCartOrder();

            return;
        }

        const removeBtn = event.target.closest("button[data-remove-cart-id]");
        if (!removeBtn) return;

        const cartItemId = Number(removeBtn.dataset.removeCartId);
        if (!cartItemId) return;
>>>>>>> Stashed changes

        const result = await CustomerOrderAPI.removeCartItem(cartItemId);
        if (!result.success) {
            Toast.error(result.message || "Unable to remove cart item.");
            return;
        }

        Toast.success("Cart item removed.");
        renderCart(result.data);
    });
}

<<<<<<< Updated upstream
function escapeHtml(value) {
  return String(value || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}
=======
// --- GLOBAL EVENT LISTENER FOR DYNAMIC BUTTONS ---
document.addEventListener("click", async (event) => {
    // 1. Handle Cancel Order Click
    const cancelBtn = event.target.closest(".cancel_order_btn");
    if (cancelBtn) {
        const orderId = cancelBtn.dataset.cancelOrder;

        if (
            confirm(
                "Are you sure you want to cancel this order? This cannot be undone.",
            )
        ) {
            try {
                // Hitting the backend route we made in Step 4
                const response = await fetch(
                    `/customer/orders/${orderId}/cancel`,
                    {
                        method: "POST", // or PATCH depending on your route
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector(
                                'meta[name="csrf-token"]',
                            ).content,
                        },
                    },
                );
                const data = await response.json();

                if (data.success) {
                    Toast.success(data.message || "Order cancelled.");
                    await loadPageData(); // Refresh the list!
                } else {
                    Toast.error(data.message || "Could not cancel order.");
                }
            } catch (error) {
                Toast.error("An error occurred while cancelling the order.");
            }
        }
    }

    // 2. Handle Pay Now Click
    const payBtn = event.target.closest(".pay_now_btn");
    if (payBtn) {
        const orderId = payBtn.dataset.payOrder;
        const amount = payBtn.dataset.amount;

        if (typeof window.openPaymentModal === "function") {
            window.openPaymentModal(event, {
                payableAmount: amount,
                onConfirm: async (paymentData) => {
                    const formData = new FormData();
                    formData.append("payment_proof", paymentData.file);

                    // Safely grab the CSRF token without crashing if it's missing
                    const csrfTag = document.querySelector(
                        'meta[name="csrf-token"]',
                    );
                    const csrfToken = csrfTag ? csrfTag.content : "";

                    try {
                        const response = await fetch(
                            `/customer/orders/${orderId}/pay`,
                            {
                                method: "POST",
                                headers: {
                                    Accept: "application/json", // 👉 Forces Laravel to return real errors!
                                    "X-CSRF-TOKEN": csrfToken,
                                },
                                body: formData,
                            },
                        );

                        // Wait to parse JSON until we check if the server crashed
                        if (!response.ok) {
                            const errorData = await response
                                .json()
                                .catch(() => null);
                            console.error("Server Error:", errorData);

                            // If Laravel blocked it (like a validation error or 419 token error)
                            if (
                                response.status === 422 &&
                                errorData &&
                                errorData.errors
                            ) {
                                const firstError = Object.values(
                                    errorData.errors,
                                )[0][0];
                                throw new Error(firstError);
                            } else if (response.status === 419) {
                                throw new Error(
                                    "Security token expired. Please refresh the page.",
                                );
                            } else {
                                throw new Error(
                                    errorData?.message ||
                                        `Server error: ${response.status}`,
                                );
                            }
                        }

                        const data = await response.json();

                        if (data.success) {
                            Toast.success(
                                "Payment submitted! Waiting for admin confirmation.",
                            );
                            await loadPageData();
                        } else {
                            throw new Error(
                                data.message || "Failed to submit payment.",
                            );
                        }
                    } catch (error) {
                        console.error("Upload failed:", error);
                        // 👉 This will now show us the ACTUAL error on your screen!
                        Toast.error(
                            error.message ||
                                "An error occurred while uploading the payment.",
                        );
                    }
                },
            });
        }
    }
});
>>>>>>> Stashed changes
