/**
 * Custom Notification System
 * Professional modals for success, error, and warning messages
 */

// Create notification modal HTML structure
function createNotificationModal() {
    const modal = document.createElement('div');
    modal.id = 'notificationModal';
    modal.className = 'notification-modal';
    modal.innerHTML = `
        <div class="notification-content">
            <div class="notification-header">
                <div class="notification-icon" id="notificationIcon"></div>
                <div class="notification-title">
                    <h3 id="notificationTitle"></h3>
                    <p id="notificationSubtitle"></p>
                </div>
            </div>
            <div class="notification-body" id="notificationBody"></div>
            <div class="notification-footer" id="notificationFooter"></div>
        </div>
    `;
    document.body.appendChild(modal);

    // Close on background click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeNotification();
        }
    });
}

// Show notification
function showNotification(options) {
    const {
        type = 'success', // 'success', 'error', 'warning'
        title,
        subtitle = '',
        message,
        buttons = []
    } = options;

    // Create modal if it doesn't exist
    if (!document.getElementById('notificationModal')) {
        createNotificationModal();
    }

    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const titleEl = document.getElementById('notificationTitle');
    const subtitleEl = document.getElementById('notificationSubtitle');
    const body = document.getElementById('notificationBody');
    const footer = document.getElementById('notificationFooter');

    // Set icon based on type
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠'
    };

    icon.className = `notification-icon ${type}`;
    icon.textContent = icons[type] || '✓';

    // Set content
    titleEl.textContent = title;
    subtitleEl.textContent = subtitle;
    body.innerHTML = message;

    // Set buttons
    if (buttons.length === 0) {
        // Default OK button
        buttons.push({
            text: 'OK',
            type: 'primary',
            onClick: closeNotification
        });
    }

    footer.innerHTML = buttons.map((btn, index) => {
        return `<button class="notification-btn ${btn.type || 'primary'}" data-btn-index="${index}">${btn.text}</button>`;
    }).join('');

    // Attach button click handlers
    footer.querySelectorAll('button').forEach((btn, index) => {
        btn.addEventListener('click', () => {
            if (buttons[index].onClick) {
                buttons[index].onClick();
            } else {
                closeNotification();
            }
        });
    });

    // Show modal
    modal.classList.add('show');
}

// Close notification
function closeNotification() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Shortcut functions
function showSuccess(title, message, onClose) {
    showNotification({
        type: 'success',
        title: title,
        subtitle: 'Operation completed successfully',
        message: message,
        buttons: [{
            text: 'OK',
            type: 'success',
            onClick: () => {
                closeNotification();
                if (onClose) onClose();
            }
        }]
    });
}

function showError(title, message) {
    showNotification({
        type: 'error',
        title: title,
        subtitle: 'An error occurred',
        message: message,
        buttons: [{
            text: 'Close',
            type: 'danger',
            onClick: closeNotification
        }]
    });
}

function showWarning(title, message, onConfirm, onCancel) {
    showNotification({
        type: 'warning',
        title: title,
        subtitle: 'Please confirm this action',
        message: message,
        buttons: [
            {
                text: 'Cancel',
                type: 'secondary',
                onClick: () => {
                    closeNotification();
                    if (onCancel) onCancel();
                }
            },
            {
                text: 'Confirm',
                type: 'danger',
                onClick: () => {
                    closeNotification();
                    if (onConfirm) onConfirm();
                }
            }
        ]
    });
}

// Export functions (for module usage)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showNotification,
        closeNotification,
        showSuccess,
        showError,
        showWarning
    };
}

