/**
 * FormState - Persists form data to localStorage
 * Prevents data loss during page navigation or accidental refreshes
 */
class FormState {
    constructor() {
        this.storagePrefix = "form_state_";
        this.autoSaveInterval = 1000; // Auto-save every 1 second
        this.activeAutoSaveIntervals = new Map();
    }

    /**
     * Generate a unique key for form storage
     * @param {string} formId - The form element ID
     * @returns {string} Storage key
     * @private
     */
    getStorageKey(formId) {
        return `${this.storagePrefix}${formId}`;
    }

    /**
     * Save form data to localStorage
     * @param {string} formId - The form element ID or form element itself
     * @param {Object} customData - Optional additional data to save with form state
     */
    save(formId) {
        const form = this.getFormElement(formId);
        if (!form) {
            console.warn(`Form with ID "${formId}" not found`);
            return;
        }

        // Collect form data
        const formData = new FormData(form);
        const data = {};

        // Convert FormData to plain object
        for (let [key, value] of formData.entries()) {
            // Handle multiple values for same key (checkboxes, multi-select)
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }

        // Save to localStorage
        try {
            localStorage.setItem(
                this.getStorageKey(formId),
                JSON.stringify(data),
            );
            console.log(`Form "${formId}" auto-saved to localStorage`);
        } catch (error) {
            console.error(
                `Error saving form "${formId}" to localStorage:`,
                error,
            );
        }
    }

    /**
     * Restore form data from localStorage
     * @param {string} formId - The form element ID or form element itself
     * @returns {Object} Restored form data object
     */
    restore(formId) {
        const form = this.getFormElement(formId);
        if (!form) {
            console.warn(`Form with ID "${formId}" not found`);
            return null;
        }

        try {
            const storageKey = this.getStorageKey(formId);
            const savedData = localStorage.getItem(storageKey);

            if (!savedData) {
                return null;
            }

            const data = JSON.parse(savedData);

            // Populate form fields with saved data
            for (let [name, value] of Object.entries(data)) {
                const field = form.elements[name];

                if (!field) {
                    continue;
                }

                // Handle different field types
                if (field.type === "checkbox") {
                    // For checkboxes, check if value is in the saved array
                    if (Array.isArray(value)) {
                        field.checked = value.includes(field.value);
                    } else {
                        field.checked = value === field.value;
                    }
                } else if (field.type === "radio") {
                    field.checked = field.value === value;
                } else if (field.multiple) {
                    // Handle multi-select
                    Array.from(field.options).forEach((option) => {
                        option.selected =
                            Array.isArray(value) &&
                            value.includes(option.value);
                    });
                } else {
                    // Handle text, textarea, number, email, etc.
                    field.value = value;
                }
            }

            console.log(`Form "${formId}" restored from localStorage`);
            return data;
        } catch (error) {
            console.error(
                `Error restoring form "${formId}" from localStorage:`,
                error,
            );
            return null;
        }
    }

    /**
     * Enable auto-save for a form
     * Automatically saves form data to localStorage at interval
     * @param {string} formId - The form element ID
     * @param {number} interval - Auto-save interval in milliseconds (default: 1000)
     */
    enableAutoSave(formId, interval = this.autoSaveInterval) {
        const form = this.getFormElement(formId);
        if (!form) {
            console.warn(`Form with ID "${formId}" not found`);
            return;
        }

        // Clear any existing auto-save for this form
        this.disableAutoSave(formId);

        // Save on input events
        const saveHandler = () => this.save(formId);
        form.addEventListener("input", saveHandler);
        form.addEventListener("change", saveHandler);

        // Also set up periodic save
        const intervalId = setInterval(() => {
            this.save(formId);
        }, interval);

        // Store handlers and interval ID for cleanup
        this.activeAutoSaveIntervals.set(formId, {
            intervalId,
            handlers: { saveHandler },
        });

        console.log(
            `Auto-save enabled for form "${formId}" (interval: ${interval}ms)`,
        );
    }

