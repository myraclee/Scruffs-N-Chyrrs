/**
 * MaterialAPI - Manages all material inventory-related API calls
 * Handles CRUD operations for materials and product-material relationships
 */
class MaterialAPI {
  constructor() {
    this.baseUrl = '/api/materials';
    this.isLoading = false;
  }

  /**
   * Fetch all materials from database
   * @returns {Promise<Array>} Array of material objects with products relation
   */
  async getAllMaterials() {
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
      const response = await fetch(`${this.baseUrl}/${materialId}`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

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

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (!response.ok) {
        // Handle validation errors
        if (result.errors) {
          const errorMessages = Object.values(result.errors).flat().join(', ');
          throw new Error(errorMessages);
        }
        throw new Error(result.error || result.message || 'Failed to create material');
      }

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

      const response = await fetch(`${this.baseUrl}/${materialId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (!response.ok) {
        if (result.errors) {
          const errorMessages = Object.values(result.errors).flat().join(', ');
          throw new Error(errorMessages);
        }
        throw new Error(result.error || result.message || 'Failed to update material');
      }

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

      const response = await fetch(`${this.baseUrl}/${materialId}`, {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        }
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || result.message || 'Failed to delete material');
      }

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

      const response = await fetch(`${this.baseUrl}/${materialId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ products })
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || result.message || 'Failed to update product associations');
      }

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
