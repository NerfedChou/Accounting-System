/**
 * Accounting System - Login Flow Controller
 * Username-first flow with Password + OTP authentication
 */

class LoginManager {
    constructor() {
        this.currentStep = 1;
        this.username = '';
        this.apiBase = '/api/v1/auth';

        this.views = {
            username: document.getElementById('view-username'),
            auth: document.getElementById('view-auth')
        };

        this.elements = {
            stepIndicator: document.getElementById('stepIndicator'),
            usernameForm: document.getElementById('usernameForm'),
            usernameError: document.getElementById('usernameError'),
            usernameInput: document.getElementById('username'),
            btnUsername: document.getElementById('btnUsername'),
            authForm: document.getElementById('authForm'),
            authError: document.getElementById('authError'),
            authSubtitle: document.getElementById('authSubtitle'),
            password: document.getElementById('password'),
            otpCode: document.getElementById('otpCode'),
            btnBack: document.getElementById('btnBack'),
            btnLogin: document.getElementById('btnLogin')
        };

        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        this.elements.usernameForm.addEventListener('submit', (e) => this.handleUsername(e));
        this.elements.authForm.addEventListener('submit', (e) => this.handleAuth(e));
        this.elements.btnBack.addEventListener('click', () => this.goToStep(1));
    }

    handleUsername(event) {
        event.preventDefault();

        this.username = this.elements.usernameInput.value.trim();

        if (!this.username) {
            this.showError('username', 'Username is required');
            return;
        }

        // Move to auth step
        this.elements.authSubtitle.textContent = `Welcome, ${this.username}`;
        this.goToStep(2);
        this.elements.password.focus();
    }

    async handleAuth(event) {
        event.preventDefault();

        const password = this.elements.password.value;
        const otpCode = this.elements.otpCode.value;

        this.elements.btnLogin.disabled = true;
        this.elements.btnLogin.innerHTML = '<span class="spinner"></span> Logging in...';
        this.hideError('auth');

        try {
            const response = await fetch(`${this.apiBase}/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: this.username,
                    password: password,
                    otp_code: otpCode
                })
            });

            const data = await response.json();

            if (!response.ok) {
                const errorMsg = data.error?.message || data.error || 'Authentication failed';
                throw new Error(errorMsg);
            }

            if (data.data?.token) {
                this.handleLoginSuccess(data.data);
            }
        } catch (error) {
            this.showError('auth', error.message);
            this.elements.otpCode.value = '';
            this.elements.otpCode.focus();
        } finally {
            this.elements.btnLogin.disabled = false;
            this.elements.btnLogin.textContent = 'Login';
        }
    }

    handleLoginSuccess(sessionData) {
        // Store token in localStorage
        localStorage.setItem('auth_token', sessionData.token);
        localStorage.setItem('auth_expires', sessionData.expires_at);
        localStorage.setItem('user_id', sessionData.user_id);

        // Redirect to dashboard
        window.location.href = '/dashboard.html';
    }

    showError(view, message) {
        const el = view === 'username' ? this.elements.usernameError : this.elements.authError;
        el.textContent = message;
        el.style.display = 'block';
    }

    hideError(view) {
        const el = view === 'username' ? this.elements.usernameError : this.elements.authError;
        el.style.display = 'none';
    }

    goToStep(step) {
        this.currentStep = step;

        // Update views
        Object.values(this.views).forEach(view => view.classList.remove('active'));

        if (step === 1) {
            this.views.username.classList.add('active');
        } else {
            this.views.auth.classList.add('active');
        }

        // Update step indicator
        const dots = this.elements.stepIndicator.querySelectorAll('.step-dot');
        dots.forEach((dot, index) => {
            dot.classList.remove('active', 'completed');
            if (index + 1 < step) {
                dot.classList.add('completed');
            } else if (index + 1 === step) {
                dot.classList.add('active');
            }
        });
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new LoginManager();
});
