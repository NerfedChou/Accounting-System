# 🎯 FINAL COMPREHENSIVE SYSTEM FLOWCHART
## Professional Accounting System - Complete Architecture

---

## 📊 SYSTEM OVERVIEW FLOWCHART

```mermaid
graph TB
    Start([User Access System]) --> RoleCheck{User Role?}
    
    RoleCheck -->|Admin| AdminFlow[Admin Dashboard]
    RoleCheck -->|Tenant| TenantCheck{Check Status}
    RoleCheck -->|No Session| Login[Login Page]
    
    TenantCheck -->|Pending Registration| PendingPage[Pending Approval Page]
    TenantCheck -->|Declined| DeclinedPage[Registration Declined Page]
    TenantCheck -->|Deactivated| DeactivatedPage[Account Deactivated Page]
    TenantCheck -->|Approved & Active| TenantFlow[Tenant Dashboard]
    
    AdminFlow --> AdminModules[Admin Modules]
    TenantFlow --> TenantModules[Tenant Modules]
    
    AdminModules --> AM1[User Management]
    AdminModules --> AM2[Company Management]
    AdminModules --> AM3[Transaction Approval]
    AdminModules --> AM4[System Monitoring]
    AdminModules --> AM5[Reports for All Companies]
    
    TenantModules --> TM1[Account Management]
    TenantModules --> TM2[Transaction Recording]
    TenantModules --> TM3[Financial Reports]
    TenantModules --> TM4[Company Profile]
    
    style AdminFlow fill:#3498db,color:#fff
    style TenantFlow fill:#27ae60,color:#fff
    style PendingPage fill:#f39c12,color:#fff
    style DeclinedPage fill:#e74c3c,color:#fff
    style DeactivatedPage fill:#95a5a6,color:#fff
```

---

## 🔐 AUTHENTICATION & AUTHORIZATION FLOW

```mermaid
sequenceDiagram
    participant User
    participant Frontend
    participant Session API
    participant Database
    
    User->>Frontend: Access Page
    Frontend->>Session API: Check Session
    Session API->>Database: Validate Session & Role
    
    alt Valid Admin Session
        Database-->>Session API: Admin User Data
        Session API-->>Frontend: Authorized (Admin)
        Frontend-->>User: Show Admin Dashboard
    else Valid Tenant Session (Approved)
        Database-->>Session API: Tenant User Data
        Session API-->>Frontend: Check Registration Status
        alt Status = Approved & Active
            Frontend-->>User: Show Tenant Dashboard
        else Status = Pending
            Frontend-->>User: Redirect to Pending Page
        else Status = Declined
            Frontend-->>User: Redirect to Declined Page
        else Deactivated Account
            Frontend-->>User: Redirect to Deactivated Page
        end
    else Invalid Session
        Session API-->>Frontend: Unauthorized
        Frontend-->>User: Redirect to Login
    end
```

---

## 📝 USER REGISTRATION WORKFLOW

```mermaid
graph TB
    Start([User Visits Registration]) --> FillForm[Fill Registration Form]
    FillForm --> Submit[Submit Registration]
    Submit --> Validate{Validation}
    
    Validate -->|Invalid| ShowError[Show Validation Error]
    ShowError --> FillForm
    
    Validate -->|Valid| CreateUser[Create User Record]
    CreateUser --> SetStatus[Set Status = Pending]
    SetStatus --> CreateCompany[Create Company Record]
    CreateCompany --> NotifyAdmin[Notify Admin]
    NotifyAdmin --> PendingPage[User: Pending Approval Page]
    
    PendingPage --> WaitAdmin{Admin Action}
    
    WaitAdmin -->|Approve| Approve[Admin Approves]
    Approve --> UpdateStatus[Status = Approved]
    UpdateStatus --> SetActive[Set is_active = 1]
    SetActive --> NotifyUser[Notify User]
    NotifyUser --> UserLogin[User Can Login]
    
    WaitAdmin -->|Decline| Decline[Admin Declines]
    Decline --> SetDeclined[Status = Declined]
    SetDeclined --> AddReason[Add Decline Reason]
    AddReason --> NotifyDeclined[Notify User]
    NotifyDeclined --> DeclinedPage[User: Registration Declined Page]
    
    style Approve fill:#27ae60,color:#fff
    style Decline fill:#e74c3c,color:#fff
    style PendingPage fill:#f39c12,color:#fff
```

