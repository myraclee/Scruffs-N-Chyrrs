/**
 * CustomerOrderAPI - Manages customer order-related API calls
 * Handles fetching order templates and submitting orders
 */
class CustomerOrderAPI {
  constructor() {
    this.orderBaseUrl = '/api/customer-orders';
    this.cartBaseUrl = '/api/customer-cart';
  }

  /**
   * Get CSRF token from meta tag
   * @returns {string} CSRF token
   */
  getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
      console.warn('CSRF token not found in meta tag');
    }
    return token || '';
  }

  async request(url, options = {}) {
    const isFormData = options.body instanceof FormData;

    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': this.getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.body && !isFormData
          ? { 'Content-Type': 'application/json' }
          : {}),
        ...(options.headers || {}),
      },
      ...options,
    });

    const contentType = response.headers.get('content-type') || '';
    const result = contentType.includes('application/json')
      ? await response.json()
      : { message: await response.text() };

    if (!response.ok) {
      return {
        success: false,
        statusCode: response.status,
        message: result.message || 'Request failed.',
        error_code: result.error_code || null,
        errors: result.errors || {},
        shortages: Array.isArray(result.shortages) ? result.shortages : [],
        max_order_quantity:
          Number.isInteger(result.max_order_quantity) && result.max_order_quantity >= 0
            ? result.max_order_quantity
            : null,
        configuration_issues: Array.isArray(result.configuration_issues)
          ? result.configuration_issues
          : [],
        inventory:
          result.inventory && typeof result.inventory === 'object'
            ? result.inventory
            : null,
      };
    }

    return result;
  }

  /**
   * Fetch order template for a product
   * Gets all configuration: options, pricings, discounts, rush fees
   * @param {number} productId - The product ID
   * @returns {Promise<Object>} Template data with product, options, pricings, discounts, rush fees
   */
  async getOrderTemplate(productId, options = {}) {
    try {
      const queryParams = new URLSearchParams();
      const selectedOptionTypeIds = Array.isArray(options.selectedOptionTypeIds)
        ? options.selectedOptionTypeIds
        : [];

      selectedOptionTypeIds
        .map((id) => Number(id))
        .filter((id) => Number.isInteger(id) && id > 0)
        .forEach((id) => {
          queryParams.append('selected_option_type_ids[]', String(id));
        });

      const endpoint = queryParams.toString()
        ? `${this.orderBaseUrl}/product/${productId}/template?${queryParams.toString()}`
        : `${this.orderBaseUrl}/product/${productId}/template`;

      const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        let errorData = null;

        try {
          errorData = await response.json();
        } catch {
          errorData = null;
        }

        const apiError = new Error(errorData?.message || `HTTP error! status: ${response.status}`);
        apiError.status = response.status;
        apiError.payload = errorData;
        throw apiError;
      }

      const result = await response.json();

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to fetch order template');
      }
    } catch (error) {
      console.error('Error fetching order template:', error);
      throw error;
    }
  }

  /**
   * Submit a new order
   * Authorization required (user must be logged in)
   * @param {Object} orderData - Order configuration object
   * @returns {Promise<Object>} Server response with order details
   */
  async submitOrder(orderData) {
    try {
      const response = await this.request(this.orderBaseUrl, {
        method: 'POST',
        body: JSON.stringify(orderData),
      });

      return response;
    } catch (error) {
      console.error('Error submitting order:', error);
      return {
        success: false,
        message: 'Network error occurred. Please try again.',
      };
    }
  }

  async getMyOrders(filters = {}) {
    try {
      const query = new URLSearchParams(filters).toString();
      const url = query ? `${this.orderBaseUrl}?${query}` : this.orderBaseUrl;
      return await this.request(url, { method: 'GET' });
    } catch (error) {
      console.error('Error fetching customer orders:', error);
      return {
        success: false,
        message: 'Unable to load your orders right now.',
      };
    }
  }

  async getOrderGroup(orderGroupId) {
    try {
      return await this.request(`${this.orderBaseUrl}/${orderGroupId}`, { method: 'GET' });
    } catch (error) {
      console.error('Error fetching order group:', error);
      return {
        success: false,
        message: 'Unable to load order details.',
      };
    }
  }

  async updateOrderDetails(orderGroupId, payload) {
    try {
      return await this.request(`${this.orderBaseUrl}/${orderGroupId}/details`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
    } catch (error) {
      console.error('Error updating order details:', error);
      return {
        success: false,
        message: 'Unable to update order details.',
      };
    }
  }

  async getCart() {
    try {
      return await this.request(this.cartBaseUrl, { method: 'GET' });
    } catch (error) {
      console.error('Error fetching cart:', error);
      return {
        success: false,
        message: 'Unable to load your cart right now.',
      };
    }
  }

  async addCartItem(itemData) {
    try {
      return await this.request(`${this.cartBaseUrl}/items`, {
        method: 'POST',
        body: JSON.stringify(itemData),
      });
    } catch (error) {
      console.error('Error adding cart item:', error);
      return {
        success: false,
        message: 'Unable to add item to cart.',
      };
    }
  }

  async updateCartItem(itemId, itemData) {
    try {
      return await this.request(`${this.cartBaseUrl}/items/${itemId}`, {
        method: 'PATCH',
        body: JSON.stringify(itemData),
      });
    } catch (error) {
      console.error('Error updating cart item:', error);
      return {
        success: false,
        message: 'Unable to update cart item.',
      };
    }
  }

  async removeCartItem(itemId) {
    try {
      return await this.request(`${this.cartBaseUrl}/items/${itemId}`, {
        method: 'DELETE',
      });
    } catch (error) {
      console.error('Error removing cart item:', error);
      return {
        success: false,
        message: 'Unable to remove cart item.',
      };
    }
  }

  async checkoutCart(generalDriveLink) {
    try {
      return await this.request(`${this.cartBaseUrl}/checkout`, {
        method: 'POST',
        body: JSON.stringify({ general_drive_link: generalDriveLink }),
      });
    } catch (error) {
      console.error('Error during checkout:', error);
      return {
        success: false,
        message: 'Checkout failed. Please try again.',
      };
    }
  }

  async submitPaymentProof(orderGroupId, payload) {
    try {
      const formData = new FormData();
      formData.append('payment_method', payload.payment_method);
      formData.append('payment_reference_number', payload.payment_reference_number);
      formData.append('payment_proof', payload.payment_proof);

      return await this.request(`${this.orderBaseUrl}/${orderGroupId}/payment-proof`, {
        method: 'POST',
        body: formData,
      });
    } catch (error) {
      console.error('Error submitting payment proof:', error);
      return {
        success: false,
        message: 'Unable to submit payment proof.',
      };
    }
  }

  async cancelOrder(orderGroupId) {
    try {
      return await this.request(`${this.orderBaseUrl}/${orderGroupId}/cancel`, {
        method: 'PATCH',
      });
    } catch (error) {
      console.error('Error cancelling order:', error);
      return {
        success: false,
        message: 'Unable to cancel order.',
      };
    }
  }
}

// Export as singleton
export default new CustomerOrderAPI();
