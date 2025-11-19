/**
 * API Wrapper - Centralized API communication
 * Accounting System
 */

import { showToast, showLoading, hideLoading } from './utils.js';

class API {
  constructor() {
    this.baseURL = '/php/api';
    this.headers = {
      'Content-Type': 'application/json'
    };
  }

  /**
   * GET request
   * @param {string} endpoint - API endpoint
   * @param {Object} params - Query parameters
   * @param {boolean} showLoader - Show loading indicator
   * @returns {Promise} Response data
   */
  async get(endpoint, params = {}, showLoader = false) {
    try {
      if (showLoader) showLoading();

      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `${this.baseURL}/${endpoint}?${queryString}` : `${this.baseURL}/${endpoint}`;

      const response = await fetch(url, {
        method: 'GET',
        headers: this.headers,
        credentials: 'same-origin'
      });

      return await this.handleResponse(response);
    } catch (error) {
      this.handleError(error);
      throw error;
    } finally {
      if (showLoader) hideLoading();
    }
  }

  /**
   * POST request
   * @param {string} endpoint - API endpoint
   * @param {Object} data - Request body
   * @param {boolean} showLoader - Show loading indicator
   * @returns {Promise} Response data
   */
  async post(endpoint, data = {}, showLoader = true) {
    try {
      if (showLoader) showLoading();

      const response = await fetch(`${this.baseURL}/${endpoint}`, {
        method: 'POST',
        headers: this.headers,
        credentials: 'same-origin',
        body: JSON.stringify(data)
      });

      return await this.handleResponse(response);
    } catch (error) {
      this.handleError(error);
      throw error;
    } finally {
      if (showLoader) hideLoading();
    }
  }

  /**
   * PUT request
   * @param {string} endpoint - API endpoint
   * @param {Object} data - Request body
   * @param {boolean} showLoader - Show loading indicator
   * @returns {Promise} Response data
   */
  async put(endpoint, data = {}, showLoader = true) {
    try {
      if (showLoader) showLoading();

      const response = await fetch(`${this.baseURL}/${endpoint}`, {
        method: 'PUT',
        headers: this.headers,
        credentials: 'same-origin',
        body: JSON.stringify(data)
      });

      return await this.handleResponse(response);
    } catch (error) {
      this.handleError(error);
      throw error;
    } finally {
      if (showLoader) hideLoading();
    }
  }

  /**
   * DELETE request
   * @param {string} endpoint - API endpoint
   * @param {boolean} showLoader - Show loading indicator
   * @returns {Promise} Response data
   */
  async delete(endpoint, showLoader = true) {
    try {
      if (showLoader) showLoading();

      const response = await fetch(`${this.baseURL}/${endpoint}`, {
        method: 'DELETE',
        headers: this.headers,
        credentials: 'same-origin'
      });

      return await this.handleResponse(response);
    } catch (error) {
      this.handleError(error);
      throw error;
    } finally {
      if (showLoader) hideLoading();
    }
  }

  /**
   * Handle API response
   * @param {Response} response - Fetch response
   * @returns {Promise} Parsed response
   */
  async handleResponse(response) {
    const contentType = response.headers.get('content-type');

    // Parse JSON response
    if (contentType && contentType.includes('application/json')) {
      const data = await response.json();

      // 401 is expected for session checks when not logged in - return data without throwing
      if (response.status === 401) {
        return data;
      }

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      // Show success message if provided
      if (data.message && response.status === 200) {
        showToast(data.message, 'success');
      }

      return data;
    }

    // Non-JSON response
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return response.text();
  }

  /**
   * Handle API errors
   * @param {Error} error - Error object
   */
  handleError(error) {
    console.error('API Error:', error);

    // Show user-friendly error message
    let message = 'An error occurred. Please try again.';

    if (error.message) {
      message = error.message;
    }

    if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
      message = 'Network error. Please check your connection.';
    }

    if (error.message.includes('401')) {
      message = 'Unauthorized. Please log in again.';
      // Redirect to login after showing error
      setTimeout(() => {
        window.location.href = '/tenant/login.html';
      }, 2000);
    }

    if (error.message.includes('403')) {
      message = 'Access denied. You do not have permission.';
    }

    showToast(message, 'error', 5000);
  }
}

// Create singleton instance
const api = new API();

export default api;
export { API };

