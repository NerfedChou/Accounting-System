/**
 * Accounts Page
 * Accounting System
 */

import api from '/shared/js/api.js';
import { formatCurrency } from '/shared/js/utils.js';
import session from '/shared/js/session.js';

class AccountsManager {
    constructor() {
        this.init();
    }

    async init() {
        await session.requireTenant();
        await this.loadAccounts();
    }

    async loadAccounts() {
        try {
            const response = await api.get('accounts/list.php');
            // FILTER OUT EXTERNAL ACCOUNTS - they are not part of the company's accounts
            const internalAccounts = (response.data || []).filter(account => !account.is_system_account);
            this.renderAccounts(internalAccounts);
        } catch (error) {
            document.getElementById('accountsContainer').innerHTML = `
                <div class="list__item list__item--empty">
                    <p>No accounts found or unable to load accounts.</p>
                </div>
            `;
        }
    }

    renderAccounts(accounts) {
        const container = document.getElementById('accountsContainer');

        if (accounts.length === 0) {
            container.innerHTML = `
                <div class="list__item list__item--empty">
                    <p>No accounts found. Create your first account via <a href="/tenant/transactions.html">Transactions</a>.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = accounts.map(account => `
            <div class="list__item">
                <div class="list__cell" style="flex: 0 0 100px;">
                    <strong>${account.account_code}</strong>
                </div>
                <div class="list__cell" style="flex: 2;">
                    ${account.account_name}
                </div>
                <div class="list__cell" style="flex: 1;">
                    <span class="badge badge--info">${account.type_name}</span>
                </div>
                <div class="list__cell" style="flex: 0 0 150px; text-align: right;">
                    <strong>${formatCurrency(account.current_balance)}</strong>
                </div>
            </div>
        `).join('');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new AccountsManager();
});

