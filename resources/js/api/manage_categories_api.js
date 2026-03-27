/**
 * ManageCategoriesAPI - Manages all FAQ category-related API calls
 * Handles CRUD operations for FAQ categories
 */
class ManageCategoriesAPI {
  constructor() {
    this.baseUrl = '/api/faq-categories';
  }

  /**
   * Get CSRF token from meta tag
   * @returns {string} CSRF token
   */
  getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
  }

  /**
   * Fetch all FAQ categories
   * @returns {Promise<Array>} Array of category objects
   */
  async getAllCategories() {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data || [];
    } catch (error) {
      console.error('Error fetching categories:', error);
      throw error;
    }
  }

  /**
   * Create a new FAQ category
   * @param {Object} categoryData - Category data { name, sort_order }
   * @returns {Promise<Object>} Created category
   */
  async createCategory(categoryData) {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(categoryData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw {
          status: response.status,
          message: data.message || `HTTP error! status: ${response.status}`,
          errors: data.errors || {},
        };
      }

      return data.data;
    } catch (error) {
      console.error('Error creating category:', error);
      throw error;
    }
  }

  /**
   * Update an existing FAQ category
   * @param {number} categoryId - Category ID
   * @param {Object} categoryData - Updated category data { name?, sort_order? }
   * @returns {Promise<Object>} Updated category
   */
  async updateCategory(categoryId, categoryData) {
    try {
      const response = await fetch(`${this.baseUrl}/${categoryId}`, {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(categoryData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw {
          status: response.status,
          message: data.message || `HTTP error! status: ${response.status}`,
          errors: data.errors || {},
        };
      }

      return data.data;
    } catch (error) {
      console.error('Error updating category:', error);
      throw error;
    }
  }

  /**
   * Delete a FAQ category
   * @param {number} categoryId - Category ID
   * @returns {Promise<Object>} Response data
   */
  async deleteCategory(categoryId) {
    try {
      const response = await fetch(`${this.baseUrl}/${categoryId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw {
          status: response.status,
          message: data.message || `HTTP error! status: ${response.status}`,
          faq_count: data.faq_count || 0,
        };
      }

      return data;
    } catch (error) {
      console.error('Error deleting category:', error);
      throw error;
    }
  }
}

// Export as singleton
export default new ManageCategoriesAPI();
