import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

const cartContent = document.getElementById("cartContent");
const currentOrdersContent = document.getElementById("currentOrdersContent");
const completedOrdersContent = document.getElementById("completedOrdersContent");
const currentOrdersCount = document.getElementById("currentOrdersCount");
const completedOrdersCount = document.getElementById("completedOrdersCount");
const orderEditModal = document.getElementById("orderEditModal");
const orderEditCloseBtn = document.getElementById("orderEditCloseBtn");
const orderEditCancelBtn = document.getElementById("orderEditCancelBtn");
const orderEditSaveBtn = document.getElementById("orderEditSaveBtn");
const orderEditDriveLink = document.getElementById("orderEditDriveLink");
const orderEditItems = document.getElementById("orderEditItems");
const DRIVE_LINK_FIELD_ID = "cartGeneralDriveLink";
const PLACE_ORDER_BUTTON_ID = "cartPlaceOrderBtn";

const CURRENT_STATUSES = new Set(["waiting", "approved", "preparing", "ready"]);
const COMPLETED_STATUSES = new Set(["completed", "cancelled"]);
const EDITABLE_STATUS = "waiting";

let activeEditGroupId = null;
let activeEditDraft = null;
let isSavingOrderEdit = false;
let isPlacingOrder = false;

const money = (value) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(Number(value || 0));

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
  bindOrderEditEvents();
  await loadPageData();
  bindCartEvents();
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
				<div class="file_spec_row" data-cart-item-id="${item.id}" style="width:100%;">
					<div class="file_spec_row_header">
						<span class="file_spec_number">${item.product_name} x${item.quantity}</span>
						<button type="button" class="remove_file_spec_btn" data-remove-cart-id="${item.id}">✕</button>
					</div>
					<div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;flex-wrap:wrap;font-family:'Coolvetica',sans-serif;font-size:13px;">
						<span>${options || "No option summary"}</span>
						<strong style="color:#682c7a;">${money(item.total_price)}</strong>
					</div>
					${item.special_instructions ? `<div style="margin-top:8px;font-family:'Coolvetica',sans-serif;font-size:12px;color:#666;">Notes: ${item.special_instructions}</div>` : ""}
				</div>
			`;
    })
    .join("");

  cartContent.innerHTML = `
		<div style="width:100%;display:flex;flex-direction:column;gap:14px;">
			${rows}
			<div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:2px dashed rgba(104,44,122,0.2);font-family:'Coolvetica',sans-serif;color:#682c7a;">
				<span style="font-size:18px;">Cart Total</span>
				<strong style="font-size:20px;">${money(cart.totals?.total_price)}</strong>
			</div>
			<div class="cart_checkout_block">
				<label for="${DRIVE_LINK_FIELD_ID}" class="cart_checkout_label">Main Drive Link <span class="label_required">*</span></label>
				<input
					type="url"
					id="${DRIVE_LINK_FIELD_ID}"
					class="cart_checkout_input"
					placeholder="https://drive.google.com/drive/folders/..."
					required
				>
				<p class="cart_checkout_hint">Accepted Drive Formats: /drive/folders/{id}, /file/d/{id}, /open?id={id}, /uc?id={id}</p>
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

function validateCartDriveLinkInput() {
  const driveLinkInput = document.getElementById(DRIVE_LINK_FIELD_ID);
  if (!driveLinkInput) {
    return {
      valid: false,
      value: "",
      message: "Drive link field is not available.",
    };
  }

  const normalizedLink = normalizeDriveLink(driveLinkInput.value);
  if (driveLinkInput.value !== normalizedLink) {
    driveLinkInput.value = normalizedLink;
  }

  if (!normalizedLink) {
    return {
      valid: false,
      value: "",
      message: "Please provide your main Google Drive link before placing the order.",
    };
  }

  if (!isValidGoogleDriveUrl(normalizedLink)) {
    return {
      valid: false,
      value: normalizedLink,
      message: "Enter a valid Google Drive URL (drive.google.com) using an accepted Drive format.",
    };
  }

  return {
    valid: true,
    value: normalizedLink,
    message: "",
  };
}

