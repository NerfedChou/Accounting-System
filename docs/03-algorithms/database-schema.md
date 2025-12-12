# Database Schema Documentation

> **Master Architect Reference**: Complete database schema and migration strategy.

## Overview

The Accounting System uses **MySQL 8.0** with the following schema organization:

- **8 Bounded Contexts** → 8 Logical Groups of Tables
- **Multi-tenant** → All tables include `company_id`
- **Event Sourcing** → Selected domains use event store
- **Audit Trail** → Immutable activity logging

---

## Schema Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        ACCOUNTING SYSTEM DATABASE                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐       │
│  │    IDENTITY     │     │    COMPANY      │     │     CHART       │       │
│  ├─────────────────┤     ├─────────────────┤     │   OF ACCOUNTS   │       │
│  │ users           │────▶│ companies       │────▶├─────────────────┤       │
│  │ sessions        │     │ company_settings│     │ accounts        │       │
│  │ user_roles      │     └─────────────────┘     └────────┬────────┘       │
│  └─────────────────┘                                       │                │
│                                                            │                │
│  ┌─────────────────┐     ┌─────────────────┐             │                │
│  │  TRANSACTIONS   │────▶│     LEDGER      │◀────────────┘                │
│  ├─────────────────┤     ├─────────────────┤                              │
│  │ transactions    │     │ account_balances│                              │
│  │ transaction_    │     │ balance_changes │                              │
│  │   lines         │     └─────────────────┘                              │
│  └────────┬────────┘                                                       │
│           │                                                                 │
│           ▼                                                                 │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐       │
│  │   APPROVALS     │     │   REPORTING     │     │   AUDIT TRAIL   │       │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤       │
│  │ approvals       │     │ generated_      │     │ activity_logs   │◀──ALL │
│  │ approval_       │     │   reports       │     │ audit_snapshots │       │
│  │   history       │     │ report_cache    │     │ security_alerts │       │
│  │ approval_rules  │     └─────────────────┘     └─────────────────┘       │
│  └─────────────────┘                                                       │
│                                                                              │
│  ┌─────────────────┐                                                        │
│  │  EVENT STORE    │  (for event-sourced domains)                          │
│  ├─────────────────┤                                                        │
│  │ domain_events   │                                                        │
│  │ event_snapshots │                                                        │
│  └─────────────────┘                                                        │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Table Definitions

### 1. Identity & Access Management

