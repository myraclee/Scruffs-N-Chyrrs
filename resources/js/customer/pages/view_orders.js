import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

const cartContent = document.getElementById("cartContent");
const currentOrdersContent = document.getElementById("currentOrdersContent");
const completedOrdersContent = document.getElementById("completedOrdersContent");
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
  openCurrentOrdersByDefault();
  bindOrderActionEvents();
  bindOrderDetailsModalEvents();
  bindCartEvents();
  await loadPageData();
});

function openCurrentOrdersByDefault() {
  currentOrdersContent?.classList.add("open");
  const currentHeaderButton = document.querySelector(
    '[onclick="toggleOrdersCategory(\'current\')"]',
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
  const current = groups.filter((group) => CURRENT_STATUSES.has(group.status));
  const completed = groups.filter((group) => COMPLETED_STATUSES.has(group.status));

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
      `;
    })
    .join("");

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
    placeOrderBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Processing...</span>';
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
      message: "Main Drive Link is required. Set it in Product Order before checkout.",
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
  if (isPlacingOrder) return false;

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
      Toast.error("Inventory shortage detected. Please adjust your cart and try again.");
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

  Toast.success("Order placed. Waiting for owner approval.");
  await loadPageData();
  return true;
}

function renderOrderGroups(container, groups, emptyMessage) {
  if (!container) return;

  if (!groups || groups.length === 0) {
    renderOrderPlaceholders(container, emptyMessage);
    return;
  }

  container.innerHTML = groups
    .map((group) => {
      const lines = (group.orders || [])
        .map(
          (item) => `
          <li class="order_group_item">
            <span class="order_group_item_label">${escapeHtml(item.product_name)} x${item.quantity}</span>
            <strong class="order_group_item_price">${money(item.total_price)}</strong>
          </li>
        `,
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
    })
    .join("");
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

        const cancelBtn = event.target.closest(
            "button[data-cancel-order-group]",
        );
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

  orderEditModal.classList.toggle("open", isVisible);
  orderEditModal.setAttribute("aria-hidden", isVisible ? "false" : "true");
}
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
        orderEditSubtitle.textContent =
            "Review your order items and payment status.";
    }

  if (orderEditDriveLink) {
    orderEditDriveLink.value = "";
    orderEditDriveLink.readOnly = false;
    orderEditDriveLink.disabled = false;
  }

  if (orderEditItems) {
    orderEditItems.innerHTML = "";
  }

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
          </div>
          <div class="order_view_item_body">
            <p><strong>Quantity:</strong> ${Number(order.quantity || 0)}</p>
            <p><strong>Item Status:</strong> ${escapeHtml(order.status_label || order.status || "-")}</p>
            <p><strong>Special Instructions:</strong> ${escapeHtml(order.special_instructions || "None")}</p>
            <p><strong>Total:</strong> ${money(order.total_price)}</p>
            <div class="order_view_item_options">
              <strong>Options:</strong>
              ${options ? `<ul>${options}</ul>` : "<p>None</p>"}
            </div>
          </div>
        </section>
      `;
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

    const removeBtn = event.target.closest("button[data-remove-cart-id]");
    if (!removeBtn) return;

    const cartItemId = Number(removeBtn.dataset.removeCartId);
    if (!cartItemId) return;

    const result = await CustomerOrderAPI.removeCartItem(cartItemId);
    if (!result.success) {
      Toast.error(result.message || "Unable to remove cart item.");
      return;
    }

    Toast.success("Cart item removed.");
    renderCart(result.data);
  });
}
function escapeHtml(value) {
    return String(value || "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}