async function placeCartOrder() {
  if (isPlacingOrder) return;

  const validation = validateCartDriveLinkInput();
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

  Toast.success("Checkout complete! Your order is now waiting for approval.");
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
      const canEdit = Boolean(group?.is_editable ?? group?.status === EDITABLE_STATUS);
      const editAction = canEdit
        ? `<button type="button" class="order_group_edit_btn" data-edit-order-group="${group.id}">Edit</button>`
        : "";

      const lines = (group.orders || [])
        .map(
          (item) => `
          <li class="order_group_item">
            <span class="order_group_item_label">${item.product_name} x${item.quantity}</span>
            <strong class="order_group_item_price">${money(item.total_price)}</strong>
					</li>
				`,
        )
        .join("");

      return `
        <article class="order_group_card">
          <div class="order_group_header">
            <span class="order_group_number">Order #${group.id}</span>
					<div class="order_group_header_actions">
              <span class="order_group_status_chip" title="${group.status_label}">${group.status_label}</span>
						${editAction}
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

function renderOrderPlaceholders(container, message) {
  container.innerHTML = `<div class="orders_placeholder"><p>${message}</p></div>`;
}

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

    activeEditDraft.general_drive_link = (orderEditDriveLink.value || "").trim();
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

  orderEditModal.classList.toggle("open", isVisible);
  orderEditModal.setAttribute("aria-hidden", isVisible ? "false" : "true");
}

function closeOrderEditModal() {
  if (isSavingOrderEdit) return;

  setOrderEditModalVisible(false);
  activeEditGroupId = null;
  activeEditDraft = null;

  if (orderEditItems) {
    orderEditItems.innerHTML = "";
  }

  if (orderEditDriveLink) {
    orderEditDriveLink.value = "";
  }
}

async function openOrderEditModal(orderGroupId) {
  const response = await CustomerOrderAPI.getOrderGroup(orderGroupId);

  if (!response.success) {
    Toast.error(response.message || "Unable to load order details for editing.");
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
          order?.rush_fee_id === null || order?.rush_fee_id === undefined
            ? null
            : Number(order.rush_fee_id),
        special_instructions: order?.special_instructions || "",
        options: optionSchema.map((option) => {
          const types = Array.isArray(option?.types) ? option.types : [];
          const fallbackTypeId = types.length > 0 ? Number(types[0].id) : null;
          const selectedTypeId =
            option?.selected_type_id === null || option?.selected_type_id === undefined
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
              const selected = Number(type.id) === Number(option.selected_type_id);
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
          const primaryTimeframe = Array.isArray(rushFee?.timeframes) && rushFee.timeframes.length > 0
            ? ` (${rushFee.timeframes[0].label})`
            : "";
          const selected = Number(order.rush_fee_id) === Number(rushFee.id);

          return `<option value="${rushFee.id}" ${selected ? "selected" : ""}>${escapeHtml(rushFee.label)}${escapeHtml(primaryTimeframe)}</option>`;
        }),
      ].join("");

      return `
        <section class="order_edit_item" data-edit-order-item-id="${order.id}">
          <div class="order_edit_item_header">
            <h4>Item ${index + 1}: ${escapeHtml(order.product_name)}</h4>
          </div>

          <div class="order_edit_option_grid">
            ${optionFields}
          </div>

          <div class="order_edit_field_grid">
            <label class="order_edit_field">
              <span class="order_edit_field_label">Quantity</span>
              <input
                type="number"
                min="${order.min_order_quantity}"
                step="1"
                class="order_edit_input"
                data-edit-order-id="${order.id}"
                data-edit-qty="true"
                value="${order.quantity}"
              >
              <span class="order_edit_hint">Minimum: ${order.min_order_quantity}</span>
            </label>

            <label class="order_edit_field">
              <span class="order_edit_field_label">Rush Fee</span>
              <select class="order_edit_select" data-edit-order-id="${order.id}" data-edit-rush="true">
                ${rushFeeChoices}
              </select>
            </label>
          </div>

          <label class="order_edit_field order_edit_field_full">
            <span class="order_edit_field_label">Special Instructions / File Names</span>
            <textarea class="order_edit_textarea" data-edit-order-id="${order.id}" data-edit-notes="true">${escapeHtml(order.special_instructions)}</textarea>
          </label>
        </section>
      `;
    })
    .join("");
}

function handleOrderEditItemMutation(event) {
  if (!activeEditDraft) return;

  const target = event.target;
  const orderId = Number(target.dataset.editOrderId);
  if (!orderId) return;

  const draftOrder = activeEditDraft.orders.find((item) => item.id === orderId);
  if (!draftOrder) return;

  if (target.dataset.editOptionId) {
    const optionId = Number(target.dataset.editOptionId);
    const draftOption = draftOrder.options.find((option) => option.id === optionId);
    if (draftOption) {
      draftOption.selected_type_id = Number(target.value || 0);
    }
    return;
  }

  if (target.dataset.editQty) {
    const minValue = Number(draftOrder.min_order_quantity || 1);
    const parsed = Number(target.value);
    draftOrder.quantity = Number.isFinite(parsed) && parsed >= minValue ? parsed : minValue;
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

      selectedOptions[String(option.id)] = Number(option.selected_type_id);
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
      special_instructions: (order.special_instructions || "").trim() || null,
    };
  });

  if (orders.some((entry) => entry === null)) {
    return null;
  }

  return {
    general_drive_link: (activeEditDraft.general_drive_link || "").trim() || null,
    orders,
  };
}

async function saveOrderEdit() {
  if (isSavingOrderEdit || !activeEditGroupId) {
    return;
  }

  const payload = buildOrderEditPayload();
  if (!payload) {
    Toast.error("Please complete all required edit fields with valid values.");
    return;
  }

  isSavingOrderEdit = true;
  if (orderEditSaveBtn) {
    orderEditSaveBtn.disabled = true;
    orderEditSaveBtn.textContent = "Saving...";
  }

  const response = await CustomerOrderAPI.updateOrderDetails(activeEditGroupId, payload);

  isSavingOrderEdit = false;
  if (orderEditSaveBtn) {
    orderEditSaveBtn.disabled = false;
    orderEditSaveBtn.textContent = "Save Changes";
  }

  if (!response.success) {
    if (response.error_code === "customer_order_not_editable") {
      Toast.error(response.message || "This order is no longer editable.");
      closeOrderEditModal();
      await loadPageData();
      return;
    }

    if (Array.isArray(response.errors?.general_drive_link) && response.errors.general_drive_link.length > 0) {
      Toast.error(response.errors.general_drive_link[0]);
      return;
    }

    if (Array.isArray(response.shortages) && response.shortages.length > 0) {
      Toast.error("Unable to apply changes because inventory stock is insufficient.");
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
      if (!cartState.success || !Array.isArray(cartState.data?.items) || cartState.data.items.length === 0) {
        Toast.warning("Your cart is empty. Add at least one item.");
        return;
      }

      if (typeof window.openPaymentModal === "function") {
        window.openPaymentModal(event, {
          onConfirm: async () => {
            return await placeCartOrder();
          },
        });
      } else {
        await placeCartOrder();
      }

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
