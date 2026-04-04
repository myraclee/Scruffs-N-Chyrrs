import CustomerOrderAPI from "/resources/js/api/customerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

const cartContent = document.getElementById("cartContent");
const currentOrdersContent = document.getElementById("currentOrdersContent");
const completedOrdersContent = document.getElementById("completedOrdersContent");
const currentOrdersCount = document.getElementById("currentOrdersCount");
const completedOrdersCount = document.getElementById("completedOrdersCount");

const CURRENT_STATUSES = new Set(["waiting", "approved", "preparing", "ready"]);
const COMPLETED_STATUSES = new Set(["completed", "cancelled"]);

const money = (value) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(Number(value || 0));

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
			<a href="/products" class="browse_products_btn" style="align-self:flex-start;">
				<span class="btn_sparkle">✦</span>
				<span>Add More Items</span>
			</a>
		</div>
	`;
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
					<li style="display:flex;justify-content:space-between;gap:8px;">
						<span>${item.product_name} x${item.quantity}</span>
						<strong>${money(item.total_price)}</strong>
					</li>
				`,
        )
        .join("");

      return `
				<article class="file_spec_row" style="margin:18px 22px;">
					<div class="file_spec_row_header">
						<span class="file_spec_number">Order #${group.id}</span>
						<span style="font-family:'Coolvetica',sans-serif;font-size:12px;background:#f9f0ff;border:1px solid #682c7a;border-radius:999px;padding:5px 10px;color:#682c7a;">${group.status_label}</span>
					</div>
					<ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px;font-family:'Coolvetica',sans-serif;font-size:13px;color:#4a4a4a;">
						${lines}
					</ul>
					<div style="margin-top:10px;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;font-family:'Coolvetica',sans-serif;font-size:12px;color:#666;">
						<span>Placed: ${new Date(group.created_at).toLocaleString()}</span>
						<strong style="color:#682c7a;">Total: ${money(group.totals?.total_price)}</strong>
					</div>
				</article>
			`;
    })
    .join("");
}

function renderOrderPlaceholders(container, message) {
  container.innerHTML = `<div class="orders_placeholder"><p>${message}</p></div>`;
}

function bindCartEvents() {
  cartContent.addEventListener("click", async (event) => {
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
