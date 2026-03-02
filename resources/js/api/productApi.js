/**
 * ProductAPI - Manages all product-related API calls
 * Handles CRUD operations for product samples and price images
 */
class ProductAPI {
  constructor() {
    this.baseUrl = '/api/products';
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
   * Fetch all products from database
   * @returns {Promise<Array>} Array of product objects with priceImages and materials relations
   */
  async getAllProducts() {
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
        throw new Error(result.message || 'Failed to fetch products');
      }
    } catch (error) {
      console.error('Error fetching products:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Fetch a single product by ID
   * @param {number} productId - The product ID to fetch
   * @returns {Promise<Object>} Product object with relations
   */
  async getProduct(productId) {
    try {
      this.isLoading = true;
      const response = await fetch(`${this.baseUrl}/${productId}`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to fetch product');
      }
    } catch (error) {
      console.error('Error fetching product:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Create a new product with cover image and price images
   * @param {FormData} formData - Form data containing:
   *   - name: string (product name)
   *   - description: string (product description)
   *   - cover_image: File (cover image)
   *   - price_images: FileList (price list images)
   * @returns {Promise<Object>} Created product object
   */
  async createProduct(formData) {
    try {
      this.isLoading = true;

      // Validate required fields
      if (!formData.get('name')) {
        throw new Error('Product name is required');
      }
      if (!formData.get('cover_image')) {
        throw new Error('Cover image is required');
      }

      // Add CSRF token to FormData as fallback
      if (this.getCsrfToken() && !formData.has('_token')) {
        formData.append('_token', this.getCsrfToken());
      }

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
        // Don't set Content-Type header - browser will set it with boundary for multipart
      });

      const result = await response.json();

      if (!response.ok) {
        // Handle validation errors
        if (result.errors) {
          const errorMessages = Object.values(result.errors).flat().join(', ');
          throw new Error(errorMessages);
        }
        throw new Error(result.error || result.message || 'Failed to create product');
      }

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to create product');
      }
    } catch (error) {
      console.error('Error creating product:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Update an existing product
   * @param {number} productId - The product ID to update
   * @param {FormData} formData - Form data containing fields to update
   * @returns {Promise<Object>} Updated product object
   */
  async updateProduct(productId, formData) {
    try {
      this.isLoading = true;

      // Laravel requires _method field for PUT requests when using POST
      if (!formData.get('_method')) {
        formData.append('_method', 'PUT');
      }

      // Add CSRF token to FormData as fallback
      if (this.getCsrfToken() && !formData.has('_token')) {
        formData.append('_token', this.getCsrfToken());
      }

      const response = await fetch(`${this.baseUrl}/${productId}`, {
        method: 'POST', // Using POST with _method=PUT for form data
        body: formData,
        headers: {
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
      });

      const result = await response.json();

      if (!response.ok) {
        if (result.errors) {
          const errorMessages = Object.values(result.errors).flat().join(', ');
          throw new Error(errorMessages);
        }
        throw new Error(result.error || result.message || 'Failed to update product');
      }

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to update product');
      }
    } catch (error) {
      console.error('Error updating product:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Delete a product and all associated images
   * @param {number} productId - The product ID to delete
   * @returns {Promise<Object>} Success response
   */
  async deleteProduct(productId) {
    try {
      this.isLoading = true;

      const response = await fetch(`${this.baseUrl}/${productId}`, {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || result.message || 'Failed to delete product');
      }

      if (result.success) {
        return result;
      } else {
        throw new Error(result.message || 'Failed to delete product');
      }
    } catch (error) {
      console.error('Error deleting product:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Get all price images for a specific product
   * @param {number} productId - The product ID
   * @returns {Promise<Array>} Array of price images
   */
  async getProductPriceImages(productId) {
    try {
      const product = await this.getProduct(productId);
      return product.price_images || [];
    } catch (error) {
      console.error('Error fetching price images:', error);
      return [];
    }
  }
}

// Export as singleton
export default new ProductAPI();
