/**
 * Admin Dashboard Page
 * Accounting System
 *
 * Aligned with:
 * - Reference.md: Admin manages companies and tenants
 * - DATABASE.md: Admin has company_id = NULL, can view all data
 * - USE-CASE-DIAGRAM.md: UC-A04 View All Company Data
 */

import api from '/shared/js/api.js';
import { showToast } from '/shared/js/utils.js';
import session from '/shared/js/session.js';

class AdminDashboard {
    constructor() {
        this.init();
    }

    async init() {
        // Check admin session
        await session.requireAdmin();

        // Update header with user info
        this.updateHeader();

        // Setup logout handler
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await session.logout();
        });

        try {
            await this.loadDashboardData();
        } catch (error) {
            showToast('Error loading dashboard data', 'error');
            console.error('Dashboard error:', error);
        }
    }

    /**
     * Update header with user info
     */
    updateHeader() {
        const user = session.getUser();
        if (user) {
            document.getElementById('username').textContent = user.full_name;
        }
    }

    /**
     * Load admin dashboard statistics
     */
    async loadDashboardData() {
        try {
            const [stats, recentCompanies] = await Promise.all([
                this.fetchStats(),
                this.fetchRecentCompanies()
            ]);

            this.displayStats(stats);
            this.renderRecentCompanies(recentCompanies);

        } catch (error) {
            throw error;
        }
    }

    /**
     * Fetch admin statistics
     */
    async fetchStats() {
        const response = await api.get('admin/stats.php');
        return response.data || {
            total_companies: 0,
            total_tenants: 0,
            total_transactions: 0
        };
    }

    /**
     * Fetch recent companies
     */
    async fetchRecentCompanies() {
        const response = await api.get('companies/list.php', { limit: 10 });
        return response.data || [];
    }

    /**
     * Display statistics
     */
    displayStats(stats) {
        document.getElementById('totalCompanies').textContent = stats.total_companies;
        document.getElementById('totalTenants').textContent = stats.total_tenants;
        document.getElementById('totalTransactions').textContent = stats.total_transactions;
    }

    /**
     * Render recent companies list
     */
    renderRecentCompanies(companies) {
        const container = document.getElementById('companiesContainer');

        if (companies.length === 0) {
            container.innerHTML = `
                <div class="list__item list__item--empty">
                    <p>No companies found. <a href="/admin/companies.html">Create your first company</a></p>
                </div>
            `;
            return;
        }

        container.innerHTML = companies.map(company => `
            <div class="list__item" onclick="window.location.href='/admin/companies.html?id=${company.id}'">
                <div class="list__cell" style="flex: 2;">
                    <strong>${company.company_name}</strong>
                </div>
                <div class="list__cell" style="flex: 1;">
                    ${company.email || '-'}
                </div>
                <div class="list__cell" style="flex: 1;">
                    ${company.phone || '-'}
                </div>
                <div class="list__cell" style="flex: 0 0 100px; text-align: center;">
                    <span class="badge ${company.is_active ? 'badge--success' : 'badge--danger'}">
                        ${company.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
            </div>
        `).join('');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new AdminDashboard();
});

