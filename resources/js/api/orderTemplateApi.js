/**
 * OrderTemplateAPI - Manages all order template-related API calls
 * Handles CRUD operations for order templates with nested options, pricings, and discounts
 */
class OrderTemplateAPI {
  constructor() {
    this.baseUrl = '/api/order-templates';
  }

  /**
   * Get CSRF token from meta tag
   * @returns {string} CSRF token
   */
  getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
      throw new Error('CSRF token not found');
    }
    return token;
  }

  /**
   * Fetch all order templates with relationships
   * @returns {Promise<Array>} Array of order template objects
   */
  async getAllOrderTemplates() {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      return data.data || [];
    } catch (error) {
      console.error('Error fetching order templates:', error);
      throw error;
    }
  }

  /**
   * Fetch a single order template by ID
   * @param {number} id - Order template ID
   * @returns {Promise<Object>} Order template object with all relationships
   */
  async getOrderTemplate(id) {
    try {
      const response = await fetch(`${this.baseUrl}/${id}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error(`Error fetching order template ${id}:`, error);
      throw error;
    }
  }

  /**
   * Create a new order template
   * @param {Object} templateData - Template data with options, pricings, discounts
   * @returns {Promise<Object>} Created order template
   */
  async createOrderTemplate(templateData) {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(templateData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Error creating order template:', error);
      throw error;
    }
  }

  /**
   * Update an existing order template
   * @param {number} id - Order template ID
   * @param {Object} templateData - Updated template data
   * @returns {Promise<Object>} Updated order template
   */
  async updateOrderTemplate(id, templateData) {
    try {
      const response = await fetch(`${this.baseUrl}/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(templateData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error(`Error updating order template ${id}:`, error);
      throw error;
    }
  }

  /**
   * Delete an order template
   * @param {number} id - Order template ID
   * @returns {Promise<Object>} Success response
   */
  async deleteOrderTemplate(id) {
    try {
      const response = await fetch(`${this.baseUrl}/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error(`Error deleting order template ${id}:`, error);
      throw error;
    }
  }
}

// Export as singleton
export default new OrderTemplateAPI();
