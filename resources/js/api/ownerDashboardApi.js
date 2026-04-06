/**
 * OwnerDashboardAPI - Owner dashboard metrics client.
 * Handles year-based dashboard metric requests.
 */
class OwnerDashboardAPI {
  constructor() {
    this.baseUrl = "/api/owner/dashboard/metrics";
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

  async getMetrics(filters = {}) {
    const query = new URLSearchParams();

    if (Number.isInteger(filters.year)) {
      query.set("year", String(filters.year));
    }

    if (Number.isInteger(filters.month)) {
      query.set("month", String(filters.month));
    }

    const queryString = query.toString();
    const url = queryString ? `${this.baseUrl}?${queryString}` : this.baseUrl;

    return this.request(url, { method: "GET" });
  }
}

export default new OwnerDashboardAPI();
