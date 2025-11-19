# 🎨 COMPLETE SYSTEM VISUALIZATION
## Accounting System - Use Case Diagram, ERD, and Flow Charts

**Generated**: November 18, 2025  
**Version**: 1.0 - Complete System Analysis

---

## 📋 TABLE OF CONTENTS

1. [Use Case Diagram](#-use-case-diagram)
2. [Entity Relationship Diagram (ERD)](#-entity-relationship-diagram-erd)
3. [System Flow Charts](#-system-flow-charts)
   - Authentication Flow
   - Transaction Processing Flow
   - Registration & Approval Flow
   - Company & Tenant Management Flow
4. [Sequence Diagrams](#-sequence-diagrams)
5. [System Architecture](#-system-architecture)

---

## 🎯 USE CASE DIAGRAM

```mermaid
graph TB
    subgraph "External Actors"
        Admin[👤 Administrator]
        Tenant[👤 Tenant User]
        Guest[👤 Guest/Visitor]
    end
    
    subgraph "Accounting System"
        subgraph "Authentication & Registration"
            UC1[Login to System]
            UC2[Register New Account]
            UC3[Logout]
            UC4[Change Password]
        end
        
        subgraph "Admin Functions"
            UC5[Manage Companies]
            UC6[Approve/Decline Registration]
            UC7[Manage Tenants]
            UC8[Activate/Deactivate Tenants]
            UC9[View All Transactions]
            UC10[Approve/Decline Transactions]
            UC11[Void Transactions]
            UC12[View Activity Logs]
            UC13[Export Reports CSV]
            UC14[View System Statistics]
            UC15[Manage System Accounts]
        end
        
        subgraph "Tenant Functions"
            UC16[View Dashboard]
            UC17[Manage Chart of Accounts]
            UC18[Create Transaction]
            UC19[Update Transaction]
            UC20[Delete Transaction]
            UC21[Post Transaction]
            UC22[View Balance Sheet]
            UC23[View Income Statement]
            UC24[Manage Company Profile]
            UC25[View Transaction History]
        end
        
        subgraph "Common Functions"
            UC26[Update Profile]
            UC27[View Notifications]
            UC28[Check Account Balance]
        end
    end
    
    %% Admin Relationships
    Admin --> UC1
    Admin --> UC3
    Admin --> UC4
    Admin --> UC5
    Admin --> UC6
    Admin --> UC7
    Admin --> UC8
    Admin --> UC9
    Admin --> UC10
    Admin --> UC11
    Admin --> UC12
    Admin --> UC13
    Admin --> UC14
    Admin --> UC15
    Admin --> UC26
    Admin --> UC27
    
    %% Tenant Relationships
    Tenant --> UC1
    Tenant --> UC3
    Tenant --> UC4
    Tenant --> UC16
    Tenant --> UC17
    Tenant --> UC18
    Tenant --> UC19
    Tenant --> UC20
    Tenant --> UC21
    Tenant --> UC22
    Tenant --> UC23
    Tenant --> UC24
    Tenant --> UC25
    Tenant --> UC26
    Tenant --> UC27
    Tenant --> UC28
    
    %% Guest Relationships
    Guest --> UC2
    Guest --> UC1
    
    %% Include/Extend Relationships
    UC18 -.->|includes| UC28
    UC19 -.->|includes| UC28
    UC21 -.->|includes| UC28
    UC6 -.->|extends| UC7
    UC10 -.->|extends| UC9
    
    style Admin fill:#3498db,stroke:#2980b9,color:#fff
    style Tenant fill:#27ae60,stroke:#229954,color:#fff
    style Guest fill:#95a5a6,stroke:#7f8c8d,color:#fff
```

---

## 📊 ENTITY RELATIONSHIP DIAGRAM (ERD)

```mermaid
erDiagram
    COMPANIES ||--o{ USERS : "employs"
    COMPANIES ||--o{ ACCOUNTS : "owns"
    COMPANIES ||--o{ TRANSACTIONS : "records"
    COMPANIES ||--o{ ACTIVITY_LOGS : "generates"
    COMPANIES ||--o{ PENDING_REGISTRATIONS : "receives"
    
    ACCOUNT_TYPES ||--o{ ACCOUNTS : "categorizes"
    TRANSACTION_STATUSES ||--o{ TRANSACTIONS : "defines_status"
    
    USERS ||--o{ TRANSACTIONS : "creates"
    USERS ||--o{ TRANSACTIONS : "posts"
    USERS ||--o{ TRANSACTIONS : "voids"
    USERS ||--o{ ACCOUNTS : "creates"
    USERS ||--o{ ACTIVITY_LOGS : "performs"
    USERS ||--o{ USERS : "approves"
    
    ACCOUNTS ||--o{ TRANSACTION_LINES : "appears_in"
    ACCOUNTS ||--o{ ACCOUNTS : "parent_of"
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
        enum role "admin, tenant"
        int company_id FK
        boolean is_active
        varchar registration_status "pending, approved, declined"
        text declined_reason
        datetime approved_at
        int approved_by FK
        datetime last_login
        text deactivation_reason
        datetime created_at
        datetime updated_at
    }
    
    ACCOUNT_TYPES {
        int id PK
        varchar type_name "Asset, Liability, Equity, Revenue, Expense"
        enum normal_balance "debit, credit"
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
        varchar status_code "PENDING, POSTED, VOIDED"
        varchar status_name
        text description
        datetime created_at
    }
    
    TRANSACTIONS {
        int id PK
        varchar transaction_number UK
        date transaction_date
        varchar transaction_type
        enum entry_mode "double"
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
        enum line_type "debit, credit"
        decimal amount
        datetime created_at
    }
    
    ACTIVITY_LOGS {
        int id PK
        int user_id FK
        varchar username
        enum user_role "admin, tenant"
        int company_id FK
        varchar activity_type "transaction, user, company, account"
        text details
        datetime created_at
    }
    
    PENDING_REGISTRATIONS {
        int id PK
        varchar username
        varchar full_name
        varchar email
        varchar company_name
        text company_address
        varchar company_phone
        varchar company_email
        varchar status "pending, approved, declined"
        text decline_reason
        int processed_by FK
        datetime processed_at
        datetime created_at
    }
```

---

## 🔄 SYSTEM FLOW CHARTS

### 1. AUTHENTICATION FLOW

```mermaid
flowchart TD
    Start([User Accesses System]) --> CheckSession{Has Valid<br/>Session?}
    
    CheckSession -->|Yes| CheckRole{Check User Role}
    CheckSession -->|No| LoginPage[Show Login Page]
    
    LoginPage --> EnterCreds[Enter Credentials]
    EnterCreds --> ValidateCreds{Valid<br/>Credentials?}
    
    ValidateCreds -->|No| ShowError[Show Error Message]
    ShowError --> LoginPage
    
    ValidateCreds -->|Yes| CheckUserStatus{User Status?}
    
    CheckUserStatus -->|Deactivated| DeactivatedPage[Account Deactivated Page]
    CheckUserStatus -->|Declined| DeclinedPage[Registration Declined Page]
    CheckUserStatus -->|Pending| PendingPage[Pending Approval Page]
    CheckUserStatus -->|Active| CreateSession[Create Session]
    
    CreateSession --> UpdateLastLogin[Update Last Login]
    UpdateLastLogin --> CheckRole
    
    CheckRole -->|Admin| AdminDashboard[Admin Dashboard]
    CheckRole -->|Tenant| CheckCompany{Company<br/>Active?}
    
    CheckCompany -->|No| DeactivatedPage
    CheckCompany -->|Yes| TenantDashboard[Tenant Dashboard]
    
    AdminDashboard --> End([Access Granted])
    TenantDashboard --> End
    DeactivatedPage --> End
    DeclinedPage --> End
    PendingPage --> End
    
    style Start fill:#e8f5e9
    style End fill:#ffebee
    style AdminDashboard fill:#3498db,color:#fff
    style TenantDashboard fill:#27ae60,color:#fff
    style DeactivatedPage fill:#95a5a6,color:#fff
    style DeclinedPage fill:#e74c3c,color:#fff
    style PendingPage fill:#f39c12,color:#fff
```

---

### 2. TRANSACTION PROCESSING FLOW

```mermaid
flowchart TD
    Start([Create Transaction]) --> CheckAuth{User<br/>Authenticated?}
    
    CheckAuth -->|No| Unauthorized[Return 401 Unauthorized]
    CheckAuth -->|Yes| CheckActive{User<br/>Active?}
    
    CheckActive -->|No| Deactivated[Return Account Deactivated]
    CheckActive -->|Yes| ValidateInput{Valid<br/>Input Data?}
    
    ValidateInput -->|No| ValidationError[Return Validation Errors]
    ValidateInput -->|Yes| CheckLines{At Least<br/>2 Lines?}
    
    CheckLines -->|No| MinLinesError[Error: Need 2+ Lines]
    CheckLines -->|Yes| CheckBalance{Debits =<br/>Credits?}
    
    CheckBalance -->|No| BalanceError[Error: Debits Must Equal Credits]
    CheckBalance -->|Yes| ValidateAccounts{All Accounts<br/>Valid & Active?}
    
    ValidateAccounts -->|No| AccountError[Error: Invalid Accounts]
    ValidateAccounts -->|Yes| CheckNegative{Will Cause<br/>Negative Balance?}
    
    CheckNegative -->|Yes Assets/Liabilities| NegativeError[Error: Cannot Be Negative]
    CheckNegative -->|Yes Equity & Not Approved| SetPending[Set requires_approval=1<br/>Status=Pending]
    CheckNegative -->|No| CheckStatus{Status?}
    
    SetPending --> BeginTxn
    CheckStatus -->|Posted| DirectPost[Set status_id=2]
    CheckStatus -->|Pending| SetPendingStatus[Set status_id=1]
    
    DirectPost --> BeginTxn[Begin Database Transaction]
    SetPendingStatus --> BeginTxn
    
    BeginTxn --> InsertTxn[Insert into TRANSACTIONS]
    InsertTxn --> GetTxnID[Get transaction_id]
    GetTxnID --> LoopLines{For Each Line}
    
    LoopLines --> InsertLine[Insert TRANSACTION_LINE]
    InsertLine --> CalcChange[Calculate Balance Change]
    CalcChange --> UpdateAccount[Update Account Balance]
    UpdateAccount --> MoreLines{More Lines?}
    
    MoreLines -->|Yes| LoopLines
    MoreLines -->|No| ValidateEquation{Accounting<br/>Equation Valid?}
    
    ValidateEquation -->|No| Rollback[Rollback Transaction]
    ValidateEquation -->|Yes| LogActivity[Log Activity]
    
    LogActivity --> CommitTxn[Commit Transaction]
    CommitTxn --> ReturnSuccess[Return Success]
    
    Rollback --> ReturnError[Return Error]
    
    ReturnSuccess --> End([Transaction Complete])
    ReturnError --> End
    Unauthorized --> End
    Deactivated --> End
    ValidationError --> End
    MinLinesError --> End
    BalanceError --> End
    AccountError --> End
    NegativeError --> End
    
    style Start fill:#e8f5e9
    style End fill:#c8e6c9
    style ReturnSuccess fill:#4caf50,color:#fff
    style ReturnError fill:#f44336,color:#fff
    style CommitTxn fill:#2196f3,color:#fff
    style Rollback fill:#ff9800,color:#fff
```

---

### 3. REGISTRATION & APPROVAL FLOW

```mermaid
flowchart TD
    Start([Guest Visits Registration]) --> FillForm[Fill Registration Form]
    FillForm --> ValidateForm{Form Valid?}
    
    ValidateForm -->|No| ShowFormErrors[Show Validation Errors]
    ShowFormErrors --> FillForm
    
    ValidateForm -->|Yes| CheckDuplicate{Username/Email<br/>Exists?}
    
    CheckDuplicate -->|Yes| DuplicateError[Error: Already Exists]
    DuplicateError --> FillForm
    
    CheckDuplicate -->|No| BeginReg[Begin Registration]
    
    BeginReg --> CreateUser[Create User Record]
    CreateUser --> SetPendingStatus[Set registration_status='pending'<br/>is_active=0]
    SetPendingStatus --> CreateCompany[Create Company Record]
    CreateCompany --> LinkUserCompany[Link User to Company]
    LinkUserCompany --> InsertPending[Insert into PENDING_REGISTRATIONS]
    InsertPending --> NotifyAdmin[Notify Admin]
    NotifyAdmin --> ShowPending[Redirect to Pending Page]
    
    ShowPending --> WaitApproval{Wait for<br/>Admin Action}
    
    WaitApproval -->|Approve| AdminApprove[Admin Clicks Approve]
    WaitApproval -->|Decline| AdminDecline[Admin Clicks Decline]
    
    AdminApprove --> SelectCompany[Select/Create Company]
    SelectCompany --> UpdateUserApprove[UPDATE users SET<br/>registration_status='approved'<br/>company_id=X<br/>is_active=1<br/>approved_by=admin_id<br/>approved_at=NOW]
    UpdateUserApprove --> UpdatePendingApprove[UPDATE pending_registrations<br/>status='approved']
    UpdatePendingApprove --> LogApproval[Log Activity]
    LogApproval --> NotifyTenantApprove[Notify Tenant Approved]
    NotifyTenantApprove --> TenantLogin[Tenant Can Login]
    TenantLogin --> EndApproved([Registration Approved])
    
    AdminDecline --> EnterReason[Enter Decline Reason]
    EnterReason --> UpdateUserDecline[UPDATE users SET<br/>registration_status='declined'<br/>is_active=0]
    UpdateUserDecline --> UpdatePendingDecline[UPDATE pending_registrations<br/>status='declined'<br/>decline_reason=X]
    UpdatePendingDecline --> LogDeclining[Log Activity]
    LogDeclining --> NotifyTenantDecline[Notify Tenant Declined]
    NotifyTenantDecline --> ShowDeclined[Show Declined Page]
    ShowDeclined --> EndDeclined([Registration Declined])
    
    style Start fill:#e3f2fd
    style EndApproved fill:#c8e6c9,color:#000
    style EndDeclined fill:#ffcdd2,color:#000
    style AdminApprove fill:#4caf50,color:#fff
    style AdminDecline fill:#f44336,color:#fff
    style ShowPending fill:#fff3e0
    style TenantLogin fill:#2196f3,color:#fff
```

---

### 4. COMPANY & TENANT MANAGEMENT FLOW

```mermaid
flowchart TD
    Start([Admin Manages Tenants]) --> ListTenants[View All Tenants]
    ListTenants --> SelectAction{Select Action}
    
    SelectAction -->|View Details| ViewDetails[View Tenant Details]
    SelectAction -->|Deactivate| DeactivateTenant
    SelectAction -->|Reactivate| ReactivateTenant
    SelectAction -->|Manage Company| ManageCompany
    
    subgraph "Deactivate Tenant Flow"
        DeactivateTenant[Select Deactivate] --> EnterDeactiveReason[Enter Deactivation Reason]
        EnterDeactiveReason --> ConfirmDeactivate{Confirm<br/>Deactivation?}
        ConfirmDeactivate -->|No| ListTenants
        ConfirmDeactivate -->|Yes| UpdateTenantInactive[UPDATE users SET<br/>is_active=0<br/>deactivation_reason=X]
        UpdateTenantInactive --> LogoutTenant[Force Logout Tenant]
        LogoutTenant --> LogDeactivate[Log Activity]
        LogDeactivate --> NotifyDeactivate[Show Success Message]
        NotifyDeactivate --> ListTenants
    end
    
    subgraph "Reactivate Tenant Flow"
        ReactivateTenant[Select Reactivate] --> ConfirmReactivate{Confirm<br/>Reactivation?}
        ConfirmReactivate -->|No| ListTenants
        ConfirmReactivate -->|Yes| CheckCompanyActive{Company<br/>Active?}
        CheckCompanyActive -->|No| ErrorCompanyInactive[Error: Company Inactive]
        CheckCompanyActive -->|Yes| UpdateTenantActive[UPDATE users SET<br/>is_active=1<br/>deactivation_reason=NULL]
        UpdateTenantActive --> LogReactivate[Log Activity]
        LogReactivate --> NotifyReactivate[Show Success Message]
        NotifyReactivate --> ListTenants
        ErrorCompanyInactive --> ListTenants
    end
    
    subgraph "Manage Company Flow"
        ManageCompany[View Company List] --> SelectCompanyAction{Select Action}
        SelectCompanyAction -->|Create| CreateCompany[Create New Company]
        SelectCompanyAction -->|Update| UpdateCompany[Update Company Info]
        SelectCompanyAction -->|Toggle Status| ToggleCompany[Toggle Active/Inactive]
        
        CreateCompany --> ValidateCompany{Valid Data?}
        ValidateCompany -->|No| CompanyError[Show Errors]
        ValidateCompany -->|Yes| InsertCompany[INSERT into companies]
        InsertCompany --> LogCompanyCreate[Log Activity]
        LogCompanyCreate --> ManageCompany
        CompanyError --> ManageCompany
        
        UpdateCompany --> SaveChanges[Save Company Changes]
        SaveChanges --> LogCompanyUpdate[Log Activity]
        LogCompanyUpdate --> ManageCompany
        
        ToggleCompany --> CheckTenants{Has Active<br/>Tenants?}
        CheckTenants -->|Yes & Deactivating| CascadeDeactivate[Cascade Deactivate<br/>All Company Tenants]
        CheckTenants -->|No or Activating| UpdateCompanyStatus[Update Company Status]
        CascadeDeactivate --> UpdateCompanyStatus
        UpdateCompanyStatus --> LogCompanyToggle[Log Activity]
        LogCompanyToggle --> ManageCompany
    end
    
    ViewDetails --> ListTenants
    
    ListTenants --> End([Management Complete])
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style UpdateTenantInactive fill:#ff9800,color:#fff
    style UpdateTenantActive fill:#4caf50,color:#fff
    style CascadeDeactivate fill:#f44336,color:#fff
```

---

## 🔁 SEQUENCE DIAGRAMS

### Transaction Creation Sequence

```mermaid
sequenceDiagram
    actor Tenant
    participant Frontend
    participant API
    participant Validator
    participant Database
    participant Logger
    
    Tenant->>Frontend: Create Transaction
    Frontend->>Frontend: Validate Form
    Frontend->>API: POST /api/transactions/create.php
    
    API->>API: Check Session & Auth
    API->>Database: Verify User Active Status
    Database-->>API: User Data
    
    API->>Validator: Validate Transaction Data
    Validator->>Validator: Check Lines (min 2)
    Validator->>Validator: Check Balance (Debits=Credits)
    Validator->>Database: Validate Accounts Exist & Active
    Database-->>Validator: Account Data
    
    Validator->>Validator: Check Negative Balances
    Validator-->>API: Validation Result
    
    alt Validation Failed
        API-->>Frontend: Error Response
        Frontend-->>Tenant: Show Errors
    else Validation Passed
        API->>Database: BEGIN TRANSACTION
        API->>Database: INSERT TRANSACTIONS
        Database-->>API: transaction_id
        
        loop For Each Line
            API->>Database: INSERT TRANSACTION_LINES
            API->>Database: UPDATE accounts.current_balance
        end
        
        API->>Validator: Validate Accounting Equation
        Validator->>Database: Calculate Assets, Liabilities, Equity
        Database-->>Validator: Balances
        
        Validator->>Validator: Check: Assets = Liabilities + Equity
        
        alt Equation Invalid
            API->>Database: ROLLBACK
            API-->>Frontend: Error: Equation Imbalance
            Frontend-->>Tenant: Show Error
        else Equation Valid
            API->>Logger: Log Activity
            Logger->>Database: INSERT activity_logs
            API->>Database: COMMIT
            Database-->>API: Success
            API-->>Frontend: Success Response
            Frontend-->>Tenant: Show Success & Refresh
        end
    end
```

---

### Admin Approval Sequence

```mermaid
sequenceDiagram
    actor Admin
    participant AdminUI
    participant API
    participant Database
    participant Notification
    actor Tenant
    
    Admin->>AdminUI: View Pending Registrations
    AdminUI->>API: GET /api/admin/pending-registrations.php
    API->>Database: SELECT * FROM pending_registrations<br/>WHERE status='pending'
    Database-->>API: Pending List
    API-->>AdminUI: Registration Data
    AdminUI-->>Admin: Display Pending Items
    
    Admin->>AdminUI: Click Approve
    AdminUI->>AdminUI: Select Company (or create new)
    Admin->>AdminUI: Confirm Approval
    
    AdminUI->>API: POST /api/admin/approve-registration.php<br/>{user_id, company_id}
    
    API->>Database: BEGIN TRANSACTION
    API->>Database: Verify user status='pending'
    Database-->>API: User Status
    
    API->>Database: UPDATE users SET<br/>registration_status='approved',<br/>company_id=X,<br/>is_active=1,<br/>approved_by=admin_id,<br/>approved_at=NOW()
    
    API->>Database: UPDATE pending_registrations<br/>SET status='approved'
    
    API->>Database: INSERT activity_logs
    
    API->>Database: COMMIT
    Database-->>API: Success
    
    API-->>AdminUI: Approval Success
    AdminUI-->>Admin: Show Success Message
    
    API->>Notification: Notify Tenant
    Notification-->>Tenant: Email/Alert: Account Approved
    
    Tenant->>AdminUI: Login to System
    AdminUI-->>Tenant: Access Granted
```

---

## 🏗️ SYSTEM ARCHITECTURE

```mermaid
graph TB
    subgraph "Presentation Layer"
        AdminUI[Admin Portal<br/>HTML/CSS/JS]
        TenantUI[Tenant Portal<br/>HTML/CSS/JS]
        SharedUI[Shared Components]
    end
    
    subgraph "Application Layer"
        AuthAPI[Authentication API]
        UserAPI[User Management API]
        CompanyAPI[Company API]
        AccountAPI[Account API]
        TransactionAPI[Transaction API]
        ReportAPI[Report API]
        AdminAPI[Admin API]
    end
    
    subgraph "Business Logic Layer"
        AuthMiddleware[Auth Middleware]
        Validator[Accounting Validator]
        Processor[Transaction Processor]
        Logger[Activity Logger]
    end
    
    subgraph "Data Layer"
        Database[(MySQL Database)]
        Session[PHP Sessions]
    end
    
    subgraph "Infrastructure"
        Nginx[Nginx Web Server]
        PHP[PHP-FPM 8.2]
        Docker[Docker Containers]
    end
    
    AdminUI --> AuthAPI
    AdminUI --> UserAPI
    AdminUI --> CompanyAPI
    AdminUI --> AdminAPI
    
    TenantUI --> AuthAPI
    TenantUI --> AccountAPI
    TenantUI --> TransactionAPI
    TenantUI --> ReportAPI
    
    AdminUI --> SharedUI
    TenantUI --> SharedUI
    
    AuthAPI --> AuthMiddleware
    UserAPI --> AuthMiddleware
    CompanyAPI --> AuthMiddleware
    AccountAPI --> AuthMiddleware
    TransactionAPI --> AuthMiddleware
    ReportAPI --> AuthMiddleware
    AdminAPI --> AuthMiddleware
    
    TransactionAPI --> Validator
    TransactionAPI --> Processor
    AccountAPI --> Validator
    
    AuthMiddleware --> Session
    Validator --> Database
    Processor --> Database
    Logger --> Database
    
    AuthAPI --> Database
    UserAPI --> Database
    CompanyAPI --> Database
    AccountAPI --> Database
    TransactionAPI --> Database
    ReportAPI --> Database
    AdminAPI --> Database
    
    Nginx --> PHP
    PHP --> AuthAPI
    PHP --> UserAPI
    PHP --> CompanyAPI
    PHP --> AccountAPI
    PHP --> TransactionAPI
    PHP --> ReportAPI
    PHP --> AdminAPI
    
    Docker --> Nginx
    Docker --> PHP
    Docker --> Database
    
    style AdminUI fill:#3498db,color:#fff
    style TenantUI fill:#27ae60,color:#fff
    style Database fill:#e74c3c,color:#fff
    style Docker fill:#2c3e50,color:#fff
```

---

## 📊 DATA FLOW DIAGRAM

```mermaid
flowchart LR
    subgraph "External Entities"
        Admin[Admin User]
        Tenant[Tenant User]
    end
    
    subgraph "System Processes"
        P1[1.0 Authentication]
        P2[2.0 User Management]
        P3[3.0 Transaction Processing]
        P4[4.0 Report Generation]
        P5[5.0 Activity Logging]
    end
    
    subgraph "Data Stores"
        D1[(Users)]
        D2[(Companies)]
        D3[(Accounts)]
        D4[(Transactions)]
        D5[(Activity Logs)]
    end
    
    Admin -->|Login Credentials| P1
    Tenant -->|Login Credentials| P1
    
    P1 -->|User Data| D1
    D1 -->|Session Info| P1
    P1 -->|Session Token| Admin
    P1 -->|Session Token| Tenant
    
    Admin -->|Registration Action| P2
    P2 -->|User Updates| D1
    P2 -->|Company Assignment| D2
    D1 -->|User List| P2
    P2 -->|User Status| Admin
    
    Tenant -->|Transaction Data| P3
    P3 -->|Account Validation| D3
    D3 -->|Account Balances| P3
    P3 -->|Transaction Records| D4
    D4 -->|Transaction List| P3
    P3 -->|Transaction Status| Tenant
    
    Admin -->|Report Request| P4
    Tenant -->|Report Request| P4
    P4 -->|Query Accounts| D3
    P4 -->|Query Transactions| D4
    D3 -->|Balance Data| P4
    D4 -->|Transaction Data| P4
    P4 -->|Financial Reports| Admin
    P4 -->|Financial Reports| Tenant
    
    P1 -->|Login Events| P5
    P2 -->|User Actions| P5
    P3 -->|Transaction Events| P5
    P5 -->|Activity Records| D5
    D5 -->|Audit Trail| P5
    P5 -->|Activity Logs| Admin
    
    style Admin fill:#3498db,color:#fff
    style Tenant fill:#27ae60,color:#fff
    style D1 fill:#e74c3c,color:#fff
    style D2 fill:#e74c3c,color:#fff
    style D3 fill:#e74c3c,color:#fff
    style D4 fill:#e74c3c,color:#fff
    style D5 fill:#e74c3c,color:#fff
```

---

## 🎯 COMPONENT DIAGRAM

```mermaid
graph TB
    subgraph "Frontend Components"
        subgraph "Admin Portal"
            A1[Dashboard]
            A2[User Management]
            A3[Company Management]
            A4[Transaction Monitor]
            A5[Reports]
            A6[Activity Logs]
        end
        
        subgraph "Tenant Portal"
            T1[Dashboard]
            T2[Chart of Accounts]
            T3[Transaction Entry]
            T4[Reports]
            T5[Company Profile]
        end
        
        subgraph "Shared Components"
            S1[Login/Auth]
            S2[Notifications]
            S3[Settings]
            S4[Navigation]
        end
    end
    
    subgraph "Backend Components"
        subgraph "API Layer"
            API1[Auth API]
            API2[User API]
            API3[Company API]
            API4[Account API]
            API5[Transaction API]
            API6[Report API]
        end
        
        subgraph "Core Services"
            SVC1[Authentication Service]
            SVC2[Authorization Service]
            SVC3[Transaction Processor]
            SVC4[Accounting Validator]
            SVC5[Activity Logger]
        end
        
        subgraph "Data Access"
            DAO1[User DAO]
            DAO2[Company DAO]
            DAO3[Account DAO]
            DAO4[Transaction DAO]
        end
    end
    
    subgraph "Database"
        DB[(MySQL)]
    end
    
    A1 --> API1
    A2 --> API2
    A3 --> API3
    A4 --> API5
    A5 --> API6
    A6 --> API2
    
    T1 --> API1
    T2 --> API4
    T3 --> API5
    T4 --> API6
    T5 --> API3
    
    S1 --> API1
    S2 --> API1
    S3 --> API2
    
    API1 --> SVC1
    API2 --> SVC2
    API5 --> SVC3
    API5 --> SVC4
    API1 --> SVC5
    API2 --> SVC5
    API5 --> SVC5
    
    SVC1 --> DAO1
    SVC2 --> DAO1
    SVC3 --> DAO3
    SVC3 --> DAO4
    SVC4 --> DAO3
    SVC5 --> DAO1
    
    API3 --> DAO2
    API4 --> DAO3
    API6 --> DAO3
    API6 --> DAO4
    
    DAO1 --> DB
    DAO2 --> DB
    DAO3 --> DB
    DAO4 --> DB
    
    style A1 fill:#3498db,color:#fff
    style T1 fill:#27ae60,color:#fff
    style DB fill:#e74c3c,color:#fff
```

---

## 📝 SYSTEM SUMMARY

### Key Features Overview

| Feature | Admin | Tenant | Description |
|---------|-------|--------|-------------|
| **Authentication** | ✅ | ✅ | Login/Logout with role-based access |
| **User Management** | ✅ | ❌ | Create, approve, deactivate users |
| **Company Management** | ✅ | View Only | CRUD operations on companies |
| **Chart of Accounts** | ✅ | ✅ | Manage account hierarchy |
| **Transaction Entry** | ✅ | ✅ | Double-entry bookkeeping |
| **Transaction Approval** | ✅ | ❌ | Approve/decline special transactions |
| **Transaction Voiding** | ✅ | ❌ | Void posted transactions |
| **Financial Reports** | ✅ | ✅ | Balance Sheet, Income Statement |
| **Activity Logs** | ✅ | ❌ | Audit trail of all actions |
| **Dashboard** | ✅ | ✅ | Real-time statistics and charts |

---

### Technical Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 8.2
- **Database**: MySQL 8.0
- **Web Server**: Nginx
- **Container**: Docker & Docker Compose
- **Architecture**: MVC Pattern
- **Security**: Session-based authentication, Role-based access control

---

### Accounting Rules Enforced

1. **Double-Entry Bookkeeping**: Every transaction must have equal debits and credits
2. **Accounting Equation**: Assets = Liabilities + Equity (always balanced)
3. **Account Types**: Asset, Liability, Equity, Revenue, Expense
4. **Normal Balances**: Enforced for each account type
5. **Negative Balance Prevention**: Assets and Liabilities cannot go negative
6. **Transaction Immutability**: Posted transactions cannot be edited (only voided)
7. **Audit Trail**: All actions are logged for accountability

---

## 📞 CONTACT & SUPPORT

For questions or issues, please refer to:
- **README.md** - System setup and overview
- **COMPLETE_ACCOUNTING_IMPLEMENTATION.md** - Detailed accounting logic
- **FINAL-ERD-COMPREHENSIVE.md** - Database schema details

---

**Document Version**: 1.0  
**Last Updated**: November 18, 2025  
**System Status**: ✅ Production Ready