---

## 💰 ACCOUNT CREATION FLOW

```mermaid
graph TB
    Start([Create Account Button]) --> Modal[Open Account Modal]
    Modal --> TypeSelect{Account Type?}
    
    TypeSelect -->|Asset| AssetFlow[Asset Account Flow]
    TypeSelect -->|Liability| LiabilityFlow[Liability Account Flow]
    TypeSelect -->|Equity| EquityFlow[Equity Account Flow]
    TypeSelect -->|Revenue| RevenueFlow[Revenue Account Flow]
    TypeSelect -->|Expense| ExpenseFlow[Expense Account Flow]
    
    AssetFlow --> CodeSuggest[Suggest Code: 1000-1999]
    LiabilityFlow --> CodeSuggest2[Suggest Code: 2000-2999]
    EquityFlow --> CodeSuggest3[Suggest Code: 3000-3999]
    RevenueFlow --> CodeSuggest4[Suggest Code: 4000-4999]
    ExpenseFlow --> CodeSuggest5[Suggest Code: 5000-5999]
    
    CodeSuggest --> CheckExternal{External Account?}
    CodeSuggest2 --> CheckExternal
    CodeSuggest3 --> CheckExternal
    CodeSuggest4 --> CheckExternal
    CodeSuggest5 --> CheckExternal
    
    CheckExternal -->|Yes| SetExternal[Set Code: EXT-TYPE]
    CheckExternal -->|No| SetNormal[Use Normal Code]
    
    SetExternal --> SetUnlimited[Opening Balance: 999999999]
    SetNormal --> SetZero[Opening Balance: 0]
    
    SetUnlimited --> SaveAccount[Save to Database]
    SetZero --> SaveAccount
    
    SaveAccount --> ValidateCode{Code Exists?}
    
    ValidateCode -->|Yes| ShowError[Show Error: Code Exists]
    ValidateCode -->|No| InsertDB[Insert into accounts Table]
    
    InsertDB --> LogActivity[Log Account Creation]
    LogActivity --> Success[✅ Account Created]
    Success --> RefreshList[Refresh Account List]
    
    style SetExternal fill:#f39c12,color:#fff
    style SetNormal fill:#3498db,color:#fff
    style Success fill:#27ae60,color:#fff
```

---

## 📊 DOUBLE-ENTRY TRANSACTION FLOW

