# 🎨 VISUAL ERD DIAGRAM - Accounting System

## Database Schema Visualization

```mermaid
erDiagram
    COMPANIES ||--o{ USERS : "has"
    COMPANIES ||--o{ ACCOUNTS : "owns"
    COMPANIES ||--o{ TRANSACTIONS : "has"
    COMPANIES ||--o{ ACTIVITY_LOGS : "logs"
    COMPANIES ||--o{ PENDING_REGISTRATIONS : "receives"
    
    ACCOUNT_TYPES ||--o{ ACCOUNTS : "categorizes"
    TRANSACTION_STATUSES ||--o{ TRANSACTIONS : "defines"
    
    USERS ||--o{ TRANSACTIONS : "creates"
    USERS ||--o{ TRANSACTIONS : "posts"
    USERS ||--o{ TRANSACTIONS : "voids"
    USERS ||--o{ ACCOUNTS : "creates"
    USERS ||--o{ ACTIVITY_LOGS : "generates"
    
    ACCOUNTS ||--o{ TRANSACTION_LINES : "appears_in"
    TRANSACTIONS ||--o{ TRANSACTION_LINES : "contains"
    
    COMPANIES {
        int id PK
        varchar company_name
        text address
        varchar phone
        varchar email
        char currency_code
        boolean is_active
        datetime created_at
        datetime updated_at
    }
    
    USERS {
        int id PK
        varchar username UK
        varchar password
        varchar full_name
        varchar email UK
        enum role
        int company_id FK
        boolean is_active
        varchar registration_status
        text declined_reason
        datetime approved_at
        int approved_by FK
        datetime last_login
        text deactivation_reason
        datetime created_at
    }
    
    ACCOUNT_TYPES {
        int id PK
        varchar type_name
        enum normal_balance
        text description
        datetime created_at
    }
    
    ACCOUNTS {
        int id PK
        varchar account_code UK
        varchar account_name
        int account_type_id FK
        int company_id FK
        text description
        decimal opening_balance
        decimal current_balance
        boolean is_active
        boolean is_system_account
        int parent_account_id FK
        int created_by FK
        datetime created_at
        datetime updated_at
    }
    
    TRANSACTION_STATUSES {
        int id PK
        varchar status_code UK
        varchar status_name
        text description
        datetime created_at
    }
    
    TRANSACTIONS {
        int id PK
        varchar transaction_number UK
        date transaction_date
        varchar transaction_type
        enum entry_mode
        int company_id FK
        int status_id FK
        text description
        varchar reference_number
        decimal total_amount
        boolean requires_approval
        int created_by FK
        datetime created_at
        int posted_by FK
        datetime posted_at
        int voided_by FK
        datetime voided_at
        text void_reason
    }
    
    TRANSACTION_LINES {
        int id PK
        int transaction_id FK
        int account_id FK
        enum line_type
        decimal amount
        datetime created_at
    }
    
    ACTIVITY_LOGS {
        int id PK
        int user_id FK
        varchar username
        enum user_role
        int company_id FK
        enum activity_type
        text details
        varchar ip_address
        datetime created_at
    }
    
    PENDING_REGISTRATIONS {
        int id PK
        varchar username
        varchar password
        varchar full_name
        varchar email
        int company_id FK
        enum status
        text declined_reason
        int reviewed_by FK
        datetime reviewed_at
        datetime created_at
    }
```

## Legend

- **PK** = Primary Key
- **FK** = Foreign Key
- **UK** = Unique Key
- **||--o{** = One-to-Many relationship
- **Enum** = Predefined set of values

## Color Coding (for visual tools)

- 🔵 **Blue** = Core business entities (Companies, Accounts, Transactions)
- 🟢 **Green** = Reference/System tables (Account Types, Transaction Statuses)
- 🟡 **Yellow** = User management (Users, Pending Registrations)
- 🟣 **Purple** = Transaction details (Transaction Lines)
- 🟠 **Orange** = Audit/Logging (Activity Logs)

---

**Status**: Production Ready ✅  
**Date**: November 17, 2025

