/**
 * MaterialAPI - Manages all material inventory-related API calls
 * Handles CRUD operations for materials and product-material relationships
 */
class MaterialAPI {
  constructor() {
    this.baseUrl = '/api/materials';
    this.isLoading = false;
  }

  getCsrfToken() {
    const token = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content');

    return token || '';
  }

  getHttpErrorMessage(statusCode) {
    if (statusCode === 419) {
      return 'Session expired. Please refresh the page and sign in again.';
    }

    if (statusCode === 403) {
      return 'You are not authorized to perform this action.';
    }

    if (statusCode === 401) {
      return 'Authentication required. Please sign in and try again.';
    }

    return 'Request failed.';
  }

  async request(url, options = {}) {
    const {
      headers: customHeaders = {},
      body,
      ...requestOptions
    } = options;

    const shouldSetJsonContentType =
      body !== undefined &&
      body !== null &&
      !(typeof FormData !== 'undefined' && body instanceof FormData);

    const response = await fetch(url, {
      ...requestOptions,
      ...(body !== undefined ? { body } : {}),
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': this.getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(shouldSetJsonContentType ? { 'Content-Type': 'application/json' } : {}),
        ...customHeaders,
      },
    });

    const contentType = response.headers.get('content-type') || '';
    const result = contentType.includes('application/json')
      ? await response.json()
      : { message: this.getHttpErrorMessage(response.status) };

    if (!response.ok) {
      const error = new Error(result.message || this.getHttpErrorMessage(response.status));
      error.statusCode = response.status;
      error.errors = result.errors || {};
      error.shortages = result.shortages || [];
      throw error;
    }

    return result;
  }

  /**
   * Fetch all materials from database
   * @returns {Promise<Array>} Array of material objects with products relation
   */
  async getAllMaterials() {
    try {
      this.isLoading = true;
      const result = await this.request(this.baseUrl, { method: 'GET' });

      if (result.success) {
        return result.data || [];
      } else {
        throw new Error(result.message || 'Failed to fetch materials');
      }
    } catch (error) {
      console.error('Error fetching materials:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Fetch a single material by ID
   * @param {number} materialId - The material ID to fetch
   * @returns {Promise<Object>} Material object with products relation
   */
  async getMaterial(materialId) {
    try {
      this.isLoading = true;
      const result = await this.request(`${this.baseUrl}/${materialId}`, {
        method: 'GET',
      });

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to fetch material');
      }
    } catch (error) {
      console.error('Error fetching material:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Create a new material with optional product associations
   * @param {Object} data - Material data containing:
   *   - name: string (material name, must be unique)
   *   - units: number (unit count for this material)
   *   - description: string (optional material description)
   *   - products: Array of {product_id, quantity} (optional product associations)
   * @returns {Promise<Object>} Created material object
   */
  async createMaterial(data) {
    try {
      this.isLoading = true;

      // Validate required fields
      if (!data.name) {
        throw new Error('Material name is required');
      }
      if (data.units === undefined || data.units === null) {
        throw new Error('Material units are required');
      }

      const result = await this.request(this.baseUrl, {
        method: 'POST',
        body: JSON.stringify(data)
      });

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to create material');
      }
    } catch (error) {
      console.error('Error creating material:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Update an existing material
   * @param {number} materialId - The material ID to update
   * @param {Object} data - Updated material data
   * @returns {Promise<Object>} Updated material object
   */
  async updateMaterial(materialId, data) {
    try {
      this.isLoading = true;

      const result = await this.request(`${this.baseUrl}/${materialId}`, {
        method: 'PUT',
        body: JSON.stringify(data)
      });

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to update material');
      }
    } catch (error) {
      console.error('Error updating material:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Delete a material and detach from all products
   * @param {number} materialId - The material ID to delete
   * @returns {Promise<Object>} Success response
   */
  async deleteMaterial(materialId) {
    try {
      this.isLoading = true;

      const result = await this.request(`${this.baseUrl}/${materialId}`, {
        method: 'DELETE',
      });

      if (result.success) {
        return result;
      } else {
        throw new Error(result.message || 'Failed to delete material');
      }
    } catch (error) {
      console.error('Error deleting material:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Update product associations for a material
   * @param {number} materialId - The material ID
   * @param {Array} products - Array of {product_id, quantity} objects
   * @returns {Promise<Object>} Updated material object
   */
  async syncMaterialProducts(materialId, products) {
    try {
      this.isLoading = true;

      const result = await this.request(`${this.baseUrl}/${materialId}`, {
        method: 'PUT',
        body: JSON.stringify({ products })
      });

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Failed to update product associations');
      }
    } catch (error) {
      console.error('Error syncing material products:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Get all products associated with a material
   * @param {number} materialId - The material ID
   * @returns {Promise<Array>} Array of products with quantity pivot data
   */
  async getMaterialProducts(materialId) {
    try {
      const material = await this.getMaterial(materialId);
      return material.products || [];
    } catch (error) {
      console.error('Error fetching material products:', error);
      return [];
    }
  }

  /**
   * Search materials by name or get by unit count range
   * @param {Object} filters - Search filters
   *   - name: string (search term for material name)
   *   - minUnits: number (minimum unit threshold)
   *   - maxUnits: number (maximum unit threshold)
   * @returns {Promise<Array>} Filtered array of materials
   */
  async searchMaterials(filters = {}) {
    try {
      const allMaterials = await this.getAllMaterials();

      return allMaterials.filter(material => {
        // Filter by name
        if (filters.name && !material.name.toLowerCase().includes(filters.name.toLowerCase())) {
          return false;
        }

        // Filter by unit count range
        if (filters.minUnits && material.units < filters.minUnits) {
          return false;
        }
        if (filters.maxUnits && material.units > filters.maxUnits) {
          return false;
        }

        return true;
      });
    } catch (error) {
      console.error('Error searching materials:', error);
      return [];
    }
  }
}

// Export as singleton
export default new MaterialAPI();
