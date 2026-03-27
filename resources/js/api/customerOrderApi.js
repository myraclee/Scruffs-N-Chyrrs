/**
 * CustomerOrderAPI - Manages customer order-related API calls
 * Handles fetching order templates and submitting orders
 */
class CustomerOrderAPI {
  constructor() {
    this.baseUrl = '/api/customer-orders';
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

  /**
   * Fetch order template for a product
   * Gets all configuration: options, pricings, discounts, rush fees
   * @param {number} productId - The product ID
   * @returns {Promise<Object>} Template data with product, options, pricings, discounts, rush fees
   */
  async getOrderTemplate(productId) {
    try {
      const response = await fetch(`${this.baseUrl}/product/${productId}/template`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
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
      const csrfToken = this.getCsrfToken();

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(orderData),
      });

      const result = await response.json();

      if (!response.ok) {
        // Return error response for handling by caller
        return {
          success: false,
          statusCode: response.status,
          message: result.message || 'Failed to submit order',
          errors: result.errors || {},
        };
      }

      if (result.success) {
        return result;
      } else {
        return {
          success: false,
          message: result.message || 'Failed to submit order',
        };
      }
    } catch (error) {
      console.error('Error submitting order:', error);
      return {
        success: false,
        message: 'Network error occurred. Please try again.',
      };
    }
  }
}

// Export as singleton
export default new CustomerOrderAPI();
