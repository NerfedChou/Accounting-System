/**
 * Tenant Login Page
 * Accounting System
 *
 * Aligned with:
 * - Reference.md: Tenant login on normal dashboard, simple & clean
 * - DATABASE.md: users table (username, password, role='tenant', company_id)
 * - FLOWCHART.md: FC-3 Login & Authentication flow
 * - USE-CASE-DIAGRAM.md: UC-001 Login to System
 * - Backend: /php/api/auth/login.php
 */

import api from '/shared/js/api.js';
import { validateRequired, displayFormErrors, clearFormErrors } from '/shared/js/validation.js';
import { showToast } from '/shared/js/utils.js';
import session from '/shared/js/session.js';

class TenantLogin {
    constructor() {
        this.form = document.getElementById('loginForm');
        this.usernameInput = document.getElementById('username');
        this.passwordInput = document.getElementById('password');
        this.loginBtn = document.getElementById('loginBtn');
        this.btnText = document.getElementById('btnText');
        this.btnLoader = document.getElementById('btnLoader');

        this.init();
    }

    init() {
        // Check if already logged in
        this.checkExistingSession();

        // Attach event listeners
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Clear errors on input
        this.usernameInput.addEventListener('input', () => clearFormErrors());
        this.passwordInput.addEventListener('input', () => clearFormErrors());
    }

    /**
     * Check if user is already logged in
     * Aligned with: FLOWCHART.md FC-3 (session check)
     */
    async checkExistingSession() {
        try {
            await session.init();

            if (session.isAuthenticated()) {
                // Already logged in, redirect to appropriate dashboard
                if (session.isTenant()) {
                    window.location.href = '/tenant/dashboard.html';
                } else {
                    // Logged in as admin, redirect to admin dashboard
                    window.location.href = '/admin/dashboard.html';
                }
            }
        } catch (error) {
            // Not logged in, stay on login page
            console.log('No active session');
        }
    }

    /**
     * Handle form submission
     * Aligned with: FLOWCHART.md FC-3 Login flow
     */
    async handleSubmit(e) {
        e.preventDefault();
        clearFormErrors();

        // Get form data
        const username = this.usernameInput.value.trim();
        const password = this.passwordInput.value;

        // Validate inputs (FLOWCHART Step: Validate Input)
        const errors = this.validateForm(username, password);

        if (Object.keys(errors).length > 0) {
            displayFormErrors(errors);
            showToast('Please fill in all required fields', 'error');
            return;
        }

        // Show loading state
        this.setLoading(true);

        try {
            // Send to server API: /api/auth/login.php
            // Aligned with: Backend php/api/auth/login.php
            // Database query: SELECT * FROM users WHERE username = ? AND is_active = TRUE
            const response = await api.post('auth/login.php', {
                username: username,
                password: password
            }, false); // Don't show default loader

            if (response.success && response.data) {
                const { user, company } = response.data;

                // Check if user is tenant (FLOWCHART: Check User Role)
                if (user.role !== 'tenant') {
                    showToast('Access denied. Please use admin portal.', 'error');
                    this.setLoading(false);
                    return;
                }

                // Tenant must have company_id (DATABASE.md: company_id NOT NULL for tenants)
                if (!user.company_id) {
                    showToast('Error: No company associated with this account', 'error');
                    this.setLoading(false);
                    return;
                }

                // Success! Update session
                session.updateUser(user);
                session.updateCompany(company);

                showToast('Login successful! Redirecting...', 'success');

                // Redirect to tenant dashboard (FLOWCHART: Redirect Based on Role)
                setTimeout(() => {
                    window.location.href = '/tenant/dashboard.html';
                }, 1000);

            } else {
                showToast(response.message || 'Login failed', 'error');
                this.setLoading(false);
            }

        } catch (error) {
            // FLOWCHART: Authentication Failed
            showToast('Invalid username or password', 'error');
            this.setLoading(false);
        }
    }

    /**
     * Validate form inputs
     * Aligned with: FLOWCHART.md (Validate Input step)
     */
    validateForm(username, password) {
        const errors = {};

        // Validate username
        const usernameValidation = validateRequired(username);
        if (!usernameValidation.isValid) {
            errors.username = usernameValidation.message;
        }

        // Validate password
        const passwordValidation = validateRequired(password);
        if (!passwordValidation.isValid) {
            errors.password = passwordValidation.message;
        }

        return errors;
    }

    /**
     * Set loading state
     */
    setLoading(isLoading) {
        if (isLoading) {
            this.loginBtn.disabled = true;
            this.btnText.textContent = 'Logging in...';
            this.btnLoader.classList.remove('hidden');
        } else {
            this.loginBtn.disabled = false;
            this.btnText.textContent = 'Login';
            this.btnLoader.classList.add('hidden');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new TenantLogin();
});

