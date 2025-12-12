# Backend TODO: Foundation Structures

> **Date:** 2025-12-13
> **Purpose:** Define ERD, Flowcharts, Use Cases, and Database Tables for Backend Implementation

---

## Table of Contents

1. [Entity Relationship Diagram (ERD)](#1-entity-relationship-diagram-erd)
2. [System Flowcharts](#2-system-flowcharts)
3. [Use Cases](#3-use-cases)
4. [Database Tables](#4-database-tables)
5. [Backend Structure](#5-backend-structure)
6. [Implementation Checklist](#6-implementation-checklist)

---

## 1. Entity Relationship Diagram (ERD)

### Core Entities

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              ENTITY RELATIONSHIPS                            │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│    USERS     │         │  COMPANIES   │         │   ACCOUNTS   │
├──────────────┤         ├──────────────┤         ├──────────────┤
│ id (PK)      │    ┌───▶│ id (PK)      │◀───┐    │ id (PK)      │
│ company_id(FK)────┘    │ company_name │    │    │ company_id(FK)───┐
│ username     │         │ legal_name   │    │    │ account_code │   │
│ email        │         │ tax_id       │    │    │ account_name │   │
│ password_hash│         │ status       │    │    │ account_type │   │
│ role         │         │ currency     │    │    │ normal_balance   │
│ status       │         │ ...          │    │    │ parent_id (FK)───┼──┐
│ ...          │         └──────────────┘    │    │ is_active    │   │  │
└──────────────┘                              │    │ ...          │   │  │
       │                                      │    └──────────────┘   │  │
       │ 1:N                                  │           │           │  │
       ▼                                      │           │ 1:N       │  │
┌──────────────┐                              │           ▼           │  │
│   SESSIONS   │                              │    ┌──────────────┐   │  │
├──────────────┤                              │    │ACCOUNT_BALANCES  │  │
│ id (PK)      │                              │    ├──────────────┤   │  │
│ user_id (FK) │                              │    │ id (PK)      │   │  │
│ token_hash   │                              │    │ account_id(FK)───┘  │
│ ip_address   │                              │    │ company_id(FK)──────┘
│ expires_at   │                              │    │ current_balance│
│ ...          │                              │    │ total_debits │
└──────────────┘                              │    │ total_credits│
                                              │    │ ...          │
                                              │    └──────────────┘
┌──────────────┐         ┌──────────────┐     │
│ TRANSACTIONS │         │  TXN_LINES   │     │
├──────────────┤         ├──────────────┤     │
│ id (PK)      │◀───┐    │ id (PK)      │     │
│ company_id(FK)────┼────│ txn_id (FK)  │     │
│ txn_number   │    │    │ account_id(FK)─────┘
│ txn_date     │    │    │ line_type    │
│ description  │    │    │ amount       │
│ total_amount │    │    │ line_order   │
│ status       │    │    └──────────────┘
│ created_by(FK)    │
│ posted_by (FK)    │
│ ...          │    │
└──────────────┘    │
                    │
┌──────────────┐    │    ┌──────────────┐
│  APPROVALS   │    │    │BALANCE_CHANGES│
├──────────────┤    │    ├──────────────┤
│ id (PK)      │    │    │ id (PK)      │
│ company_id(FK)    │    │ account_id(FK)
│ entity_type  │    │    │ txn_id (FK)──┘
│ entity_id    │────┘    │ line_type    │
│ status       │         │ amount       │
│ reason       │         │ prev_balance │
│ requested_by │         │ new_balance  │
│ reviewed_by  │         │ ...          │
│ ...          │         └──────────────┘
└──────────────┘

┌──────────────┐         ┌──────────────┐
│ACTIVITY_LOGS │         │AUDIT_SNAPSHOTS│
├──────────────┤         ├──────────────┤
│ id (PK)      │         │ id (PK)      │
│ company_id   │         │ company_id(FK)
│ actor_id     │         │ snapshot_type│
│ activity_type│         │ snapshot_date│
│ entity_type  │         │ balance_data │
│ entity_id    │         │ checksum     │
│ prev_state   │         │ ...          │
│ new_state    │         └──────────────┘
│ ip_address   │
│ ...          │
└──────────────┘
```

### Relationship Summary

| Parent | Child | Relationship | FK Column |
|--------|-------|--------------|-----------|
| companies | users | 1:N | users.company_id |
| companies | accounts | 1:N | accounts.company_id |
| companies | transactions | 1:N | transactions.company_id |
| companies | approvals | 1:N | approvals.company_id |
| users | sessions | 1:N | sessions.user_id |
| users | transactions (created) | 1:N | transactions.created_by |
| users | transactions (posted) | 1:N | transactions.posted_by |
| users | approvals (requested) | 1:N | approvals.requested_by |
| users | approvals (reviewed) | 1:N | approvals.reviewed_by |
| accounts | accounts (parent) | 1:N | accounts.parent_account_id |
| accounts | transaction_lines | 1:N | transaction_lines.account_id |
| accounts | account_balances | 1:1 | account_balances.account_id |
| accounts | balance_changes | 1:N | balance_changes.account_id |
| transactions | transaction_lines | 1:N | transaction_lines.transaction_id |
| transactions | balance_changes | 1:N | balance_changes.transaction_id |
| transactions | approvals | 1:1 | approvals.entity_id |

---

## 2. System Flowcharts

### 2.1 User Authentication Flow

```
┌─────────────┐
│   START     │
└──────┬──────┘
       ▼
┌─────────────────┐
│ User submits    │
│ username/password│
└────────┬────────┘
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ User exists?    │───────────▶│ Return 401  │
└────────┬────────┘            │ Unauthorized│
         │ Yes                 └─────────────┘
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ User active?    │───────────▶│ Return 403  │
└────────┬────────┘            │ Account     │
         │ Yes                 │ Inactive    │
         ▼                     └─────────────┘
┌─────────────────┐     No     ┌─────────────┐
│ User approved?  │───────────▶│ Return 403  │
└────────┬────────┘            │ Pending     │
         │ Yes                 │ Approval    │
         ▼                     └─────────────┘
┌─────────────────┐     No     ┌─────────────┐
│ Password valid? │───────────▶│ Log failed  │
└────────┬────────┘            │ attempt     │
         │ Yes                 │ Return 401  │
         ▼                     └─────────────┘
┌─────────────────┐
│ Create session  │
│ Generate JWT    │
└────────┬────────┘
         ▼
┌─────────────────┐
│ Log successful  │
│ authentication  │
└────────┬────────┘
         ▼
┌─────────────────┐
│ Return token    │
│ + user data     │
└────────┬────────┘
         ▼
┌─────────────┐
│    END      │
└─────────────┘
```

### 2.2 Transaction Creation Flow

```
┌─────────────┐
│   START     │
└──────┬──────┘
       ▼
┌─────────────────┐
│ User submits    │
│ transaction data│
└────────┬────────┘
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ User authorized?│───────────▶│ Return 403  │
└────────┬────────┘            └─────────────┘
         │ Yes
         ▼
┌─────────────────┐
│ Generate txn    │
│ number          │
└────────┬────────┘
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ Has ≥2 lines?   │───────────▶│ Return 422  │
│ (1 debit,1 credit)           │ Invalid     │
└────────┬────────┘            └─────────────┘
         │ Yes
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ All amounts     │───────────▶│ Return 422  │
│ positive?       │            │ Invalid     │
└────────┬────────┘            └─────────────┘
         │ Yes
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ Debits = Credits?───────────▶│ Return 422  │
│                 │            │ Unbalanced  │
└────────┬────────┘            └─────────────┘
         │ Yes
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ All accounts    │───────────▶│ Return 422  │
│ valid & active? │            │ Invalid     │
└────────┬────────┘            │ Account     │
         │ Yes                 └─────────────┘
         ▼
┌─────────────────┐
│ Save transaction│
│ status=PENDING  │
└────────┬────────┘
         ▼
┌─────────────────┐
│ Publish event   │
│ TransactionCreated
└────────┬────────┘
         ▼
┌─────────────────┐
│ Return txn data │
│ + 201 Created   │
└────────┬────────┘
         ▼
┌─────────────┐
│    END      │
└─────────────┘
```

### 2.3 Transaction Posting Flow

```
┌─────────────┐
│   START     │
└──────┬──────┘
       ▼
┌─────────────────┐
│ User requests   │
│ post transaction│
└────────┬────────┘
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ Transaction     │───────────▶│ Return 404  │
│ exists?         │            └─────────────┘
└────────┬────────┘
         │ Yes
         ▼
┌─────────────────┐     No     ┌─────────────┐
│ Status=PENDING? │───────────▶│ Return 422  │
│                 │            │ Already     │
└────────┬────────┘            │ Posted/Void │
         │ Yes                 └─────────────┘
         ▼
┌─────────────────┐
│ Calculate       │
│ balance changes │
└────────┬────────┘
         ▼
┌─────────────────┐     Yes    ┌─────────────┐
│ Would cause     │───────────▶│ Check if    │
│ negative balance?            │ EQUITY      │
└────────┬────────┘            └──────┬──────┘
         │ No                         │
         ▼                            ▼
┌─────────────────┐            ┌─────────────┐
│ Proceed to post │◀───────────│ Is EQUITY?  │
└────────┬────────┘     No     └──────┬──────┘
         │                            │ Yes
         │                            ▼
         │                     ┌─────────────┐
         │                     │ Set requires│
         │                     │ approval=YES│
         │                     └──────┬──────┘
         │                            │
         ▼◀───────────────────────────┘
┌─────────────────┐     Yes    ┌─────────────┐
│ Requires        │───────────▶│ Create      │
│ approval?       │            │ Approval    │
└────────┬────────┘            │ Request     │
         │ No                  └──────┬──────┘
         ▼                            │
┌─────────────────┐            ┌──────▼──────┐
│ Update account  │            │ Return 202  │
│ balances        │            │ Pending     │
└────────┬────────┘            │ Approval    │
         ▼                     └─────────────┘
┌─────────────────┐
│ Set status=     │
│ POSTED          │
└────────┬────────┘
         ▼
┌─────────────────┐
│ Record balance  │
│ changes         │
└────────┬────────┘
         ▼
┌─────────────────┐
│ Publish event   │
│ TransactionPosted
└────────┬────────┘
         ▼
┌─────────────────┐
│ Return 200 OK   │
└────────┬────────┘
         ▼
┌─────────────┐
│    END      │
└─────────────┘
```

### 2.4 Approval Workflow Flow

```
┌─────────────┐
│   START     │
│ (Approval   │
│  Request)   │
└──────┬──────┘
       ▼
┌─────────────────┐
│ Admin reviews   │
│ pending approval│
└────────┬────────┘
         ▼
┌─────────────────┐
│ Admin decision? │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌────────┐ ┌────────┐
│APPROVE │ │ REJECT │
└───┬────┘ └───┬────┘
    │          │
    ▼          ▼
┌────────┐ ┌────────────┐
│Update  │ │ Update     │
│status= │ │ status=    │
│APPROVED│ │ REJECTED   │
└───┬────┘ └─────┬──────┘
    │            │
    ▼            ▼
┌────────────┐ ┌────────────┐
│ Publish    │ │ Publish    │
│ Approval   │ │ Approval   │
│ Granted    │ │ Denied     │
└─────┬──────┘ └──────┬─────┘
      │               │
      ▼               ▼
┌────────────┐ ┌────────────┐
│ Auto-post  │ │ Notify     │
│ transaction│ │ requester  │
└─────┬──────┘ └──────┬─────┘
      │               │
      └───────┬───────┘
              ▼
       ┌─────────────┐
       │    END      │
       └─────────────┘
```

---

## 3. Use Cases

### 3.1 Authentication Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-AUTH-01 | Register User | Public | Create new user account (pending approval) |
| UC-AUTH-02 | Login | User | Authenticate and receive JWT token |
| UC-AUTH-03 | Logout | User | Invalidate current session |
| UC-AUTH-04 | Change Password | User | Update own password |
| UC-AUTH-05 | Approve Registration | Admin | Approve pending user registration |
| UC-AUTH-06 | Decline Registration | Admin | Decline pending user registration |
| UC-AUTH-07 | Deactivate User | Admin | Deactivate user account |

### 3.2 Company Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-COM-01 | Create Company | Admin | Create new company |
| UC-COM-02 | Activate Company | Admin | Activate pending company |
| UC-COM-03 | Update Company | Admin | Update company details |
| UC-COM-04 | Deactivate Company | Admin | Deactivate company (cascades) |
| UC-COM-05 | Update Settings | Admin | Update company settings |

### 3.3 Chart of Accounts Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-COA-01 | Create Account | User | Create new account in chart |
| UC-COA-02 | List Accounts | User | View all accounts for company |
| UC-COA-03 | View Account | User | View single account details |
| UC-COA-04 | Update Account | User | Update account name |
| UC-COA-05 | Deactivate Account | Admin | Deactivate account (if balance=0) |
| UC-COA-06 | Initialize Chart | System | Create default accounts for new company |

### 3.4 Transaction Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-TXN-01 | Create Transaction | User | Create new pending transaction |
| UC-TXN-02 | List Transactions | User | View transactions with filters |
| UC-TXN-03 | View Transaction | User | View single transaction with lines |
| UC-TXN-04 | Edit Transaction | User | Edit pending transaction |
| UC-TXN-05 | Post Transaction | User | Post transaction to ledger |
| UC-TXN-06 | Void Transaction | Admin | Void posted transaction |
| UC-TXN-07 | Delete Transaction | User | Delete pending transaction |

### 3.5 Ledger Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-LED-01 | View Account Balance | User | Get current balance for account |
| UC-LED-02 | View All Balances | User | Get all account balances |
| UC-LED-03 | View Balance History | User | Get balance changes for account |
| UC-LED-04 | View General Ledger | User | Get all transactions for account |

### 3.6 Approval Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-APR-01 | List Pending Approvals | Admin | View all pending approvals |
| UC-APR-02 | Approve Request | Admin | Grant approval |
| UC-APR-03 | Reject Request | Admin | Deny approval with reason |
| UC-APR-04 | Cancel Request | User | Cancel own pending approval |

### 3.7 Reporting Use Cases

| ID | Use Case | Actor | Description |
|----|----------|-------|-------------|
| UC-RPT-01 | Generate Balance Sheet | User | Generate balance sheet report |
| UC-RPT-02 | Generate Income Statement | User | Generate income statement |
| UC-RPT-03 | Generate Trial Balance | User | Generate trial balance |
| UC-RPT-04 | Export Report | User | Export report to PDF/Excel |

---

## 4. Database Tables

### 4.1 Companies Table

```sql
CREATE TABLE companies (
    id CHAR(36) PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    legal_name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(50) NOT NULL UNIQUE,
    tax_id_type VARCHAR(20) NOT NULL DEFAULT 'TIN',
    
    -- Address
    street1 VARCHAR(255) NOT NULL,
    street2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'PH',
    
    -- Configuration
    default_currency CHAR(3) NOT NULL DEFAULT 'PHP',
    fiscal_year_start_month TINYINT NOT NULL DEFAULT 1,
    fiscal_year_start_day TINYINT NOT NULL DEFAULT 1,
    default_timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Manila',
    
    -- Status
    status ENUM('pending', 'active', 'suspended', 'deactivated') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    deactivated_at TIMESTAMP NULL,
    
    INDEX idx_companies_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Company Settings Table

```sql
CREATE TABLE company_settings (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    
    require_approval_negative_equity BOOLEAN NOT NULL DEFAULT TRUE,
    transaction_approval_threshold DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    allow_backdated_transactions BOOLEAN NOT NULL DEFAULT TRUE,
    max_backdate_days INT NOT NULL DEFAULT 30,
    auto_post_transactions BOOLEAN NOT NULL DEFAULT FALSE,
    date_format VARCHAR(20) NOT NULL DEFAULT 'Y-m-d',
    number_format VARCHAR(20) NOT NULL DEFAULT '#,##0.00',
    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_company_settings (company_id),
    CONSTRAINT fk_settings_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.3 Users Table

```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
    registration_status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    
    INDEX idx_users_company (company_id),
    INDEX idx_users_status (registration_status),
    INDEX idx_users_active (is_active),
    
    CONSTRAINT fk_users_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.4 Sessions Table

```sql
CREATE TABLE sessions (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_token (token_hash),
    INDEX idx_sessions_expires (expires_at),
    
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.5 Accounts Table

```sql
CREATE TABLE accounts (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    account_code VARCHAR(10) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    normal_balance ENUM('debit', 'credit') NOT NULL,
    parent_account_id CHAR(36) NULL,
    hierarchy_level TINYINT NOT NULL DEFAULT 0,
    hierarchy_path VARCHAR(100) NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_system_account BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    
    UNIQUE KEY uk_accounts_company_code (company_id, account_code),
    INDEX idx_accounts_company (company_id),
    INDEX idx_accounts_type (company_id, account_type),
    INDEX idx_accounts_parent (parent_account_id),
    INDEX idx_accounts_active (company_id, is_active),
    
    CONSTRAINT fk_accounts_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_accounts_parent FOREIGN KEY (parent_account_id)
        REFERENCES accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.6 Account Balances Table

```sql
CREATE TABLE account_balances (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    company_id CHAR(36) NOT NULL,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_debits DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_credits DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    transaction_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_transaction_at TIMESTAMP NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_balances_account (account_id),
    INDEX idx_balances_company (company_id),
    
    CONSTRAINT fk_balances_account FOREIGN KEY (account_id)
        REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_balances_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.7 Transactions Table

```sql
CREATE TABLE transactions (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    transaction_number VARCHAR(20) NOT NULL,
    transaction_date DATE NOT NULL,
    description VARCHAR(500) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'posted', 'voided') NOT NULL DEFAULT 'pending',
    requires_approval BOOLEAN NOT NULL DEFAULT FALSE,
    
    created_by CHAR(36) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    posted_by CHAR(36) NULL,
    posted_at TIMESTAMP NULL,
    
    voided_by CHAR(36) NULL,
    voided_at TIMESTAMP NULL,
    void_reason VARCHAR(500) NULL,
    
    UNIQUE KEY uk_transactions_number (company_id, transaction_number),
    INDEX idx_transactions_company (company_id),
    INDEX idx_transactions_date (company_id, transaction_date),
    INDEX idx_transactions_status (company_id, status),
    INDEX idx_transactions_created_by (created_by),
    
    CONSTRAINT fk_transactions_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_created_by FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_posted_by FOREIGN KEY (posted_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_transactions_voided_by FOREIGN KEY (voided_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.8 Transaction Lines Table

```sql
CREATE TABLE transaction_lines (
    id CHAR(36) PRIMARY KEY,
    transaction_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    line_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    line_order SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_lines_transaction (transaction_id),
    INDEX idx_lines_account (account_id),
    
    CONSTRAINT fk_lines_transaction FOREIGN KEY (transaction_id)
        REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_lines_account FOREIGN KEY (account_id)
        REFERENCES accounts(id) ON DELETE RESTRICT,
    CONSTRAINT chk_line_amount_positive CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.9 Balance Changes Table

```sql
CREATE TABLE balance_changes (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    transaction_id CHAR(36) NOT NULL,
    line_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    previous_balance DECIMAL(15,2) NOT NULL,
    new_balance DECIMAL(15,2) NOT NULL,
    change_amount DECIMAL(15,2) NOT NULL,
    is_reversal BOOLEAN NOT NULL DEFAULT FALSE,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_changes_account (account_id),
    INDEX idx_changes_transaction (transaction_id),
    INDEX idx_changes_date (occurred_at),
    
    CONSTRAINT fk_changes_account FOREIGN KEY (account_id)
        REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_changes_transaction FOREIGN KEY (transaction_id)
        REFERENCES transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.10 Approvals Table

```sql
CREATE TABLE approvals (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    approval_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id CHAR(36) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    reason_type VARCHAR(50) NOT NULL,
    reason_description TEXT NOT NULL,
    reason_details JSON NULL,
    amount DECIMAL(15,2) NULL,
    priority TINYINT NOT NULL DEFAULT 3,
    
    requested_by CHAR(36) NOT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    reviewed_by CHAR(36) NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_approvals_company (company_id),
    INDEX idx_approvals_status (status),
    INDEX idx_approvals_entity (entity_type, entity_id),
    INDEX idx_approvals_pending (company_id, status),
    INDEX idx_approvals_expires (expires_at),
    
    CONSTRAINT fk_approvals_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_approvals_requested_by FOREIGN KEY (requested_by)
        REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_approvals_reviewed_by FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_different_reviewer CHECK (reviewed_by IS NULL OR reviewed_by != requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.11 Activity Logs Table

```sql
CREATE TABLE activity_logs (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NULL,
    actor_user_id CHAR(36) NULL,
    actor_type VARCHAR(20) NOT NULL,
    actor_name VARCHAR(255) NOT NULL,
    impersonated_by CHAR(36) NULL,
    activity_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_state JSON NULL,
    new_state JSON NULL,
    changes JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(100) NULL,
    request_id VARCHAR(100) NULL,
    endpoint VARCHAR(255) NULL,
    http_method VARCHAR(10) NULL,
    severity ENUM('info', 'warning', 'critical', 'security') NOT NULL DEFAULT 'info',
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_logs_company (company_id),
    INDEX idx_logs_actor (actor_user_id),
    INDEX idx_logs_entity (entity_type, entity_id),
    INDEX idx_logs_type (activity_type),
    INDEX idx_logs_occurred (occurred_at),
    INDEX idx_logs_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.12 Audit Snapshots Table

```sql
CREATE TABLE audit_snapshots (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    snapshot_type ENUM('daily', 'monthly', 'quarterly', 'yearly') NOT NULL,
    snapshot_date DATE NOT NULL,
    balance_summary JSON NOT NULL,
    checksum VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_snapshots_company (company_id),
    INDEX idx_snapshots_type (snapshot_type),
    INDEX idx_snapshots_date (snapshot_date),
    
    CONSTRAINT fk_snapshots_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 5. Backend Structure

### 5.1 Directory Structure

```
src/
├── Domain/                          # Core Business Logic
│   ├── Shared/                      # Shared Kernel
│   │   ├── ValueObject/
│   │   │   ├── Uuid.php
│   │   │   ├── Money.php
│   │   │   ├── Currency.php
│   │   │   └── Email.php
│   │   └── Event/
│   │       ├── DomainEvent.php
│   │       └── EventDispatcher.php
│   │
│   ├── Identity/                    # Bounded Context
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   └── Session.php
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   ├── Role.php
│   │   │   └── RegistrationStatus.php
│   │   ├── Repository/
│   │   │   └── UserRepositoryInterface.php
│   │   ├── Service/
│   │   │   ├── AuthenticationService.php
│   │   │   └── PasswordService.php
│   │   └── Event/
│   │       ├── UserRegistered.php
│   │       └── UserAuthenticated.php
│   │
│   ├── Company/                     # Bounded Context
│   │   ├── Entity/
│   │   │   └── Company.php
│   │   ├── ValueObject/
│   │   │   ├── CompanyId.php
│   │   │   └── CompanyStatus.php
│   │   ├── Repository/
│   │   │   └── CompanyRepositoryInterface.php
│   │   └── Event/
│   │       └── CompanyCreated.php
│   │
│   ├── ChartOfAccounts/             # Bounded Context
│   │   ├── Entity/
│   │   │   └── Account.php
│   │   ├── ValueObject/
│   │   │   ├── AccountId.php
│   │   │   ├── AccountCode.php
│   │   │   ├── AccountType.php
│   │   │   └── NormalBalance.php
│   │   ├── Repository/
│   │   │   └── AccountRepositoryInterface.php
│   │   └── Event/
│   │       └── AccountCreated.php
│   │
│   ├── Transaction/                 # Bounded Context
│   │   ├── Entity/
│   │   │   ├── Transaction.php
│   │   │   └── TransactionLine.php
│   │   ├── ValueObject/
│   │   │   ├── TransactionId.php
│   │   │   ├── LineType.php
│   │   │   └── TransactionStatus.php
│   │   ├── Repository/
│   │   │   └── TransactionRepositoryInterface.php
│   │   ├── Service/
│   │   │   ├── TransactionValidator.php
│   │   │   └── BalanceCalculator.php
│   │   └── Event/
│   │       ├── TransactionCreated.php
│   │       ├── TransactionPosted.php
│   │       └── TransactionVoided.php
│   │
│   ├── Ledger/                      # Bounded Context
│   │   ├── Entity/
│   │   │   ├── AccountBalance.php
│   │   │   └── BalanceChange.php
│   │   ├── Repository/
│   │   │   └── LedgerRepositoryInterface.php
│   │   ├── Service/
│   │   │   └── LedgerPostingService.php
│   │   └── Event/
│   │       └── AccountBalanceChanged.php
│   │
│   ├── Approval/                    # Bounded Context
│   │   ├── Entity/
│   │   │   └── Approval.php
│   │   ├── ValueObject/
│   │   │   └── ApprovalStatus.php
│   │   ├── Repository/
│   │   │   └── ApprovalRepositoryInterface.php
│   │   └── Event/
│   │       ├── ApprovalRequested.php
│   │       └── ApprovalGranted.php
│   │
│   └── Audit/                       # Bounded Context
│       ├── Entity/
│       │   └── ActivityLog.php
│       └── Repository/
│           └── ActivityLogRepositoryInterface.php
│
├── Application/                     # Use Cases
│   ├── Command/
│   │   ├── Identity/
│   │   │   ├── RegisterUserCommand.php
│   │   │   └── AuthenticateCommand.php
│   │   ├── Transaction/
│   │   │   ├── CreateTransactionCommand.php
│   │   │   └── PostTransactionCommand.php
│   │   └── Account/
│   │       └── CreateAccountCommand.php
│   ├── Handler/
│   │   ├── Identity/
│   │   │   ├── RegisterUserHandler.php
│   │   │   └── AuthenticateHandler.php
│   │   ├── Transaction/
│   │   │   ├── CreateTransactionHandler.php
│   │   │   └── PostTransactionHandler.php
│   │   └── Account/
│   │       └── CreateAccountHandler.php
│   ├── Query/
│   │   ├── GetTransactionQuery.php
│   │   └── GetAccountBalanceQuery.php
│   └── DTO/
│       ├── UserDTO.php
│       ├── TransactionDTO.php
│       └── AccountDTO.php
│
└── Infrastructure/                  # External Concerns
    ├── Persistence/
    │   ├── MySQL/
    │   │   ├── MySQLUserRepository.php
    │   │   ├── MySQLAccountRepository.php
    │   │   ├── MySQLTransactionRepository.php
    │   │   └── MySQLLedgerRepository.php
    │   └── InMemory/
    │       └── InMemoryUserRepository.php
    ├── Http/
    │   ├── Controller/
    │   │   ├── AuthController.php
    │   │   ├── AccountController.php
    │   │   ├── TransactionController.php
    │   │   └── ReportController.php
    │   ├── Middleware/
    │   │   ├── AuthMiddleware.php
    │   │   └── CorsMiddleware.php
    │   ├── Request/
    │   │   └── CreateTransactionRequest.php
    │   └── Response/
    │       └── JsonResponse.php
    ├── Event/
    │   └── SimpleEventBus.php
    └── Migration/
        ├── Migrator.php
        └── migrations/
            ├── 001_create_companies.php
            ├── 002_create_users.php
            ├── 003_create_accounts.php
            └── ...
```

### 5.2 API Endpoints Summary

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /api/v1/auth/register | Register new user | No |
| POST | /api/v1/auth/login | Login | No |
| POST | /api/v1/auth/logout | Logout | Yes |
| GET | /api/v1/users | List users | Admin |
| GET | /api/v1/users/:id | Get user | Admin |
| POST | /api/v1/users/:id/approve | Approve user | Admin |
| GET | /api/v1/companies | List companies | Admin |
| POST | /api/v1/companies | Create company | Admin |
| GET | /api/v1/accounts | List accounts | Yes |
| POST | /api/v1/accounts | Create account | Yes |
| GET | /api/v1/accounts/:id | Get account | Yes |
| GET | /api/v1/accounts/:id/balance | Get balance | Yes |
| GET | /api/v1/transactions | List transactions | Yes |
| POST | /api/v1/transactions | Create transaction | Yes |
| GET | /api/v1/transactions/:id | Get transaction | Yes |
| POST | /api/v1/transactions/:id/post | Post transaction | Yes |
| POST | /api/v1/transactions/:id/void | Void transaction | Admin |
| GET | /api/v1/approvals | List approvals | Admin |
| POST | /api/v1/approvals/:id/approve | Approve | Admin |
| POST | /api/v1/approvals/:id/reject | Reject | Admin |
| GET | /api/v1/reports/balance-sheet | Balance sheet | Yes |
| GET | /api/v1/reports/income-statement | Income statement | Yes |
| GET | /api/v1/reports/trial-balance | Trial balance | Yes |

---

## 6. Implementation Checklist

### Phase 1: Database & Migrations
- [ ] Create database migration files
- [ ] Run migrations to create all tables
- [ ] Seed default data (system accounts, admin user)
- [ ] Test database connections

### Phase 2: Domain Layer (TDD)
- [ ] Implement Shared Value Objects (Uuid, Money, Email)
- [ ] Implement Identity domain (User, Session, Auth)
- [ ] Implement Company domain
- [ ] Implement Account domain
- [ ] Implement Transaction domain with validation
- [ ] Implement Ledger domain
- [ ] Implement Approval domain
- [ ] Implement Audit domain

### Phase 3: Application Layer
- [ ] Implement Command/Handler for User Registration
- [ ] Implement Command/Handler for Authentication
- [ ] Implement Command/Handler for Account CRUD
- [ ] Implement Command/Handler for Transaction CRUD
- [ ] Implement Command/Handler for Posting
- [ ] Implement Command/Handler for Approvals

### Phase 4: Infrastructure Layer
- [ ] Implement MySQL Repositories
- [ ] Implement Event Bus
- [ ] Implement HTTP Controllers
- [ ] Implement Middleware (Auth, CORS)
- [ ] Implement Request Validation
- [ ] Implement Response Formatting

### Phase 5: Testing
- [ ] Unit tests for all Domain entities
- [ ] Unit tests for all Value Objects
- [ ] Unit tests for Transaction Validator
- [ ] Integration tests for Repositories
- [ ] API tests for all endpoints

### Phase 6: Documentation & Deployment
- [ ] API documentation (OpenAPI/Swagger)
- [ ] Docker configuration
- [ ] CI/CD pipeline verification
- [ ] Production deployment guide

---

## Quick Start Commands

```bash
# Install dependencies
composer install

# Run migrations
php migrate up

# Seed database
php seed

# Run tests
composer test

# Start development server
php -S localhost:8080 -t public/

# Run linting
composer lint

# Run static analysis
composer analyse
```

---

**Document Complete - Ready for Implementation**