```mermaid
graph TB
    Start([Create Transaction]) --> OpenModal[Open Transaction Modal]
    OpenModal --> EnterDetails[Enter Transaction Details]
    EnterDetails --> AddLines[Add Transaction Lines]
    
    AddLines --> Line1[Line 1: Select Account, Type, Amount]
    AddLines --> Line2[Line 2: Select Account, Type, Amount]
    AddLines --> LineN[Line N: Additional Lines...]
    
    Line1 --> Calculate[Calculate Balance]
    Line2 --> Calculate
    LineN --> Calculate
    
    Calculate --> CheckBalance{Debits = Credits?}
    
    CheckBalance -->|No| ShowWarning[Show Balance Warning]
    ShowWarning --> AddLines
    
    CheckBalance -->|Yes| CheckDuplicate{Same Account Used Twice?}
    
    CheckDuplicate -->|Yes| ShowError[Error: Cannot use same account twice]
    ShowError --> AddLines
    
    CheckDuplicate -->|No| ValidateIntegrity[Validate Accounting Integrity]
    
    ValidateIntegrity --> CheckViolations{Critical Violations?}
    
    CheckViolations -->|Asset/Liability/Revenue/Expense Negative| BlockTransaction[❌ Block Transaction]
    BlockTransaction --> ShowIntegrityWarning[Show Integrity Warning Modal]
    ShowIntegrityWarning --> AddLines
    
    CheckViolations -->|Equity Negative| RequireApproval{Admin Approval Needed?}
    CheckViolations -->|No Violations| SaveChoice{Save As?}
    
    RequireApproval -->|Yes| ShowApprovalModal[Show Admin Approval Modal]
    ShowApprovalModal --> UserConfirm{User Confirms?}
    
    UserConfirm -->|Yes| SavePendingApproval[Save as Pending with requires_approval=TRUE]
    UserConfirm -->|No| AddLines
    
    SaveChoice -->|Pending| SavePending[Save Transaction: Status=Pending]
    SaveChoice -->|Posted| SavePosted[Save Transaction: Status=Posted]
    
    SavePending --> InsertTransaction[Insert into transactions Table]
    SavePosted --> InsertTransaction
    SavePendingApproval --> InsertTransaction
    
    InsertTransaction --> InsertLines[Insert Transaction Lines]
    InsertLines --> CheckStatus{Status?}
    
    CheckStatus -->|Pending| SkipBalance[Don't Update Balances]
    CheckStatus -->|Posted| UpdateBalances[Update Account Balances]
    
    UpdateBalances --> ValidateEquation[Validate Accounting Equation]
    ValidateEquation --> EquationCheck{Assets = Liabilities + Equity?}
    
    EquationCheck -->|No| Rollback[❌ Rollback Transaction]
    EquationCheck -->|Yes| Commit[✅ Commit Transaction]
    
    SkipBalance --> Commit
    Commit --> LogActivity[Log Activity]
    LogActivity --> NotifyUser[Notify User]
    NotifyUser --> Success[✅ Transaction Saved]
    
    Rollback --> ShowError2[Show Equation Error]
    ShowError2 --> AddLines
    
    style BlockTransaction fill:#e74c3c,color:#fff
    style Commit fill:#27ae60,color:#fff
    style SavePendingApproval fill:#f39c12,color:#fff
    style Rollback fill:#e74c3c,color:#fff
```

---

## ✅ ADMIN APPROVAL WORKFLOW

```mermaid
sequenceDiagram
    participant Tenant
    participant System
    participant Admin
    participant Database
    
    Tenant->>System: Create Transaction (Requires Approval)
    System->>System: Validate Transaction
    System->>Database: Save as Pending with requires_approval=TRUE
    Database-->>System: Transaction Saved
    System->>Tenant: Show: "Submitted for Admin Review"
    System->>Admin: Notify: New Transaction Needs Approval
    
    Admin->>System: View Pending Approvals
    System->>Database: Query transactions WHERE requires_approval=TRUE
    Database-->>System: Return Pending Transactions
    System-->>Admin: Display Pending List
    
    Admin->>System: View Transaction Details
    System->>Database: Get Transaction & Lines
    Database-->>System: Transaction Data
    System-->>Admin: Show Full Transaction Details
    
    alt Admin Approves
        Admin->>System: Click Approve
        System->>Database: Update Status = Posted
        System->>Database: Update Account Balances
        System->>Database: Validate Equation
        alt Equation Valid
            Database-->>System: Balances Updated
            System->>Database: Log Approval
            System->>Admin: ✅ Transaction Posted
            System->>Tenant: Notify: Transaction Approved
        else Equation Invalid
            Database-->>System: Rollback
            System-->>Admin: ❌ Cannot Post (Equation Error)
        end
    else Admin Declines
        Admin->>System: Click Decline
        System->>Admin: Ask for Reason
        Admin->>System: Enter Decline Reason
        System->>Database: Update Status = Declined
        System->>Database: Add Decline Reason
        System->>Database: Log Decline
        System->>Admin: ✅ Transaction Declined
        System->>Tenant: Notify: Transaction Declined (with reason)
    end
```

---

## 🔄 VOID TRANSACTION FLOW

