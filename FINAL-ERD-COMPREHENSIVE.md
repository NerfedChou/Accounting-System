# 📊 COMPREHENSIVE ENTITY RELATIONSHIP DIAGRAM (ERD)

## Accounting System Database Schema
**Date**: November 17, 2025  
**Version**: 1.0 (Production Ready)

---

## 🎨 ERD DIAGRAM

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       ACCOUNTING SYSTEM DATABASE                         │
│                  Entity Relationship Diagram (ERD)                       │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────┐
│       COMPANIES          │
├──────────────────────────┤
│ PK  id (INT)            │
│     company_name (VARCHAR)│
│     address (TEXT)       │
│     phone (VARCHAR)      │
│     email (VARCHAR)      │
│     currency_code (CHAR) │
│     is_active (BOOL)     │
│     created_at (DATETIME)│
│     updated_at (DATETIME)│
└──────────────┬───────────┘
               │
               │ (1:N)
               │
     ┌─────────┼──────────────────────┬────────────────┬──────────────┐
     │         │                      │                │              │
     ▼         ▼                      ▼                ▼              ▼
┌────────┐ ┌──────────┐      ┌──────────────┐  ┌─────────────┐ ┌──────────────┐
│ USERS  │ │ ACCOUNTS │      │ TRANSACTIONS │  │ ACTIVITY_LOGS│ │ PENDING_REG  │
└────────┘ └──────────┘      └──────────────┘  └─────────────┘ └──────────────┘


═══════════════════════════════════════════════════════════════════════════
1. USERS TABLE
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│         USERS            │
├──────────────────────────┤
│ PK  id (INT)            │
│     username (VARCHAR)   │ UNIQUE
│     password (VARCHAR)   │ (hashed)
│     full_name (VARCHAR)  │
│     email (VARCHAR)      │ UNIQUE
│     role (ENUM)          │ 'admin' | 'tenant'
│ FK  company_id (INT)     │ → COMPANIES.id (NULL for admin)
│     is_active (BOOL)     │
│     registration_status  │ 'pending' | 'approved' | 'declined'
│     declined_reason (TEXT)│
│     approved_at (DATETIME)│
│     approved_by (INT)    │ → USERS.id
│     last_login (DATETIME)│
│     deactivation_reason  │
│     created_at (DATETIME)│
└──────────────┬───────────┘
               │
               │ (1:N)
               │
               ▼
        [Creates/Manages]
               │
┌──────────────┴───────────┐
│ TRANSACTIONS, ACCOUNTS,  │
│ ACTIVITY_LOGS, etc.      │
└──────────────────────────┘


═══════════════════════════════════════════════════════════════════════════
2. ACCOUNT_TYPES TABLE (Reference/System Data)
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│    ACCOUNT_TYPES         │
├──────────────────────────┤
│ PK  id (INT)            │
│     type_name (VARCHAR)  │ Asset, Liability, Equity, Revenue, Expense
│     normal_balance (ENUM)│ 'debit' | 'credit'
│     description (TEXT)   │
│     created_at (DATETIME)│
└──────────────┬───────────┘
               │
               │ (1:N)
               │
               ▼
         ACCOUNTS


═══════════════════════════════════════════════════════════════════════════
3. ACCOUNTS TABLE
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│       ACCOUNTS           │
├──────────────────────────┤
│ PK  id (INT)            │
│     account_code (VARCHAR)│ UNIQUE per company
│     account_name (VARCHAR)│
│ FK  account_type_id (INT)│ → ACCOUNT_TYPES.id
│ FK  company_id (INT)     │ → COMPANIES.id
│     description (TEXT)   │
│     opening_balance (DEC)│
│     current_balance (DEC)│
│     is_active (BOOL)     │
│     is_system_account(BOOL)│ External source accounts
│     parent_account_id    │ → ACCOUNTS.id (NULL, for hierarchy)
│     created_by (INT)     │ → USERS.id
│     created_at (DATETIME)│
│     updated_at (DATETIME)│
└──────────────┬───────────┘
               │
               │ (1:N)
               │
               ▼
      TRANSACTION_LINES