    /**
     * Disable auto-save for a form
     * @param {string} formId - The form element ID
     */
    disableAutoSave(formId) {
        const form = this.getFormElement(formId);
        if (!form) {
            return;
        }

        const autoSaveData = this.activeAutoSaveIntervals.get(formId);
        if (autoSaveData) {
            clearInterval(autoSaveData.intervalId);

            // Remove event listeners
            if (autoSaveData.handlers.saveHandler) {
                form.removeEventListener(
                    "input",
                    autoSaveData.handlers.saveHandler,
                );
                form.removeEventListener(
                    "change",
                    autoSaveData.handlers.saveHandler,
                );
            }

            this.activeAutoSaveIntervals.delete(formId);
            console.log(`Auto-save disabled for form "${formId}"`);
        }
    }

    /**
     * Clear saved data for a form
     * @param {string} formId - The form element ID
     */
    clear(formId) {
        try {
            localStorage.removeItem(this.getStorageKey(formId));
            console.log(`Cleared saved data for form "${formId}"`);
        } catch (error) {
            console.error(`Error clearing form data for "${formId}":`, error);
        }
    }

    /**
     * Clear all saved form data
     */
    clearAll() {
        try {
            // Get all keys that match our prefix
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key.startsWith(this.storagePrefix)) {
                    keysToRemove.push(key);
                }
            }

            // Remove all matching keys
            keysToRemove.forEach((key) => {
                localStorage.removeItem(key);
            });

            console.log(
                `Cleared all saved form data (${keysToRemove.length} forms)`,
            );
        } catch (error) {
            console.error("Error clearing all form data:", error);
        }
    }

    /**
     * Check if form has saved data
     * @param {string} formId - The form element ID
     * @returns {boolean} True if saved data exists
     */
    hasSavedData(formId) {
        try {
            return localStorage.getItem(this.getStorageKey(formId)) !== null;
        } catch (error) {
            console.error(`Error checking saved data for "${formId}":`, error);
            return false;
        }
    }

    /**
     * Get form element from ID or return if already an element
     * @param {string|HTMLFormElement} formId - Form ID or form element
     * @returns {HTMLFormElement|null} Form element or null
     * @private
     */
    getFormElement(formId) {
        if (formId instanceof HTMLFormElement) {
            return formId;
        }

        return document.getElementById(formId);
    }

    /**
     * Watch a form for changes and return callback when modified
     * Useful for enabling save buttons only when form has unsaved changes
     * @param {string} formId - The form element ID
     * @param {Function} onChange - Callback function(hasUnsavedChanges)
     * @returns {Function} Function to stop watching
     */
    watchForChanges(formId, onChange) {
        const form = this.getFormElement(formId);
        if (!form) {
            console.warn(`Form with ID "${formId}" not found`);
            return () => {};
        }

        let initialState = this.restore(formId) || this.getFormData(form);
        let hasChanges = false;

        const changeHandler = () => {
            const currentState = this.getFormData(form);
            const stateChanged =
                JSON.stringify(initialState) !== JSON.stringify(currentState);

            if (stateChanged !== hasChanges) {
                hasChanges = stateChanged;
                onChange(hasChanges);
            }
        };

        form.addEventListener("input", changeHandler);
        form.addEventListener("change", changeHandler);

        // Return unwatch function
        return () => {
            form.removeEventListener("input", changeHandler);
            form.removeEventListener("change", changeHandler);
        };
    }

    /**
     * Get current form data as object
     * @param {string|HTMLFormElement} formId - Form ID or form element
     * @returns {Object} Form data object
     * @private
     */
    getFormData(formId) {
        const form = this.getFormElement(formId);
        if (!form) {
            return {};
        }

        const formData = new FormData(form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }

        return data;
    }
}

// Export as singleton
export default new FormState();