```mermaid
graph TB
    Start([Void Transaction Button]) --> CheckStatus{Transaction Status?}
    
    CheckStatus -->|Pending| CannotVoid[❌ Cannot Void Pending]
    CheckStatus -->|Voided| AlreadyVoided[❌ Already Voided]
    CheckStatus -->|Posted| ConfirmVoid{User Confirms Void?}
    
    ConfirmVoid -->|No| Cancel[Cancel Operation]
    ConfirmVoid -->|Yes| CheckCascade{Cascade Void Option?}
    
    CheckCascade -->|Cascade Void| CascadeFlow[Cascade Void All Related]
    CheckCascade -->|Simple Void| SimpleFlow[Void Single Transaction]
    
    SimpleFlow --> ReverseBalances[Reverse Account Balances]
    ReverseBalances --> UpdateStatus[Update Status = Voided]
    UpdateStatus --> LogVoid[Log Void Activity]
    LogVoid --> Success[✅ Transaction Voided]
    
    CascadeFlow --> FindRelated[Find Related Transactions]
    FindRelated --> VoidRelated[Void Each Related Transaction]
    VoidRelated --> ReverseAll[Reverse All Balances]
    ReverseAll --> UpdateAllStatus[Update All to Voided]
    UpdateAllStatus --> LogCascade[Log Cascade Void]
    LogCascade --> SuccessCascade[✅ Cascade Void Complete]
    
    style CannotVoid fill:#e74c3c,color:#fff
    style Success fill:#27ae60,color:#fff
    style SuccessCascade fill:#27ae60,color:#fff
```

---

## 📈 FINANCIAL REPORTS GENERATION

```mermaid
graph TB
    Start([Generate Report]) --> SelectType{Report Type?}
    
    SelectType -->|Balance Sheet| BSFlow[Balance Sheet Flow]
    SelectType -->|Income Statement| ISFlow[Income Statement Flow]
    
    BSFlow --> SelectDate[Select As-Of Date]
    SelectDate --> QueryAssets[Query Asset Accounts]
    QueryAssets --> QueryLiabilities[Query Liability Accounts]
    QueryLiabilities --> QueryEquity[Query Equity Accounts]
    QueryEquity --> CalcRetained[Calculate Retained Earnings]
    CalcRetained --> CheckEquation{Assets = Liab + Equity?}
    
    CheckEquation -->|No| ShowWarning[⚠️ Show Warning: Unbalanced]
    CheckEquation -->|Yes| ShowBalanced[✅ Show: Balanced]
    
    ShowWarning --> RenderBS[Render Balance Sheet]
    ShowBalanced --> RenderBS
    RenderBS --> DisplayBS[Display Report]
    
    ISFlow --> SelectPeriod[Select Period: Start to End Date]
    SelectPeriod --> QueryRevenue[Query Revenue Accounts]
    QueryRevenue --> QueryExpenses[Query Expense Accounts]
    QueryExpenses --> CalcNet[Calculate Net Income]
    CalcNet --> CalcMargin[Calculate Profit Margin]
    CalcMargin --> RenderIS[Render Income Statement]
    RenderIS --> DisplayIS[Display Report]
    
    DisplayBS --> ExportOptions[Export Options]
    DisplayIS --> ExportOptions
    
    ExportOptions --> PrintOption[🖨️ Print Report]
    ExportOptions --> PDFOption[📄 Export to PDF]
    ExportOptions --> ExcelOption[📊 Export to Excel]
    
    style ShowBalanced fill:#27ae60,color:#fff
    style ShowWarning fill:#f39c12,color:#fff
```

---

## 🗄️ DATABASE STRUCTURE FLOW

```mermaid
erDiagram
    users ||--o{ companies : "manages"
    users ||--o{ activity_logs : "generates"
    users ||--o{ transactions : "creates"
    
    companies ||--o{ accounts : "contains"
    companies ||--o{ transactions : "has"
    companies ||--o{ approval_history : "tracks"
    
    accounts ||--o{ transaction_lines : "appears_in"
    account_types ||--o{ accounts : "categorizes"
    
    transactions ||--o{ transaction_lines : "contains"
    transactions ||--o{ approval_history : "requires"
    transaction_statuses ||--o{ transactions : "defines_status"
    
    users {
        int id PK
        string username UK
        string email UK
        string role
        int company_id FK
        string registration_status
        boolean is_active
        timestamp created_at
    }
    
    companies {
        int id PK
        string company_name
        string currency_code
        boolean is_active
        timestamp created_at
    }
    
    accounts {
        int id PK
        int company_id FK
        string account_code
        string account_name
        int account_type_id FK
        decimal current_balance
        boolean is_system_account
        boolean is_active
    }
    
    transactions {
        int id PK
        int company_id FK
        string transaction_number UK
        date transaction_date
        text description
        decimal total_amount
        int status_id FK
        boolean requires_approval
        int created_by FK
        int posted_by FK
        timestamp posted_at
    }
    
    transaction_lines {
        int id PK
        int transaction_id FK
        int account_id FK
        enum line_type
        decimal amount
    }
    
    approval_history {
        int id PK
        int company_id FK
        int transaction_id FK
        int reviewed_by FK
        string action
        text reason
        timestamp reviewed_at
    }
```

