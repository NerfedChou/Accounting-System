/**
 * Utility Functions - Shared JavaScript utilities
 * Accounting System
 */

/**
 * Format number as currency
 * @param {number} amount - Amount to format
 * @param {string} currency - Currency code (default: USD)
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currency = 'USD') {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount);
}

/**
 * Format date
 * @param {string|Date} date - Date to format
 * @param {string} format - Format type ('short', 'long', 'iso')
 * @returns {string} Formatted date string
 */
export function formatDate(date, format = 'short') {
  const d = new Date(date);

  if (format === 'iso') {
    return d.toISOString().split('T')[0];
  }

  if (format === 'long') {
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(d);
  }

  // Default short format
  return new Intl.DateTimeFormat('en-US').format(d);
}

/**
 * Format date and time
 * @param {string|Date} datetime - DateTime to format
 * @returns {string} Formatted datetime string
 */
export function formatDateTime(datetime) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(datetime));
}

/**
 * Debounce function calls
 * @param {Function} func - Function to debounce
 * @param {number} delay - Delay in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, delay = 300) {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(this, args), delay);
  };
}

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
export function showToast(message, type = 'info', duration = 3000) {
  const toast = document.createElement('div');
  toast.className = `toast toast--${type}`;
  toast.textContent = message;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/**
 * Show loading spinner
 * @param {string} message - Optional loading message
 */
export function showLoading(message = 'Loading...') {
  const existing = document.getElementById('loading-overlay');
  if (existing) return;

  const overlay = document.createElement('div');
  overlay.id = 'loading-overlay';
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  `;

  overlay.innerHTML = `
    <div style="
      background: white;
      padding: 2rem;
      border-radius: 0.5rem;
      text-align: center;
    ">
      <div class="spinner spinner--lg" style="margin: 0 auto 1rem;"></div>
      <p>${message}</p>
    </div>
  `;

  document.body.appendChild(overlay);
}

/**
 * Hide loading spinner
 */
export function hideLoading() {
  const overlay = document.getElementById('loading-overlay');
  if (overlay) {
    overlay.remove();
  }
}

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @returns {Promise<boolean>} User's choice
 */
export function confirmAction(message) {
  return new Promise((resolve) => {
    const confirmed = window.confirm(message);
    resolve(confirmed);
  });
}

/**
 * Show custom modal dialog
 * @param {Object} options - Modal options
 * @returns {Promise} User action promise
 */
export function showModal({ title, body, confirmText = 'Confirm', cancelText = 'Cancel' }) {
  return new Promise((resolve) => {
    const modal = document.createElement('div');
    modal.className = 'modal modal--active';
    modal.innerHTML = `
      <div class="modal__overlay"></div>
      <div class="modal__content">
        <div class="modal__header">
          <h3 class="modal__title">${title}</h3>
          <button class="modal__close" data-action="cancel">&times;</button>
        </div>
        <div class="modal__body">${body}</div>
        <div class="modal__footer">
          <button class="btn btn--secondary" data-action="cancel">${cancelText}</button>
          <button class="btn btn--primary" data-action="confirm">${confirmText}</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    modal.addEventListener('click', (e) => {
      const action = e.target.dataset.action || e.target.closest('[data-action]')?.dataset.action;
      if (action === 'confirm') {
        resolve(true);
        modal.remove();
      } else if (action === 'cancel' || e.target.classList.contains('modal__overlay')) {
        resolve(false);
        modal.remove();
      }
    });
  });
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} Is valid email
 */
export function isValidEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
}

/**
 * Sanitize HTML to prevent XSS
 * @param {string} html - HTML string to sanitize
 * @returns {string} Sanitized string
 */
export function sanitizeHTML(html) {
  const div = document.createElement('div');
  div.textContent = html;
  return div.innerHTML;
}

/**
 * Get query parameter from URL
 * @param {string} param - Parameter name
 * @returns {string|null} Parameter value
 */
export function getQueryParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(param);
}

/**
 * Generate unique ID
 * @returns {string} Unique ID
 */
export function generateId() {
  return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

/**
 * Deep clone object
 * @param {Object} obj - Object to clone
 * @returns {Object} Cloned object
 */
export function deepClone(obj) {
  return JSON.parse(JSON.stringify(obj));
}

/**
 * Check if user is on mobile device
 * @returns {boolean} Is mobile
 */
export function isMobile() {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Capitalize first letter
 * @param {string} string - String to capitalize
 * @returns {string} Capitalized string
 */
export function capitalize(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

/**
 * Truncate string
 * @param {string} string - String to truncate
 * @param {number} length - Max length
 * @returns {string} Truncated string
 */
export function truncate(string, length = 50) {
  if (string.length <= length) return string;
  return string.slice(0, length) + '...';
}

// Export all as default object as well
export default {
  formatCurrency,
  formatDate,
  formatDateTime,
  debounce,
  showToast,
  showLoading,
  hideLoading,
  confirmAction,
  showModal,
  isValidEmail,
  sanitizeHTML,
  getQueryParam,
  generateId,
  deepClone,
  isMobile,
  capitalize,
  truncate
};

