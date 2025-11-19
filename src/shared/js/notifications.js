// ============================================================================
// CUSTOM NOTIFICATION SYSTEM - Professional modals instead of alert()
// ============================================================================

const Notify = {
    /**
     * Show success notification
     * @param {string} message - Main message
     * @param {string} details - Optional details
     */
    success(message, details = null) {
        this._show('success', '✅', 'Success', message, details);
    },

    /**
     * Show error notification
     * @param {string} message - Main message
     * @param {string} details - Optional details (e.g., error trace)
     */
    error(message, details = null) {
        this._show('error', '❌', 'Error', message, details);
    },

    /**
     * Show warning notification
     * @param {string} message - Main message
     * @param {string} details - Optional details
     */
    warning(message, details = null) {
        this._show('warning', '⚠️', 'Warning', message, details);
    },

    /**
     * Show info notification
     * @param {string} message - Main message
     * @param {string} details - Optional details
     */
    info(message, details = null) {
        this._show('info', 'ℹ️', 'Information', message, details);
    },

    /**
     * Show confirmation dialog
     * @param {string} message - Confirmation message
     * @param {function} onConfirm - Callback when confirmed
     * @param {string} confirmText - Text for confirm button (default: "Confirm")
     * @param {string} cancelText - Text for cancel button (default: "Cancel")
     */
    confirm(message, onConfirm, confirmText = 'Confirm', cancelText = 'Cancel') {
        const overlay = document.createElement('div');
        overlay.className = 'notification-overlay';

        overlay.innerHTML = `
            <div class="notification-box notification-confirm">
                <div class="notification-header warning">
                    <div class="notification-icon">⚠️</div>
                    <h3 class="notification-title">Confirm Action</h3>
                </div>
                <div class="notification-body">
                    <p class="notification-message">${this._escapeHtml(message)}</p>
                </div>
                <div class="notification-footer">
                    <button class="notification-btn notification-btn-secondary" onclick="this.closest('.notification-overlay').remove()">
                        ${this._escapeHtml(cancelText)}
                    </button>
                    <button class="notification-btn notification-btn-danger" id="confirmBtn">
                        ${this._escapeHtml(confirmText)}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Handle confirm
        document.getElementById('confirmBtn').onclick = () => {
            overlay.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        };

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
    },

    /**
     * Internal method to show notification
     * @private
     */
    _show(type, icon, title, message, details) {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; max-width: 400px;';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `notification-toast notification-${type}`;

        const detailsHtml = details ? `
            <div class="notification-details" style="margin-top: 8px; font-size: 13px; color: inherit; opacity: 0.9;">
                <strong>Details:</strong><br>
                ${this._escapeHtml(details)}
            </div>
        ` : '';

        // Set colors based on type
        const colors = {
            success: { bg: '#d4edda', border: '#28a745', text: '#155724' },
            error: { bg: '#f8d7da', border: '#dc3545', text: '#721c24' },
            warning: { bg: '#fff3cd', border: '#ffc107', text: '#856404' },
            info: { bg: '#d1ecf1', border: '#17a2b8', text: '#0c5460' }
        };

        const color = colors[type];

        toast.innerHTML = `
            <div style="background: ${color.bg}; border-left: 4px solid ${color.border}; border-radius: 8px; padding: 15px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 300px; max-width: 400px; animation: slideIn 0.3s ease-out;">
                <div style="display: flex; align-items: start; gap: 12px;">
                    <div style="font-size: 24px; line-height: 1;">${icon}</div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; color: ${color.text}; font-size: 15px; font-weight: 600;">${this._escapeHtml(title)}</h4>
                        <p style="margin: 0; color: ${color.text}; font-size: 14px; line-height: 1.4;">${this._escapeHtml(message)}</p>
                        ${detailsHtml}
                    </div>
                    <button onclick="this.closest('.notification-toast').remove()" style="background: none; border: none; color: ${color.text}; font-size: 20px; cursor: pointer; padding: 0; line-height: 1; opacity: 0.7; margin-left: 10px;">×</button>
                </div>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Auto-remove after duration
        const duration = type === 'error' ? 8000 : 5000; // Errors stay longer
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }
        }, duration);
    },

    /**
     * Escape HTML to prevent XSS
     * @private
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Add CSS animations
if (!document.getElementById('notification-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .notification-toast {
            animation: slideIn 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
}

// Make globally available
window.Notify = Notify;

console.log('[NOTIFY] ✅ Custom notification system loaded');