---

## 🔒 ACCOUNTING INTEGRITY VALIDATION FLOW

```mermaid
graph TB
    Start([Transaction Validation]) --> CheckBalance[Check Debits = Credits]
    CheckBalance --> BalanceOK{Balanced?}
    
    BalanceOK -->|No| RejectBalance[❌ Reject: Unbalanced]
    BalanceOK -->|Yes| CheckLines[Check Each Line]
    
    CheckLines --> Line1[Line 1: Validate]
    CheckLines --> Line2[Line 2: Validate]
    CheckLines --> LineN[Line N: Validate]
    
    Line1 --> CalcNewBalance1[Calculate New Balance for Account 1]
    Line2 --> CalcNewBalance2[Calculate New Balance for Account 2]
    LineN --> CalcNewBalanceN[Calculate New Balance for Account N]
    
    CalcNewBalance1 --> CheckType1{Account Type?}
    CalcNewBalance2 --> CheckType2{Account Type?}
    CalcNewBalanceN --> CheckTypeN{Account Type?}
    
    CheckType1 -->|Asset| CheckAsset1[Check if Balance < 0]
    CheckType1 -->|Liability| CheckLiab1[Check if Balance < 0]
    CheckType1 -->|Equity| CheckEquity1[Check if Balance < 0]
    CheckType1 -->|Revenue| CheckRev1[Check if Balance < 0]
    CheckType1 -->|Expense| CheckExp1[Check if Balance < 0]
    
    CheckAsset1 --> AssetNegative1{Balance < 0?}
    CheckLiab1 --> LiabNegative1{Balance < 0?}
    CheckEquity1 --> EquityNegative1{Balance < 0?}
    CheckRev1 --> RevNegative1{Balance < 0?}
    CheckExp1 --> ExpNegative1{Balance < 0?}
    
    AssetNegative1 -->|Yes| BlockCritical[❌ BLOCK: Critical Violation]
    LiabNegative1 -->|Yes| BlockCritical
    RevNegative1 -->|Yes| BlockCritical
    ExpNegative1 -->|Yes| BlockCritical
    
    EquityNegative1 -->|Yes| RequireApproval[⚠️ Require Admin Approval]
    
    AssetNegative1 -->|No| PassLine1[✅ Line 1 Valid]
    LiabNegative1 -->|No| PassLine1
    EquityNegative1 -->|No| PassLine1
    RevNegative1 -->|No| PassLine1
    ExpNegative1 -->|No| PassLine1
    
    CheckType2 --> PassLine2[... Line 2 Validation]
    CheckTypeN --> PassLineN[... Line N Validation]
    
    PassLine1 --> AllLinesValid{All Lines Valid?}
    PassLine2 --> AllLinesValid
    PassLineN --> AllLinesValid
    
    AllLinesValid -->|No| RejectViolation[❌ Reject Transaction]
    AllLinesValid -->|Yes, No Approval| AllowSave[✅ Allow Save/Post]
    RequireApproval --> SavePendingApproval[💾 Save as Pending Approval]
    
    AllowSave --> PostTransaction[Post Transaction]
    PostTransaction --> UpdateBalances[Update All Account Balances]
    UpdateBalances --> ValidateEquation[Validate: Assets = Liab + Equity]
    
    ValidateEquation --> EquationValid{Equation Valid?}
    EquationValid -->|No| RollbackDB[❌ Rollback Database]
    EquationValid -->|Yes| CommitDB[✅ Commit Transaction]
    
    SavePendingApproval --> WaitAdmin[⏳ Wait for Admin Review]
    
    style BlockCritical fill:#e74c3c,color:#fff
    style CommitDB fill:#27ae60,color:#fff
    style RequireApproval fill:#f39c12,color:#fff
    style RollbackDB fill:#e74c3c,color:#fff
```

