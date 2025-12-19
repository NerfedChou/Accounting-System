/**
 * Accounting System - Shared API Client
 * Centralizes API calls with authentication handling
 */

class ApiClient {
    constructor() {
        this.baseUrl = '/api/v1';
    }

    getToken() {
        return localStorage.getItem('auth_token');
    }

    getCompanyId() {
        return localStorage.getItem('company_id') || '1';
    }

    async get(endpoint) {
        return this.request('GET', endpoint);
    }

    async post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }

    async put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    async delete(endpoint) {
        return this.request('DELETE', endpoint);
    }

    async request(method, endpoint, data = null) {
        const token = this.getToken();

        if (!token) {
            this.redirectToLogin();
            return null;
        }

        const options = {
            method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, options);

            // Handle authentication errors
            if (response.status === 401) {
                this.redirectToLogin();
                return null;
            }

            const result = await response.json();

            if (!response.ok) {
                throw new ApiError(
                    result.error?.message || result.error || 'Request failed',
                    response.status,
                    result
                );
            }

            return result;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError('Network error', 0, { originalError: error.message });
        }
    }

    redirectToLogin() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_expires');
        localStorage.removeItem('user_id');
        window.location.href = '/login.html';
    }

    // ========== Dashboard APIs ==========

    async getMe() {
        return this.get('/auth/me');
    }

    async getDashboardStats() {
        // Use dedicated backend endpoint for system-wide stats
        try {
            const result = await this.get('/dashboard/stats');
            return {
                transactionCount: result?.data?.todays_transactions || 0,
                pendingApprovals: result?.data?.pending_approvals || 0,
                accountCount: result?.data?.gl_accounts || 0,
                activeSessions: result?.data?.active_sessions || 1
            };
        } catch (error) {
            console.error('Failed to fetch dashboard stats:', error);
            return {
                transactionCount: 0,
                pendingApprovals: 0,
                accountCount: 0,
                activeSessions: 1
            };
        }
    }

    // ========== Company APIs ==========

    async getCompanies() {
        return this.get('/companies');
    }

    async getCompany(id) {
        return this.get(`/companies/${id}`);
    }

    // ========== Transaction APIs ==========

    async getTransactions(page = 1, limit = 20, companyId = null, status = 'all') {
        const cid = companyId || this.getCompanyId();
        let url = `/companies/${cid}/transactions?page=${page}&limit=${limit}`;
        if (status && status !== 'all') {
            url += `&status=${encodeURIComponent(status)}`;
        }
        return this.get(url);
    }

    async getTransaction(id, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.get(`/companies/${cid}/transactions/${id}`);
    }

    async createTransaction(data, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/transactions`, data);
    }

    async postTransaction(id, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/transactions/${id}/post`);
    }

    async voidTransaction(id, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/transactions/${id}/void`);
    }

    // ========== Approval APIs ==========

    async getPendingApprovals(page = 1, limit = 20, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.get(`/companies/${cid}/approvals/pending?page=${page}&limit=${limit}`);
    }

    async approveRequest(id, reason = '', companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/approvals/${id}/approve`, { reason });
    }

    async rejectRequest(id, reason = '', companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/approvals/${id}/reject`, { reason });
    }

    // ========== Account APIs ==========

    async getAccounts(companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.get(`/companies/${cid}/accounts`);
    }

    async getAccount(id, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.get(`/companies/${cid}/accounts/${id}`);
    }

    async createAccount(data, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/accounts`, data);
    }

    async updateAccount(id, data, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.put(`/companies/${cid}/accounts/${id}`, data);
    }

    async toggleAccount(id, companyId = null) {
        const cid = companyId || this.getCompanyId();
        return this.post(`/companies/${cid}/accounts/${id}/toggle`);
    }

    async getAccountTransactions(companyId, accountId) {
        const cid = companyId || this.getCompanyId();
        const result = await this.get(`/companies/${cid}/accounts/${accountId}/transactions`);
        return result?.data || [];
    }

    // ========== Report APIs ==========

    async getReports() {
        const companyId = this.getCompanyId();
        return this.get(`/companies/${companyId}/reports`);
    }

    async generateReport(reportType, periodStart, periodEnd) {
        const companyId = this.getCompanyId();
        return this.post(`/companies/${companyId}/reports/generate`, {
            report_type: reportType,
            period_start: periodStart,
            period_end: periodEnd
        });
    }
}

/**
 * Custom API Error class
 */
class ApiError extends Error {
    constructor(message, status, data) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
}

// Export singleton instance
const api = new ApiClient();