#### users
```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) REFERENCES companies(id),
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
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

    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email),
    INDEX idx_users_company (company_id),
    INDEX idx_users_status (registration_status),
    INDEX idx_users_active (is_active),

    CONSTRAINT fk_users_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### sessions
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

---

### 2. Company Management

#### companies
```sql
CREATE TABLE companies (
    id CHAR(36) PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(50) NOT NULL,
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

    UNIQUE KEY uk_companies_name (company_name),
    UNIQUE KEY uk_companies_tax_id (tax_id),
    INDEX idx_companies_status (status),

    CONSTRAINT chk_fiscal_month CHECK (fiscal_year_start_month BETWEEN 1 AND 12),
    CONSTRAINT chk_fiscal_day CHECK (fiscal_year_start_day BETWEEN 1 AND 31)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### company_settings
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

---

### 3. Chart of Accounts

#### accounts
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
        REFERENCES accounts(id) ON DELETE SET NULL,
    CONSTRAINT chk_hierarchy_level CHECK (hierarchy_level BETWEEN 0 AND 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4. Transaction Processing

#### transactions
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
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_amount_positive CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### transaction_lines
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

---

### 5. Ledger & Posting

#### account_balances
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

#### balance_changes
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

---

### 6. Approval Workflow

#### approvals
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
    INDEX idx_approvals_pending (company_id, status) USING BTREE,
    INDEX idx_approvals_expires (expires_at),

    CONSTRAINT fk_approvals_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_approvals_requested_by FOREIGN KEY (requested_by)
        REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_approvals_reviewed_by FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_priority CHECK (priority BETWEEN 1 AND 5),
    CONSTRAINT chk_different_reviewer CHECK (reviewed_by IS NULL OR reviewed_by != requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### approval_history
```sql
CREATE TABLE approval_history (
    id CHAR(36) PRIMARY KEY,
    approval_id CHAR(36) NOT NULL,
    previous_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by CHAR(36) NOT NULL,
    change_reason TEXT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_history_approval (approval_id),

    CONSTRAINT fk_history_approval FOREIGN KEY (approval_id)
        REFERENCES approvals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 7. Audit Trail

#### activity_logs
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

    -- NO UPDATE OR DELETE - Append only
    INDEX idx_logs_company (company_id),
    INDEX idx_logs_actor (actor_user_id),
    INDEX idx_logs_entity (entity_type, entity_id),
    INDEX idx_logs_type (activity_type),
    INDEX idx_logs_occurred (occurred_at),
    INDEX idx_logs_severity (severity),
    INDEX idx_logs_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### audit_snapshots
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

### 8. Event Store (Event Sourcing)

#### domain_events
```sql
CREATE TABLE domain_events (
    id CHAR(36) PRIMARY KEY,
    aggregate_type VARCHAR(100) NOT NULL,
    aggregate_id CHAR(36) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_data JSON NOT NULL,
    metadata JSON NULL,
    sequence_number BIGINT UNSIGNED NOT NULL,
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_events_aggregate_sequence (aggregate_type, aggregate_id, sequence_number),
    INDEX idx_events_aggregate (aggregate_type, aggregate_id),
    INDEX idx_events_type (event_type),
    INDEX idx_events_occurred (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### event_snapshots
```sql
CREATE TABLE event_snapshots (
    id CHAR(36) PRIMARY KEY,
    aggregate_type VARCHAR(100) NOT NULL,
    aggregate_id CHAR(36) NOT NULL,
    snapshot_data JSON NOT NULL,
    version BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_snapshots_aggregate (aggregate_type, aggregate_id),
    INDEX idx_snapshots_type (aggregate_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Migration Strategy

### Migration Files Structure

```
database/migrations/
├── 2025_12_13_000001_create_companies_table.php
├── 2025_12_13_000002_create_company_settings_table.php
├── 2025_12_13_000003_create_users_table.php
├── 2025_12_13_000004_create_sessions_table.php
├── 2025_12_13_000005_create_accounts_table.php
├── 2025_12_13_000006_create_account_balances_table.php
├── 2025_12_13_000007_create_transactions_table.php
├── 2025_12_13_000008_create_transaction_lines_table.php
├── 2025_12_13_000009_create_balance_changes_table.php
├── 2025_12_13_000010_create_approvals_table.php
├── 2025_12_13_000011_create_approval_history_table.php
├── 2025_12_13_000012_create_activity_logs_table.php
├── 2025_12_13_000013_create_audit_snapshots_table.php
├── 2025_12_13_000014_create_domain_events_table.php
└── 2025_12_13_000015_create_event_snapshots_table.php
```

### Migration Commands

```bash
# Create migration
php migrate create create_users_table

# Run migrations
php migrate up

# Rollback last migration
php migrate down

# Rollback all
php migrate reset

# Fresh start (drop all + migrate)
php migrate fresh

# Show status
php migrate status
```

---

## Indexes & Performance

### Key Indexes
- Primary keys: UUID (CHAR(36))
- Foreign keys: Indexed automatically
- Frequent query columns: Explicit indexes
- Composite indexes for common queries

### Query Optimization
```sql
-- Common queries with indexes

-- Find transactions by company and date range
SELECT * FROM transactions
WHERE company_id = ? AND transaction_date BETWEEN ? AND ?
ORDER BY transaction_date DESC;
-- Uses: idx_transactions_date

-- Find pending approvals
SELECT * FROM approvals
WHERE company_id = ? AND status = 'pending'
ORDER BY priority ASC, requested_at ASC;
-- Uses: idx_approvals_pending

-- Get account balances by type
SELECT a.*, ab.current_balance
FROM accounts a
JOIN account_balances ab ON a.id = ab.account_id
WHERE a.company_id = ? AND a.account_type = ? AND a.is_active = TRUE;
-- Uses: idx_accounts_type, uk_balances_account
```

---

## Data Integrity

### Constraints Summary
- **NOT NULL**: All required fields
- **UNIQUE**: Business keys (username, email, account_code)
- **FOREIGN KEY**: Referential integrity
- **CHECK**: Domain rules (amounts > 0, valid ranges)

### Soft Deletes
- `is_active` flag for logical deletion
- `deactivated_at` timestamp for audit
- No physical deletion except scheduled cleanup
