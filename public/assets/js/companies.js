/**
 * Companies Page JavaScript
 * Handles company list, creation, and management
 */

(function () {
    'use strict';

    // State
    let companies = [];
    let currentFilter = '';

    // DOM Elements
    const elements = {
        companiesBody: document.getElementById('companiesBody'),
        companyCount: document.getElementById('companyCount'),
        filterStatus: document.getElementById('filterStatus'),
        btnRefresh: document.getElementById('btnRefresh'),
        btnNewCompany: document.getElementById('btnNewCompany'),
        btnCreateFirst: document.getElementById('btnCreateFirst'),
        emptyState: document.getElementById('emptyState'),
        companyModal: document.getElementById('companyModal'),
        companyForm: document.getElementById('companyForm'),
        btnCloseModal: document.getElementById('btnCloseModal'),
        btnCancelModal: document.getElementById('btnCancelModal'),
        btnSubmit: document.getElementById('btnSubmit'),
        btnLogout: document.getElementById('btnLogout'),
        userName: document.getElementById('userName'),
        // Form fields
        companyName: document.getElementById('companyName'),
        legalName: document.getElementById('legalName'),
        taxId: document.getElementById('taxId'),
        currency: document.getElementById('currency'),
        street: document.getElementById('street'),
        city: document.getElementById('city'),
        state: document.getElementById('state'),
        postalCode: document.getElementById('postalCode'),
        country: document.getElementById('country'),
    };

    // Initialize
    async function init() {
        await loadUserInfo();
        await loadCompanies();
        setupEventListeners();
    }

    // Load user info
    async function loadUserInfo() {
        try {
            const user = await api.get('/auth/me');
            if (user.data) {
                elements.userName.textContent = user.data.username || 'User';
            }
        } catch (error) {
            console.error('Failed to load user info:', error);
        }
    }

    // Load companies from API
    async function loadCompanies() {
        showLoading();

        try {
            const response = await api.get('/companies');
            companies = response.data || [];
            renderCompanies();
        } catch (error) {
            console.error('Failed to load companies:', error);
            showError('Failed to load companies. Please try again.');
        }
    }

    // Render companies table
    function renderCompanies() {
        const filtered = filterCompanies(companies);

        if (filtered.length === 0) {
            showEmptyState();
            updateCompanyCount(0);
            return;
        }

        hideEmptyState();
        updateCompanyCount(filtered.length);

        elements.companiesBody.innerHTML = filtered.map(company => `
            <tr>
                <td><span class="company-name">${escapeHtml(company.name)}</span></td>
                <td>${escapeHtml(company.legal_name)}</td>
                <td><code>${escapeHtml(company.tax_id)}</code></td>
                <td><span class="currency-badge">${company.currency}</span></td>
                <td><span class="status-badge status-${company.status}">${capitalizeFirst(company.status)}</span></td>
                <td>${formatDate(company.created_at)}</td>
                <td>
                    <button class="btn-icon" title="View Details" data-id="${company.id}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                            <path d="M8 2c1.981 0 3.671.992 4.933 2.078 1.27 1.091 2.187 2.345 2.637 3.023a1.62 1.62 0 0 1 0 1.798c-.45.678-1.367 1.932-2.637 3.023C11.67 13.008 9.981 14 8 14c-1.981 0-3.671-.992-4.933-2.078C1.797 10.83.88 9.576.43 8.898a1.62 1.62 0 0 1 0-1.798c.45-.677 1.367-1.931 2.637-3.022C4.33 2.992 6.019 2 8 2ZM1.679 7.932a.12.12 0 0 0 0 .136c.411.622 1.241 1.75 2.366 2.717C5.176 11.758 6.527 12.5 8 12.5c1.473 0 2.825-.742 3.955-1.715 1.124-.967 1.954-2.096 2.366-2.717a.12.12 0 0 0 0-.136c-.412-.621-1.242-1.75-2.366-2.717C10.824 4.242 9.473 3.5 8 3.5c-1.473 0-2.824.742-3.955 1.715-1.124.967-1.954 2.096-2.366 2.717ZM8 10a2 2 0 1 1-.001-3.999A2 2 0 0 1 8 10Z"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Filter companies
    function filterCompanies(companies) {
        if (!currentFilter) return companies;
        return companies.filter(c => c.status === currentFilter);
    }

    // Show loading state
    function showLoading() {
        elements.companiesBody.innerHTML = `
            <tr>
                <td colspan="7">
                    <div class="loading-spinner">Loading companies...</div>
                </td>
            </tr>
        `;
    }

    // Show error state
    function showError(message) {
        elements.companiesBody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; color: var(--danger);">
                    ${message}
                </td>
            </tr>
        `;
    }

    // Show empty state
    function showEmptyState() {
        document.querySelector('.panel').style.display = 'none';
        elements.emptyState.style.display = 'flex';
    }

    // Hide empty state
    function hideEmptyState() {
        document.querySelector('.panel').style.display = 'block';
        elements.emptyState.style.display = 'none';
    }

    // Update company count
    function updateCompanyCount(count) {
        elements.companyCount.textContent = `${count} ${count === 1 ? 'company' : 'companies'}`;
    }

    // Open modal
    function openModal() {
        elements.companyModal.classList.add('active');
        elements.companyForm.reset();
        elements.country.value = 'US'; // Reset default
        elements.companyName.focus();
    }

    // Close modal
    function closeModal() {
        elements.companyModal.classList.remove('active');
        elements.companyForm.reset();
    }

    // Handle form submit
    async function handleSubmit(e) {
        e.preventDefault();

        const name = elements.companyName.value.trim();
        const legalName = elements.legalName.value.trim();
        const taxId = elements.taxId.value.trim();
        const currency = elements.currency.value;

        // Validate required fields
        if (!name || !legalName || !taxId) {
            alert('Please fill in all required fields.');
            return;
        }

        // Build payload
        const payload = {
            name,
            legal_name: legalName,
            tax_id: taxId,
            currency,
        };

        // Add address if any field is filled
        const street = elements.street.value.trim();
        const city = elements.city.value.trim();
        const state = elements.state.value.trim();
        const postalCode = elements.postalCode.value.trim();
        const country = elements.country.value.trim();

        if (street || city) {
            payload.address = {
                street: street || 'Not Provided',
                city: city || 'Not Provided',
                state: state || null,
                postal_code: postalCode || null,
                country: country || 'US',
            };
        }

        // Disable submit button
        elements.btnSubmit.disabled = true;
        elements.btnSubmit.textContent = 'Creating...';

        try {
            await api.post('/companies', payload);
            closeModal();
            await loadCompanies();
        } catch (error) {
            console.error('Failed to create company:', error);
            alert(error.message || 'Failed to create company. Please try again.');
        } finally {
            elements.btnSubmit.disabled = false;
            elements.btnSubmit.textContent = 'Create Company';
        }
    }

    // Handle logout
    async function handleLogout() {
        try {
            await api.post('/auth/logout');
            window.location.href = '/login.html';
        } catch (error) {
            console.error('Logout failed:', error);
            window.location.href = '/login.html';
        }
    }

    // Setup event listeners
    function setupEventListeners() {
        // New company buttons
        elements.btnNewCompany.addEventListener('click', openModal);
        elements.btnCreateFirst?.addEventListener('click', openModal);

        // Modal controls
        elements.btnCloseModal.addEventListener('click', closeModal);
        elements.btnCancelModal.addEventListener('click', closeModal);
        elements.companyModal.querySelector('.modal-backdrop').addEventListener('click', closeModal);

        // Form submit
        elements.companyForm.addEventListener('submit', handleSubmit);

        // Filter change
        elements.filterStatus.addEventListener('change', (e) => {
            currentFilter = e.target.value;
            renderCompanies();
        });

        // Refresh
        elements.btnRefresh.addEventListener('click', loadCompanies);

        // Logout
        elements.btnLogout.addEventListener('click', handleLogout);

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && elements.companyModal.classList.contains('active')) {
                closeModal();
            }
        });
    }

    // Utility functions
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
