/**
 * RushFeeAPI - Manages all rush fee-related API calls
 * Handles CRUD operations for rush fees and timeframes
 */
class RushFeeAPI {
    constructor() {
        this.baseUrl = "/api/rush-fees";
    }

    /**
     * Get CSRF token from meta tag
     * @returns {string} CSRF token
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
    }

    /**
     * Fetch all rush fees with timeframes (public endpoint)
     * @returns {Promise<Array>} Array of rush fees with nested timeframes
     */
    async getAllRushFees() {
        try {
            const response = await fetch(`${this.baseUrl}`, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.data || [];
        } catch (error) {
            console.error("Error fetching rush fees:", error);
            throw error;
        }
    }

    /**
     * Fetch all rush fees including full details (admin only)
     * @returns {Promise<Array>} Array of all rush fees with metadata
     */
    async getAllRushFeesAdmin() {
        try {
            const response = await fetch(`${this.baseUrl}/admin/index`, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.data || [];
        } catch (error) {
            console.error("Error fetching admin rush fees:", error);
            throw error;
        }
    }

    /**
     * Upload a rush fee image
     * @param {File} imageFile - Image file to upload
     * @returns {Promise<Object>} Uploaded image payload { image_path, image_url }
     */
    async uploadRushFeeImage(imageFile) {
        try {
            if (!(imageFile instanceof File)) {
                throw new Error("Image file is required");
            }

            const formData = new FormData();
            formData.append("image", imageFile);

            const csrfToken = this.getCsrfToken();
            if (csrfToken && !formData.has("_token")) {
                formData.append("_token", csrfToken);
            }

            const response = await fetch(`${this.baseUrl}/upload-image`, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData,
            });

            let result = null;
            try {
                result = await response.json();
            } catch {
                result = null;
            }

            if (!response.ok) {
                const errorMessage =
                    result?.message ||
                    result?.error ||
                    `HTTP error! status: ${response.status}`;
                throw new Error(errorMessage);
            }

            if (!result?.success) {
                throw new Error(
                    result?.message || "Failed to upload rush fee image",
                );
            }

            return result.data || {};
        } catch (error) {
            console.error("Error uploading rush fee image:", error);
            throw error;
        }
    }

    /**
     * Create a new rush fee with timeframes
        * @param {Object} rushFeeData - Rush fee data { label, min_price, max_price|null, timeframes: [] }
     * @returns {Promise<Object>} Created rush fee with timeframes
     */
    async createRushFee(rushFeeData) {
        try {
            const response = await fetch(this.baseUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify(rushFeeData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(
                    errorData.message ||
                    `HTTP error! status: ${response.status}`,
                );
            }

            const data = await response.json();
            return data.data;
        } catch (error) {
            console.error("Error creating rush fee:", error);
            throw error;
        }
    }

    /**
     * Update an existing rush fee with timeframes
     * @param {number} rushFeeId - Rush fee ID
     * @param {Object} rushFeeData - Rush fee data to update
     * @returns {Promise<Object>} Updated rush fee
     */
    async updateRushFee(rushFeeId, rushFeeData) {
        try {
            const response = await fetch(`${this.baseUrl}/${rushFeeId}`, {
                method: "PUT",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify(rushFeeData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(
                    errorData.message ||
                    `HTTP error! status: ${response.status}`,
                );
            }

            const data = await response.json();
            return data.data;
        } catch (error) {
            console.error("Error updating rush fee:", error);
            throw error;
        }
    }

    /**
     * Delete a rush fee (cascades to timeframes)
     * @param {number} rushFeeId - Rush fee ID
     * @returns {Promise<Object>} Response data
     */
    async deleteRushFee(rushFeeId) {
        try {
            const response = await fetch(`${this.baseUrl}/${rushFeeId}`, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(
                    errorData.message ||
                    `HTTP error! status: ${response.status}`,
                );
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Error deleting rush fee:", error);
            throw error;
        }
    }

    /**
     * Reorder timeframes within a rush fee
     * @param {number} rushFeeId - Rush fee ID
     * @param {Array} timeframesArray - Array of { id, sort_order }
     * @returns {Promise<Object>} Response data
     */
    async reorderTimeframes(rushFeeId, timeframesArray) {
        try {
            const response = await fetch(
                `${this.baseUrl}/${rushFeeId}/reorder-timeframes`,
                {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                    body: JSON.stringify({ timeframes: timeframesArray }),
                },
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(
                    errorData.message ||
                    `HTTP error! status: ${response.status}`,
                );
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Error reordering timeframes:", error);
            throw error;
        }
    }
}

// Export as singleton
export default new RushFeeAPI();
