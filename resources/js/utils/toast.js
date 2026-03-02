/**
 * Toast Notification System
 * Displays temporary notification messages to users with different severity levels
 */
class Toast {
  constructor() {
    this.container = null;
    this.initContainer();
    this.defaultDuration = 4000; // 4 seconds
  }

  /**
   * Initialize the toast container if not already present
   */
  initContainer() {
    // Check if container already exists
    this.container = document.getElementById('toast-container');

    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-width: 400px;
                pointer-events: none;
            `;
      document.body.appendChild(this.container);
    }
  }

  /**
   * Show a success toast notification
   * @param {string} message - The message to display
   * @param {number} duration - How long to show the toast (ms)
   */
  success(message, duration = this.defaultDuration) {
    this.show(message, 'success', duration);
  }

  /**
   * Show an error toast notification
   * @param {string} message - The message to display
   * @param {number} duration - How long to show the toast (ms)
   */
  error(message, duration = this.defaultDuration) {
    this.show(message, 'error', duration);
  }

  /**
   * Show an info toast notification
   * @param {string} message - The message to display
   * @param {number} duration - How long to show the toast (ms)
   */
  info(message, duration = this.defaultDuration) {
    this.show(message, 'info', duration);
  }

  /**
   * Show a warning toast notification
   * @param {string} message - The message to display
   * @param {number} duration - How long to show the toast (ms)
   */
  warning(message, duration = this.defaultDuration) {
    this.show(message, 'warning', duration);
  }

  /**
   * Core method to display a toast
   * @param {string} message - The message to display
   * @param {string} type - The toast type: 'success', 'error', 'info', 'warning'
   * @param {number} duration - How long to show the toast (ms)
   * @private
   */
  show(message, type = 'info', duration = this.defaultDuration) {
    // Ensure container exists
    if (!this.container) {
      this.initContainer();
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    // Define styles for different toast types
    const styles = {
      success: {
        bgColor: '#10b981',
        icon: '✓'
      },
      error: {
        bgColor: '#ef4444',
        icon: '✕'
      },
      info: {
        bgColor: '#3b82f6',
        icon: 'ℹ'
      },
      warning: {
        bgColor: '#f59e0b',
        icon: '⚠'
      }
    };

    const style = styles[type] || styles.info;

    toast.style.cssText = `
            background-color: ${style.bgColor};
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Coolvetica', Arial, sans-serif;
            font-size: 14px;
            font-weight: 500;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
            cursor: pointer;
            min-width: 280px;
            max-width: 400px;
            word-wrap: break-word;
            white-space: normal;
        `;

    // Create icon span
    const iconSpan = document.createElement('span');
    iconSpan.style.cssText = `
            font-size: 18px;
            font-weight: bold;
            flex-shrink: 0;
        `;
    iconSpan.textContent = style.icon;

    // Create message span
    const messageSpan = document.createElement('span');
    messageSpan.textContent = message;
    messageSpan.style.cssText = `
            flex: 1;
        `;

    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '×';
    closeBtn.style.cssText = `
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: opacity 0.2s;
        `;

    closeBtn.addEventListener('mouseover', () => {
      closeBtn.style.opacity = '0.7';
    });
    closeBtn.addEventListener('mouseout', () => {
      closeBtn.style.opacity = '1';
    });
    closeBtn.addEventListener('click', () => {
      this.removeToast(toast);
    });

    // Append elements
    toast.appendChild(iconSpan);
    toast.appendChild(messageSpan);
    toast.appendChild(closeBtn);

    // Add to container
    this.container.appendChild(toast);

    // Container must be visible
    this.container.style.pointerEvents = 'auto';

    // Auto-remove after duration (if duration > 0)
    if (duration > 0) {
      const timeoutId = setTimeout(() => {
        this.removeToast(toast);
      }, duration);

      // Store timeout ID to allow manual clearing
      toast.dataset.timeoutId = timeoutId;
    }

    // Add animation styles to document if not already present
    this.ensureAnimationStyles();

    return toast;
  }

  /**
   * Remove a toast element with animation
   * @param {HTMLElement} toast - The toast element to remove
   * @private
   */
  removeToast(toast) {
    // Clear any pending timeout
    if (toast.dataset.timeoutId) {
      clearTimeout(parseInt(toast.dataset.timeoutId));
    }

    // Add fade-out animation
    toast.style.animation = 'slideOutRight 0.3s ease-out forwards';

    // Remove after animation completes
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }

  /**
   * Clear all visible toasts
   */
  clearAll() {
    if (this.container) {
      const toasts = this.container.querySelectorAll('.toast');
      toasts.forEach(toast => this.removeToast(toast));
    }
  }

  /**
   * Ensure animation styles are in the document
   * @private
   */
  ensureAnimationStyles() {
    // Check if styles already exist
    if (document.getElementById('toast-animations')) {
      return;
    }

    // Create and inject animation styles
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }

            @media (max-width: 640px) {
                #toast-container {
                    left: 10px !important;
                    right: 10px !important;
                    max-width: none !important;
                }
            }
        `;
    document.head.appendChild(style);
  }
}

// Export as singleton
export default new Toast();