═══════════════════════════════════════════════════════════════════════════
4. TRANSACTION_STATUSES TABLE (Reference/System Data)
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│  TRANSACTION_STATUSES    │
├──────────────────────────┤
│ PK  id (INT)            │
│     status_code (VARCHAR)│ PENDING, POSTED, VOIDED
│     status_name (VARCHAR)│ Pending, Posted, Voided
│     description (TEXT)   │
│     created_at (DATETIME)│
└──────────────┬───────────┘
               │
               │ (1:N)
               │
               ▼
         TRANSACTIONS


═══════════════════════════════════════════════════════════════════════════
5. TRANSACTIONS TABLE (Main Double-Entry Transactions)
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│     TRANSACTIONS         │
├──────────────────────────┤
│ PK  id (INT)            │
│     transaction_number   │ TXN-XXXXXX (UNIQUE)
│     transaction_date (DATE)│
│     transaction_type     │ Asset, Liability, etc.
│     entry_mode (ENUM)    │ 'double' (always)
│ FK  company_id (INT)     │ → COMPANIES.id
│ FK  status_id (INT)      │ → TRANSACTION_STATUSES.id
│     description (TEXT)   │
│     reference_number     │
│     total_amount (DECIMAL)│
│     requires_approval(BOOL)│ For special cases (negative equity)
│     created_by (INT)     │ → USERS.id
│     created_at (DATETIME)│
│     posted_by (INT)      │ → USERS.id
│     posted_at (DATETIME) │
│     voided_by (INT)      │ → USERS.id
│     voided_at (DATETIME) │
│     void_reason (TEXT)   │
└──────────────┬───────────┘
               │
               │ (1:N)
               │
               ▼
      TRANSACTION_LINES


═══════════════════════════════════════════════════════════════════════════
6. TRANSACTION_LINES TABLE (Double-Entry Lines)
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│   TRANSACTION_LINES      │
├──────────────────────────┤
│ PK  id (INT)            │
│ FK  transaction_id (INT) │ → TRANSACTIONS.id
│ FK  account_id (INT)     │ → ACCOUNTS.id
│     line_type (ENUM)     │ 'debit' | 'credit'
│     amount (DECIMAL)     │
│     created_at (DATETIME)│
└──────────────────────────┘

**CONSTRAINT**: For each transaction, SUM(debits) MUST EQUAL SUM(credits)


═══════════════════════════════════════════════════════════════════════════
7. ACTIVITY_LOGS TABLE (Audit Trail)
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│     ACTIVITY_LOGS        │
├──────────────────────────┤
│ PK  id (INT)            │
│ FK  user_id (INT)        │ → USERS.id
│     username (VARCHAR)   │
│     user_role (ENUM)     │ 'admin' | 'tenant'
│ FK  company_id (INT)     │ → COMPANIES.id
│     activity_type (ENUM) │ 'login' | 'logout' | 'transaction' | 
│                          │ 'void' | 'user' | 'company' | 'account' | 'other'
│     details (TEXT)       │
│     ip_address (VARCHAR) │
│     created_at (DATETIME)│
└──────────────────────────┘


═══════════════════════════════════════════════════════════════════════════
8. PENDING_REGISTRATIONS TABLE
═══════════════════════════════════════════════════════════════════════════

┌──────────────────────────┐
│  PENDING_REGISTRATIONS   │
├──────────────────────────┤
│ PK  id (INT)            │
│     username (VARCHAR)   │
│     password (VARCHAR)   │ (hashed)
│     full_name (VARCHAR)  │
│     email (VARCHAR)      │
│ FK  company_id (INT)     │ → COMPANIES.id
│     status (ENUM)        │ 'pending' | 'approved' | 'declined'
│     declined_reason (TEXT)│
│     reviewed_by (INT)    │ → USERS.id
│     reviewed_at (DATETIME)│
│     created_at (DATETIME)│
└──────────────────────────┘


═══════════════════════════════════════════════════════════════════════════
RELATIONSHIPS SUMMARY
═══════════════════════════════════════════════════════════════════════════

COMPANIES (1) ──────< (N) USERS
COMPANIES (1) ──────< (N) ACCOUNTS
COMPANIES (1) ──────< (N) TRANSACTIONS
COMPANIES (1) ──────< (N) ACTIVITY_LOGS
COMPANIES (1) ──────< (N) PENDING_REGISTRATIONS

ACCOUNT_TYPES (1) ──────< (N) ACCOUNTS

ACCOUNTS (1) ──────< (N) TRANSACTION_LINES

