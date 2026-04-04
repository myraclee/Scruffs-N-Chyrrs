import OwnerOrderAPI from "/resources/js/api/ownerOrderApi.js";
import Toast from "/resources/js/utils/toast.js";

const listContainer = document.getElementById("ownerOrdersList");
const searchInput = document.getElementById("ownerOrdersSearch");
const filterButtons = document.querySelectorAll(".filter_btn");

const detailsModal = document.getElementById("orderDetailsModal");
const loadingModal = document.getElementById("ownerOrderLoadingModal");
const closeDetailsModalBtn = document.getElementById("closeDetailsModalBtn");
const detailStatusSelect = document.getElementById("detailStatusSelect");

const detailOrderDate = document.getElementById("detailOrderDate");
const detailOrderId = document.getElementById("detailOrderId");
const detailCustomerName = document.getElementById("detailCustomerName");
const detailCustomerContact = document.getElementById("detailCustomerContact");
const detailCustomerEmail = document.getElementById("detailCustomerEmail");
const detailDriveLink = document.getElementById("detailDriveLink");
const detailItemsBody = document.getElementById("detailItemsBody");
const detailOrderTotal = document.getElementById("detailOrderTotal");

let activeFilter = "all";
let activeSearch = "";
let currentDetailOrderId = null;

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
  completed: "Completed Orders",
  cancelled: "Cancelled Orders",
};

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

    const success = await updateStatus(orderId, nextStatus);
    if (!success) {
      statusSelect.value = previousStatus;
      applyStatusClass(statusSelect, previousStatus);
    }
  });

  detailStatusSelect?.addEventListener("change", async () => {
    if (!currentDetailOrderId) return;

    const previousStatus = detailStatusSelect.dataset.currentStatus;
    const nextStatus = detailStatusSelect.value;
    const success = await updateStatus(currentDetailOrderId, nextStatus);

    if (!success) {
      detailStatusSelect.value = previousStatus;
      applyStatusClass(detailStatusSelect, previousStatus);
      return;
    }

    await openDetails(currentDetailOrderId);
  });

  closeDetailsModalBtn?.addEventListener("click", () => {
    detailsModal.style.display = "none";
    currentDetailOrderId = null;
  });
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

  detailOrderDate.textContent = new Date(group.created_at).toLocaleString();
  detailOrderId.textContent = `#${group.id}`;
  detailCustomerName.textContent = group.user?.name || "Unknown";
  detailCustomerContact.textContent = group.user?.contact_number || "Not provided";
  detailCustomerEmail.textContent = group.user?.email || "Not provided";

  if (group.general_drive_link) {
    detailDriveLink.textContent = group.general_drive_link;
    detailDriveLink.href = group.general_drive_link;
  } else {
    detailDriveLink.textContent = "No drive link submitted";
    detailDriveLink.href = "#";
  }

  detailItemsBody.innerHTML = (group.orders || [])
    .map((item, index) => {
      const optionsSummary = (item.formatted_options || [])
        .map((opt) => `${opt.option_label}: ${opt.selected_value}`)
        .join(" | ");

      return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.product_name || "Product"}<br><small>${optionsSummary || "No options"}</small></td>
                    <td>${item.special_instructions || "No file note"}</td>
                    <td>${item.quantity}</td>
                    <td>${formatMoney(item.discount_amount)}</td>
                    <td>${formatMoney(item.total_price)}</td>
                </tr>
            `;
    })
    .join("");

  detailOrderTotal.textContent = formatMoney(group.totals?.total_price);
  detailStatusSelect.value = group.status;
  detailStatusSelect.dataset.currentStatus = group.status;
  applyStatusClass(detailStatusSelect, group.status);

  detailsModal.style.display = "flex";
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
