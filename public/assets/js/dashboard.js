/**
 * Dashboard - Control Center Manager
 */

class DashboardManager {
    constructor() {
        this.apiBase = '/api/v1';
        this.init();
    }

    init() {
        this.checkAuth();
        this.startClock();
        this.bindEvents();
    }

    async checkAuth() {
        const token = localStorage.getItem('auth_token');

        if (!token) {
            console.log('No auth token, redirecting to login');
            window.location.href = '/login.html';
            return;
        }

        console.log('Token found:', token.substring(0, 20) + '...');

        // Verify token is still valid
        try {
            const response = await fetch(`${this.apiBase}/auth/me`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                localStorage.removeItem('auth_token');
                window.location.href = '/login.html';
                return;
            }

            const data = await response.json();
            if (data.data?.username) {
                document.getElementById('userName').textContent = data.data.username;
            }
        } catch (error) {
            console.error('Auth check network error:', error);
            // Don't redirect on network error, just continue
        }
    }

    startClock() {
        const updateTime = () => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('systemTime').textContent = timeStr;
        };

        updateTime();
        setInterval(updateTime, 1000);
    }

    bindEvents() {
        document.getElementById('btnLogout').addEventListener('click', () => this.logout());
    }

    async logout() {
        const token = localStorage.getItem('auth_token');

        try {
            await fetch(`${this.apiBase}/auth/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
        } catch (error) {
            console.error('Logout error:', error);
        }

        localStorage.removeItem('auth_token');
        window.location.href = '/login.html';
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new DashboardManager();
});