TRANSACTION_STATUSES (1) ──────< (N) TRANSACTIONS

TRANSACTIONS (1) ──────< (N) TRANSACTION_LINES

USERS (1) ──────< (N) TRANSACTIONS (created_by)
USERS (1) ──────< (N) TRANSACTIONS (posted_by)
USERS (1) ──────< (N) TRANSACTIONS (voided_by)
USERS (1) ──────< (N) ACCOUNTS (created_by)
USERS (1) ──────< (N) ACTIVITY_LOGS (user_id)


═══════════════════════════════════════════════════════════════════════════
BUSINESS RULES & CONSTRAINTS
═══════════════════════════════════════════════════════════════════════════

1. DOUBLE-ENTRY BOOKKEEPING
   ✓ Every transaction MUST have at least 2 lines
   ✓ Total Debits MUST EQUAL Total Credits
   ✓ No transaction can be posted if unbalanced

2. ACCOUNTING EQUATION
   ✓ Assets = Liabilities + Equity (always maintained)
   ✓ Validated after every posted transaction
   ✓ System prevents equation violation

3. BALANCE RULES
   ✓ Asset accounts: Cannot go negative
   ✓ Liability accounts: Cannot go negative
   ✓ Revenue accounts: Cannot go negative
   ✓ Expense accounts: Cannot go negative
   ✓ Equity accounts: CAN go negative (requires admin approval)

4. TRANSACTION STATUS WORKFLOW
   Pending (1) → Posted (2) → [Cannot be edited]
   Posted (2) → Voided (3) → [Balance reversed]
   Pending (1) → Voided (3) → [Deleted without posting]

5. NORMAL BALANCE RULES
   ✓ Asset: Debit increases, Credit decreases
   ✓ Liability: Credit increases, Debit decreases
   ✓ Equity: Credit increases, Debit decreases
   ✓ Revenue: Credit increases, Debit decreases
   ✓ Expense: Debit increases, Credit decreases

6. USER ROLES & PERMISSIONS
   ADMIN:
   ✓ Full access to all companies
   ✓ Can create/edit/delete any transaction
   ✓ Can approve/decline tenant registrations
   ✓ Can approve special transactions (negative equity)
   ✓ Can deactivate user accounts
   
   TENANT:
   ✓ Access only to their company data
   ✓ Can create transactions (pending or posted)
   ✓ Can edit/delete own pending transactions
   ✓ Cannot delete transactions requiring approval
   ✓ Cannot access other companies' data

7. EXTERNAL SOURCE ACCOUNTS
   ✓ Special accounts representing outside world
   ✓ Unlimited balance (simulated external entities)
   ✓ Used for: Banks, Investors, Customers, Vendors
   ✓ Marked with is_system_account = 1

8. AUDIT TRAIL
   ✓ Every action logged in activity_logs
   ✓ User, timestamp, details recorded
   ✓ IP address captured (future enhancement)
   ✓ Permanent audit trail (never deleted)


═══════════════════════════════════════════════════════════════════════════
INDEXES FOR PERFORMANCE
═══════════════════════════════════════════════════════════════════════════

PRIMARY KEYS:
- All tables have auto-incrementing INT primary keys

UNIQUE INDEXES:
- users.username
- users.email
- accounts.account_code (per company)
- transactions.transaction_number

FOREIGN KEY INDEXES:
- users.company_id → companies.id
- accounts.company_id → companies.id
- accounts.account_type_id → account_types.id
- transactions.company_id → companies.id
- transactions.status_id → transaction_statuses.id
- transaction_lines.transaction_id → transactions.id
- transaction_lines.account_id → accounts.id
- activity_logs.user_id → users.id
- activity_logs.company_id → companies.id

COMPOSITE INDEXES (Recommended):
- transactions (company_id, status_id, transaction_date)
- transaction_lines (transaction_id, account_id)
- activity_logs (company_id, created_at)
- accounts (company_id, is_active)


═══════════════════════════════════════════════════════════════════════════
DATA TYPES & SIZES
═══════════════════════════════════════════════════════════════════════════

INTEGERS:
- id: INT(11) AUTO_INCREMENT
- Foreign keys: INT(11)
- BOOLEAN: TINYINT(1) (0 or 1)

STRINGS:
- VARCHAR fields: VARCHAR(255) max
- Account codes: VARCHAR(50)
- Transaction numbers: VARCHAR(50)
- Email: VARCHAR(255)

