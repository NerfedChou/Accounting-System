/**
 * Tenant Dashboard Page
 * Accounting System
 *
 * Aligned with:
 * - Reference.md: 4 charts (Chart.js), track assets/liabilities/equity/revenue/expenses, modern list
 * - DATABASE.md: account_types (1-5), transaction_statuses, company_id filtering
 * - USE-CASE-DIAGRAM.md: UC-T01 View Dashboard
 * - FLOWCHART.md: FC-4 Dashboard Loading (parallel API calls, Chart.js init, render transactions)
 * - QUERIES.md: Query 7.1-7.4 (dashboard metrics), Query 8.1 (recent transactions)
 */

import api from '/shared/js/api.js';
import { formatCurrency, formatDate, showToast } from '/shared/js/utils.js';
import session from '/shared/js/session.js';

class TenantDashboard {
    constructor() {
        this.charts = {
            assets: null,
            liabilities: null,
            equity: null,
            revenueExpenses: null
        };

        this.init();
    }

    async init() {
        console.log('[DASHBOARD] Starting initialization...');

        // FLOWCHART FC-4: Check Session (must be tenant)
        console.log('[DASHBOARD] Calling session.requireTenant()...');
        await session.requireTenant();
        console.log('[DASHBOARD] ✅ Session check passed, user is authenticated tenant');

        // Update header with user info
        console.log('[DASHBOARD] Updating header...');
        this.updateHeader();

        // Setup logout handler
        console.log('[DASHBOARD] Setting up logout handler...');
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            console.log('[DASHBOARD] Logout clicked');
            await session.logout();
        });

        // FLOWCHART FC-4: Initialize Dashboard, Show Loading
        console.log('[DASHBOARD] Starting data load...');
        this.showLoading();

        try {
            // FLOWCHART FC-4: PARALLEL API CALLS (Promise.all)
            await this.loadDashboardData();
            console.log('[DASHBOARD] ✅ Dashboard loaded successfully');
        } catch (error) {
            console.error('[DASHBOARD] ❌ Error loading dashboard data:', error);
            showToast('Error loading dashboard data', 'error');
        }
    }

    /**
     * Update header with user and company info
     */
    updateHeader() {
        const user = session.getUser();
        const company = session.getCompany();

        if (user) {
            document.getElementById('username').textContent = user.full_name;
        }

        if (company) {
            document.getElementById('companyName').textContent = company.company_name;
        }
    }

    /**
     * Load all dashboard data in parallel
     * Aligned with: FLOWCHART FC-4 (Parallel API Calls using Promise.all)
     */
    async loadDashboardData() {
        try {
            const [assetsData, liabilitiesData, equityData, revenueExpensesData, recentTransactions] = await Promise.all([
                this.fetchAssets(),        // Query 7.1
                this.fetchLiabilities(),   // Query 7.2
                this.fetchEquity(),        // Query 7.3
                this.fetchRevenueExpenses(), // Query 7.4
                this.fetchRecentTransactions() // Query 8.1
            ]);

            // FLOWCHART FC-4: Hide Loading Spinner
            this.hideLoading();

            // FLOWCHART FC-4: Initialize Chart.js
            this.initCharts(assetsData, liabilitiesData, equityData, revenueExpensesData);

            // FLOWCHART FC-4: Render Recent Transactions
            this.renderRecentTransactions(recentTransactions);

        } catch (error) {
            this.hideLoading();
            throw error;
        }
    }

    /**
     * Fetch Total Assets by Account
     * Aligned with: QUERIES.md Query 7.1
     */
    async fetchAssets() {
        const response = await api.get('dashboard/assets.php');
        return response.data || [];
    }

    /**
     * Fetch Total Liabilities by Account
     * Aligned with: QUERIES.md Query 7.2
     */
    async fetchLiabilities() {
        const response = await api.get('dashboard/liabilities.php');
        return response.data || [];
    }

    /**
     * Fetch Total Equity
     * Aligned with: QUERIES.md Query 7.3
     */
    async fetchEquity() {
        const response = await api.get('dashboard/equity.php');
        return response.data || [];
    }

    /**
     * Fetch Revenue vs Expenses Monthly Trend
     * Aligned with: QUERIES.md Query 7.4
     */
    async fetchRevenueExpenses() {
        const response = await api.get('dashboard/revenue-expenses.php');
        return response.data || [];
    }

    /**
     * Fetch Recent Transactions
     * Aligned with: QUERIES.md Query 8.1
     */
    async fetchRecentTransactions() {
        const response = await api.get('dashboard/recent-transactions.php');
        return response.data || [];
    }

    /**
     * Initialize all 4 Chart.js charts
     * Aligned with: FLOWCHART FC-4 (Initialize Chart.js section)
     */
    initCharts(assetsData, liabilitiesData, equityData, revenueExpensesData) {
        // Chart 1: Total Assets (Doughnut)
        this.charts.assets = this.createDoughnutChart(
            'assetsChart',
            'totalAssets',
            assetsData,
            'Assets',
            '#3b82f6' // Blue for assets
        );

        // Chart 2: Total Liabilities (Doughnut)
        this.charts.liabilities = this.createDoughnutChart(
            'liabilitiesChart',
            'totalLiabilities',
            liabilitiesData,
            'Liabilities',
            '#ef4444' // Red for liabilities
        );

        // Chart 3: Total Equity (Doughnut)
        this.charts.equity = this.createDoughnutChart(
            'equityChart',
            'totalEquity',
            equityData,
            'Equity',
            '#8b5cf6' // Purple for equity
        );

        // Chart 4: Revenue vs Expenses (Line Chart)
        this.charts.revenueExpenses = this.createRevenueExpensesChart(revenueExpensesData);
    }

    /**
     * Create Doughnut Chart
     * Aligned with: FLOWCHART FC-4 (Chart 1, 2, 3 - Type: Doughnut)
     */
    createDoughnutChart(canvasId, totalElementId, data, label, baseColor) {
        const canvas = document.getElementById(canvasId);
        const ctx = canvas.getContext('2d');

        // Calculate total
        const total = data.reduce((sum, item) => sum + parseFloat(item.amount), 0);
        document.getElementById(totalElementId).textContent = formatCurrency(total);
        console.log(total);
        // Handle empty data
        if (data.length === 0) {
            canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No data available</p>';
            return null;
        }

        // Generate colors
        const colors = this.generateColors(baseColor, data.length);

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.account_name),
                datasets: [{
                    label: label,
                    data: data.map(item => parseFloat(item.amount)),
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = formatCurrency(context.parsed);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Create Revenue vs Expenses Line Chart
     * Aligned with: FLOWCHART FC-4 (Chart 4 - Type: Line, X-axis: Months, Y-axis: Amounts)
     * Aligned with: QUERIES.md Query 7.4 (Revenue vs Expenses Monthly Trend)
     */
    createRevenueExpensesChart(data) {
        const canvas = document.getElementById('revenueExpensesChart');
        const ctx = canvas.getContext('2d');

        // Handle empty data
        if (data.length === 0) {
            canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No data available</p>';
            return null;
        }

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.month), // X-axis: Months
                datasets: [
                    {
                        label: 'Revenue',
                        data: data.map(item => parseFloat(item.revenue)), // Y-axis: Revenue amounts
                        borderColor: '#10b981', // Green (var(--color-revenue))
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: data.map(item => parseFloat(item.expenses)), // Y-axis: Expense amounts
                        borderColor: '#f59e0b', // Orange (var(--color-expense))
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false // Legend shown in HTML
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Render Recent Transactions (Modern list, NOT HTML table)
     * Aligned with: FLOWCHART FC-4 (Render Recent Transactions section)
     * Aligned with: QUERIES.md Query 8.1 (Recent Transactions for Dashboard)
     * Aligned with: Reference.md ("Dont use table because thats too old we need modern one")
     */
    renderRecentTransactions(transactions) {
        const container = document.getElementById('transactionsContainer');

        if (transactions.length === 0) {
            container.innerHTML = `
                <div class="list__item list__item--empty">
                    <p>No recent transactions found.</p>
                </div>
            `;
            return;
        }

        // FLOWCHART FC-4: Modern card/list layout with status badges (color coded)
        container.innerHTML = transactions.map(t => `
            <div class="list__item" onclick="window.location.href='/tenant/transactions.html?id=${t.id}'">
                <div class="list__cell" style="flex: 0 0 120px;">
                    <strong>${t.transaction_number}</strong>
                </div>
                <div class="list__cell" style="flex: 0 0 100px;">
                    ${formatDate(t.transaction_date)}
                </div>
                <div class="list__cell" style="flex: 2;">
                    ${t.description || 'No description'}
                </div>
                <div class="list__cell" style="flex: 0 0 120px; text-align: right;">
                    <strong>${formatCurrency(t.total_amount)}</strong>
                </div>
                <div class="list__cell" style="flex: 0 0 100px; text-align: center;">
                    <span class="badge badge--${t.status_class}">${t.status_name}</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Generate color shades for charts
     */
    generateColors(baseColor, count) {
        const colors = [];
        const hsl = this.hexToHSL(baseColor);

        for (let i = 0; i < count; i++) {
            const lightness = 40 + (i * (60 - 40) / count);
            colors.push(`hsl(${hsl.h}, ${hsl.s}%, ${lightness}%)`);
        }

        return colors;
    }

    /**
     * Convert hex to HSL
     */
    hexToHSL(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        let r = parseInt(result[1], 16) / 255;
        let g = parseInt(result[2], 16) / 255;
        let b = parseInt(result[3], 16) / 255;

        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;

        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                case g: h = ((b - r) / d + 2) / 6; break;
                case b: h = ((r - g) / d + 4) / 6; break;
            }
        }

        return {
            h: Math.round(h * 360),
            s: Math.round(s * 100),
            l: Math.round(l * 100)
        };
    }

    /**
     * Show loading state
     */
    showLoading() {
        // Already shown in HTML
    }

    /**
     * Hide loading state
     */
    hideLoading() {
        const loadingElement = document.querySelector('.list__item--loading');
        if (loadingElement) {
            loadingElement.remove();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new TenantDashboard();
});

