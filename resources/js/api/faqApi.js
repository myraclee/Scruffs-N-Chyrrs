/**
 * FaqAPI - Manages all FAQ-related API calls
 * Handles CRUD operations for FAQ items
 */
class FaqAPI {
  constructor() {
    this.baseUrl = '/api/faqs';
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
   * Fetch all active FAQs (public endpoint)
   * @returns {Promise<Object>} FAQs grouped by category
   */
  async getAllFaqs() {
    try {
      const response = await fetch(`${this.baseUrl}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data || {};
    } catch (error) {
      console.error('Error fetching FAQs:', error);
      throw error;
    }
  }

  /**
   * Fetch all FAQs including inactive (admin only)
   * @returns {Promise<Object>} All FAQs grouped by category
   */
  async getAllFaqsAdmin() {
    try {
      const response = await fetch(`${this.baseUrl}/admin/index`, {
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
      return data.data || {};
    } catch (error) {
      console.error('Error fetching admin FAQs:', error);
      throw error;
    }
  }

  /**
   * Create a new FAQ
   * @param {Object} faqData - FAQ data { category, question, answer, sort_order?, is_active? }
   * @returns {Promise<Object>} Created FAQ
   */
  async createFaq(faqData) {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(faqData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Error creating FAQ:', error);
      throw error;
    }
  }

  /**
   * Update an existing FAQ
   * @param {number} faqId - FAQ ID
   * @param {Object} faqData - FAQ data to update
   * @returns {Promise<Object>} Updated FAQ
   */
  async updateFaq(faqId, faqData) {
    try {
      const response = await fetch(`${this.baseUrl}/${faqId}`, {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(faqData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Error updating FAQ:', error);
      throw error;
    }
  }

  /**
   * Delete a FAQ
   * @param {number} faqId - FAQ ID
   * @returns {Promise<Object>} Response data
   */
  async deleteFaq(faqId) {
    try {
      const response = await fetch(`${this.baseUrl}/${faqId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Error deleting FAQ:', error);
      throw error;
    }
  }

  /**
   * Reorder FAQs
   * @param {Array} faqsArray - Array of { id, sort_order }
   * @returns {Promise<Object>} Response data
   */
  async reorderFaqs(faqsArray) {
    try {
      const response = await fetch(`${this.baseUrl}/reorder`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify({ faqs: faqsArray }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Error reordering FAQs:', error);
      throw error;
    }
  }
}

// Export as singleton
export default new FaqAPI();