TEXT:
- descriptions: TEXT (65,535 chars)
- details: TEXT
- reasons: TEXT

DECIMALS:
- Amounts/Balances: DECIMAL(15,2)
  - Max: 9,999,999,999,999.99
  - Precision: 2 decimal places

DATES:
- created_at: DATETIME
- updated_at: DATETIME
- transaction_date: DATE

ENUMS:
- role: ENUM('admin', 'tenant')
- line_type: ENUM('debit', 'credit')
- normal_balance: ENUM('debit', 'credit')
- activity_type: ENUM('login', 'logout', 'transaction', 'void', 'user', 'company', 'account', 'other')


═══════════════════════════════════════════════════════════════════════════
SYSTEM DATA (NEVER DELETE!)
═══════════════════════════════════════════════════════════════════════════

ACCOUNT_TYPES (5 records):
1. Asset (debit)
2. Liability (credit)
3. Equity (credit)
4. Revenue (credit)
5. Expense (debit)

TRANSACTION_STATUSES (3 records):
1. Pending (PENDING)
2. Posted (POSTED)
3. Voided (VOIDED)

These are reference tables required for the system to function!


═══════════════════════════════════════════════════════════════════════════
FUTURE ENHANCEMENTS (Optional)
═══════════════════════════════════════════════════════════════════════════

1. Parent-Child Transactions
   - Add: transactions.parent_transaction_id
   - Use for: Invoices → Payments, PO → Receipts

2. Recurring Transactions
   - New table: recurring_transactions
   - Auto-generate transactions on schedule

3. Multi-Currency Support
   - Add: exchange_rates table
   - Add: transactions.currency_id
   - Convert amounts for reporting

4. Budget Tracking
   - New table: budgets
   - Compare actual vs budget

5. Bank Reconciliation
   - New table: bank_statements
   - Match transactions with bank records

6. File Attachments
   - New table: transaction_attachments
   - Store receipts, invoices

7. Tags/Categories
   - New table: transaction_tags
   - Flexible categorization

8. Custom Fields
   - JSON column for company-specific fields


═══════════════════════════════════════════════════════════════════════════
END OF ERD
═══════════════════════════════════════════════════════════════════════════
```

---

## 📊 VISUAL DIAGRAM (Text-Based)

```
                            ┌─────────────┐
                            │  COMPANIES  │
                            └──────┬──────┘
                                   │
                ┌──────────────────┼────────────────────┐
                │                  │                    │
                ▼                  ▼                    ▼
         ┌──────────┐      ┌──────────────┐    ┌─────────────┐
         │  USERS   │      │  ACCOUNTS    │    │TRANSACTIONS │
         └────┬─────┘      └──────┬───────┘    └──────┬──────┘
              │                   │                    │
              │                   │                    │
              └───────┬───────────┴────────────────────┘
                      │
                      ▼
              ┌───────────────┐
              │ ACTIVITY_LOGS │
              └───────────────┘

┌─────────────────┐           ┌──────────────────────┐
│ ACCOUNT_TYPES   │──────────▶│     ACCOUNTS         │
│ (Reference)     │   1:N     └──────────┬───────────┘
└─────────────────┘                      │
                                         │ 1:N
┌──────────────────────┐                 │
│ TRANSACTION_STATUSES │                 ▼
│ (Reference)          │──────────▶┌────────────────────┐
└──────────────────────┘   1:N    │TRANSACTION_LINES   │
                                   │(Debits & Credits)  │
                                   └────────────────────┘
```

---

## 🎯 KEY FEATURES

1. ✅ **Double-Entry Bookkeeping** - Enforced at database level
2. ✅ **Accounting Equation** - Always balanced
3. ✅ **Multi-Company** - Isolated data per company
4. ✅ **Role-Based Access** - Admin vs Tenant permissions
5. ✅ **Audit Trail** - Complete activity logging
6. ✅ **Transaction Workflow** - Pending → Posted → Voided
7. ✅ **Balance Validation** - Prevents negative balances
8. ✅ **External Accounts** - Simulate outside world
9. ✅ **Referential Integrity** - Foreign keys enforced
10. ✅ **Scalable Design** - Ready for growth

---

**Created**: November 17, 2025  
**Status**: Production Ready ✅  
**Version**: 1.0