---

## 🎨 USER INTERFACE FLOW

```mermaid
graph TB
    Start([User Logs In]) --> Dashboard[Dashboard Page]
    
    Dashboard --> Nav{Navigation Menu}
    
    Nav -->|Accounts| AccountsPage[Accounts Page]
    Nav -->|Transactions| TransPage[Transactions Page]
    Nav -->|Reports| ReportsPage[Reports Page]
    Nav -->|Company| CompanyPage[Company Page]
    Nav -->|Settings| SettingsPage[Settings Page]
    
    AccountsPage --> ViewAccounts[View Account List]
    ViewAccounts --> AccountActions{Action?}
    AccountActions -->|Create| CreateAccount[Create Account Modal]
    AccountActions -->|Edit| EditAccount[Edit Account Modal]
    AccountActions -->|Deactivate| DeactivateAccount[Deactivate Confirmation]
    
    TransPage --> ViewTrans[View Transaction List]
    ViewTrans --> TransActions{Action?}
    TransActions -->|Create| CreateTrans[Create Transaction Modal]
    TransActions -->|View| ViewDetails[View Details Modal]
    TransActions -->|Edit| EditTrans[Edit Transaction Modal]
    TransActions -->|Post| PostTrans[Post Confirmation]
    TransActions -->|Void| VoidTrans[Void Confirmation]
    TransActions -->|Delete| DeleteTrans[Delete Confirmation]
    
    ReportsPage --> ReportControls[Report Controls]
    ReportControls --> SelectReport{Select Report Type}
    SelectReport -->|Balance Sheet| BSReport[Balance Sheet Display]
    SelectReport -->|Income Statement| ISReport[Income Statement Display]
    
    BSReport --> ExportBS[Export Options]
    ISReport --> ExportIS[Export Options]
    
    CompanyPage --> ViewCompany[View Company Details]
    ViewCompany --> EditCompany[Edit Company Info]
    
    SettingsPage --> UserSettings[User Settings]
    UserSettings --> ChangePassword[Change Password]
    UserSettings --> UpdateProfile[Update Profile]
    
    style Dashboard fill:#3498db,color:#fff
    style CreateTrans fill:#27ae60,color:#fff
    style ReportsPage fill:#9b59b6,color:#fff
```

---

## 🚀 SYSTEM STARTUP & INITIALIZATION

```mermaid
graph TB
    Start([System Startup]) --> CheckDocker{Docker Running?}
    
    CheckDocker -->|No| StartDocker[Start Docker Compose]
    CheckDocker -->|Yes| ContainersCheck[Check Containers]
    
    StartDocker --> ContainersCheck
    ContainersCheck --> Nginx[Nginx Container: Port 8080]
    ContainersCheck --> PHP[PHP-FPM Container]
    ContainersCheck --> MySQL[MySQL Container: Port 3306]
    
    Nginx --> WebServer[Web Server Ready]
    PHP --> PHPReady[PHP 8.2 Ready]
    MySQL --> DBCheck{Database Exists?}
    
    DBCheck -->|No| CreateDB[Run init.sql]
    DBCheck -->|Yes| CheckSchema{Schema Valid?}
    
    CreateDB --> CheckSchema
    CheckSchema -->|No| RunMigrations[Run Migrations]
    CheckSchema -->|Yes| SeedCheck{Data Seeded?}
    
    RunMigrations --> SeedCheck
    SeedCheck -->|No| SeedAdmin[Seed Admin User]
    SeedCheck -->|Yes| SystemReady[✅ System Ready]
    
    SeedAdmin --> CreateAdmin[Create Admin: username=admin, password=admin]
    CreateAdmin --> CreateStatuses[Create Transaction Statuses]
    CreateStatuses --> CreateTypes[Create Account Types]
    CreateTypes --> SystemReady
    
    SystemReady --> AccessURL[Access: http://localhost:8080]
    
    style SystemReady fill:#27ae60,color:#fff
    style AccessURL fill:#3498db,color:#fff
```

