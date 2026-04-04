/**
 * OwnerOrderAPI - Admin order management client.
 * Handles listing, filtering, viewing details, and updating statuses.
 */
class OwnerOrderAPI {
  constructor() {
    this.baseUrl = "/api/owner/orders";
  }

  getCsrfToken() {
    const token = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content");
    return token || "";
  }

  async request(url, options = {}) {
    const response = await fetch(url, {
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN": this.getCsrfToken(),
        "X-Requested-With": "XMLHttpRequest",
        ...(options.body ? { "Content-Type": "application/json" } : {}),
        ...(options.headers || {}),
      },
      ...options,
    });

    const contentType = response.headers.get("content-type") || "";
    const result = contentType.includes("application/json")
      ? await response.json()
      : { message: await response.text() };

    if (!response.ok) {
      return {
        success: false,
        statusCode: response.status,
        message: result.message || "Request failed.",
        errors: result.errors || {},
      };
    }

    return result;
  }

  async getOrders(filters = {}) {
    const query = new URLSearchParams(filters).toString();
    const url = query ? `${this.baseUrl}?${query}` : this.baseUrl;
    return this.request(url, { method: "GET" });
  }

  async getOrder(orderGroupId) {
    return this.request(`${this.baseUrl}/${orderGroupId}`, {
      method: "GET",
    });
  }

  async updateOrderStatus(orderGroupId, status) {
    return this.request(`${this.baseUrl}/${orderGroupId}/status`, {
      method: "PATCH",
      body: JSON.stringify({ status }),
    });
  }
}

export default new OwnerOrderAPI();
