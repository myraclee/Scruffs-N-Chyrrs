/**
 * ProductSampleAPI - Manages all product sample-related API calls
 * Handles CRUD operations for product samples with images
 */
class ProductSampleAPI {
  constructor() {
    this.baseUrl = '/api/product-samples';
    this.isLoading = false;
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
   * Fetch all product samples with images
   * @returns {Promise<Array>} Array of product sample objects
   */
  async getAllSamples() {
    try {
      this.isLoading = true;
      const response = await fetch(this.baseUrl);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        return result.data || [];
      } else {
        throw new Error(result.message || 'Failed to fetch product samples');
      }
    } catch (error) {
      console.error('Error fetching product samples:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Get a single product sample with its images
   * @param {number} id - The product sample ID
   * @returns {Promise<Object>} Product sample object with images
   */
  async getSample(id) {
    try {
      this.isLoading = true;
      const response = await fetch(`${this.baseUrl}/${id}`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to fetch product sample');
      }
    } catch (error) {
      console.error('Error fetching product sample:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Create a new product sample with images
   * @param {FormData} formData - FormData with name, description, and images
   * @returns {Promise<Object>} Created product sample object
   */
  async createSample(formData) {
    try {
      this.isLoading = true;

      // Validate FormData
      if (!formData.has('name')) {
        throw new Error('Sample name is required');
      }

      // Add CSRF token to FormData as fallback
      if (this.getCsrfToken() && !formData.has('_token')) {
        formData.append('_token', this.getCsrfToken());
      }

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': this.getCsrfToken(),
          Accept: 'application/json'
        }
      });

      const result = await response.json();

      if (!response.ok) {
        const errorMsg = result.message || result.error || 'Failed to create sample';
        throw new Error(errorMsg);
      }

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Unexpected error creating sample');
      }
    } catch (error) {
      console.error('Error creating product sample:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Update an existing product sample
   * @param {number} id - The product sample ID
   * @param {FormData} formData - FormData with updates and new images
   * @returns {Promise<Object>} Updated product sample object
   */
  async updateSample(id, formData) {
    try {
      this.isLoading = true;

      // Add CSRF token to FormData as fallback
      if (this.getCsrfToken() && !formData.has('_token')) {
        formData.append('_token', this.getCsrfToken());
      }

      const response = await fetch(`${this.baseUrl}/${id}`, {
        method: 'PUT',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': this.getCsrfToken(),
          Accept: 'application/json'
        }
      });

      const result = await response.json();

      if (!response.ok) {
        const errorMsg = result.message || result.error || 'Failed to update sample';
        throw new Error(errorMsg);
      }

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Unexpected error updating sample');
      }
    } catch (error) {
      console.error('Error updating product sample:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Delete a product sample and all its images
   * @param {number} id - The product sample ID
   * @returns {Promise<void>}
   */
  async deleteSample(id) {
    try {
      this.isLoading = true;

      const response = await fetch(`${this.baseUrl}/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': this.getCsrfToken(),
          Accept: 'application/json'
        }
      });

      const result = await response.json();

      if (!response.ok) {
        const errorMsg = result.message || result.error || 'Failed to delete sample';
        throw new Error(errorMsg);
      }

      if (!result.success) {
        throw new Error(result.message || 'Unexpected error deleting sample');
      }
    } catch (error) {
      console.error('Error deleting product sample:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }
}

// Export as singleton
export default new ProductSampleAPI();