---

## 📋 COMPLETE FEATURE MAP

### Admin Features
- ✅ User Management (Approve/Decline Registrations)
- ✅ Company Management (View/Edit/Activate/Deactivate)
- ✅ Transaction Approval System
- ✅ Global Transaction Monitoring
- ✅ Cross-Company Reports
- ✅ Activity Log Monitoring
- ✅ System Statistics Dashboard
- ✅ Account Management (All Companies)

### Tenant Features
- ✅ Account Creation (Normal & External)
- ✅ Double-Entry Transaction Recording
- ✅ Transaction Management (Pending/Posted)
- ✅ Financial Reports (Balance Sheet, Income Statement)
- ✅ Company Profile Management
- ✅ User Settings & Profile
- ✅ Activity History

### System Features
- ✅ Double-Entry Bookkeeping Validation
- ✅ Accounting Equation Enforcement
- ✅ Negative Balance Prevention
- ✅ Admin Approval Workflow (Rare Scenarios)
- ✅ External Source Account Simulation
- ✅ Transaction Void & Cascade
- ✅ Audit Trail & Activity Logs
- ✅ Role-Based Access Control
- ✅ Session Management
- ✅ Real-Time Notifications

---

## 🎯 KEY SYSTEM RULES

### Accounting Principles Enforced
1. **Debits Must Equal Credits** - Every transaction must balance
2. **Assets = Liabilities + Equity** - Equation must hold after every transaction
3. **No Negative Balances** - Assets, Liabilities, Revenue, Expenses cannot go negative
4. **Equity Can Go Negative** - But requires admin approval (rare scenario)
5. **Posted Transactions are Immutable** - Can only be voided, not edited or deleted
6. **Void Creates Reversal** - All account balances are reversed
7. **Double-Entry Required** - Minimum 2 lines per transaction
8. **External Accounts Unlimited** - Simulate outside world with unlimited balance

### User Rules
1. **Admin can do everything** - Full system access
2. **Tenant can only access their company** - Multi-tenant isolation
3. **Pending registration users cannot access system** - Must be approved
4. **Declined users cannot re-register** - Must contact admin
5. **Deactivated users cannot login** - Account suspended
6. **Session expires on inactivity** - Security measure

### Transaction Rules
1. **Pending transactions can be edited/deleted** - Draft mode
2. **Posted transactions are permanent** - Locked forever
3. **Voided transactions show in history** - Audit trail
4. **Same account cannot be used twice** - Prevents confusion
5. **Admin approval required for rare scenarios** - Negative equity
6. **Balance validation happens on save** - Real-time check

---

## 📊 SYSTEM METRICS

- **Total Database Tables**: 12
- **Total API Endpoints**: 50+
- **User Roles**: 2 (Admin, Tenant)
- **Account Types**: 5 (Asset, Liability, Equity, Revenue, Expense)
- **Transaction Statuses**: 3 (Pending, Posted, Voided)
- **Registration Statuses**: 3 (Pending, Approved, Declined)

---

## 🎓 ACCOUNTING EDUCATION FEATURES

The system includes comprehensive educational materials:

- **Accounting Rules Documentation** (`/docs/accounting-rules.html`)
- **External World Simulator Guide** (`/external-world-simulator.html`)
- **Interactive Help in Transaction Forms**
- **Real-time Balance Impact Indicators**
- **Contextual Warnings & Information**
- **Professional Error Messages with Explanations**

---

## ✨ CONCLUSION

This comprehensive flowchart covers the entire system architecture, from user registration to financial reporting. The system enforces strict accounting principles while providing flexibility for complex scenarios through admin approval workflows.

**Built with:** PHP 8.2, MySQL 8.0, Nginx, Docker, Vanilla JavaScript
**Architecture:** Multi-tenant, Role-based Access Control, RESTful API
**Accounting:** Double-Entry Bookkeeping, GAAP Compliant

---

**Document Version:** 1.0  
**Last Updated:** November 17, 2025  
**System Status:** ✅ Production Ready

