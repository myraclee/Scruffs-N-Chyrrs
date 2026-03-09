/**
 * HomeImageAPI - Manages all home image-related API calls
 * Handles CRUD operations for images displayed on the home page
 */
class HomeImageAPI {
  constructor() {
    this.baseUrl = '/api/home-images';
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
   * Fetch all home page images
   * @returns {Promise<Array>} Array of home image objects
   */
  async getAllImages() {
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
        throw new Error(result.message || 'Failed to fetch home images');
      }
    } catch (error) {
      console.error('Error fetching home images:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Upload a new home page image
   * @param {FormData} formData - FormData containing the image file
   * @returns {Promise<Object>} Created image object with id and image_path
   */
  async createImage(formData) {
    try {
      this.isLoading = true;

      // Validate FormData
      if (!formData.has('image')) {
        throw new Error('Image file is required');
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
        const errorMsg = result.message || result.error || 'Failed to upload image';
        throw new Error(errorMsg);
      }

      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message || 'Unexpected error uploading image');
      }
    } catch (error) {
      console.error('Error uploading home image:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  /**
   * Delete a home page image
   * @param {number} id - The home image ID to delete
   * @returns {Promise<void>}
   */
  async deleteImage(id) {
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
        const errorMsg = result.message || result.error || 'Failed to delete image';
        throw new Error(errorMsg);
      }

      if (!result.success) {
        throw new Error(result.message || 'Unexpected error deleting image');
      }
    } catch (error) {
      console.error('Error deleting home image:', error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }
}

// Export as singleton
export default new HomeImageAPI();
