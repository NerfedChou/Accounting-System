# Chart of Accounts - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Chart of Accounts bounded context.

## Aggregate: Account

**Aggregate Root:** Account entity

### Entities

#### Account
```php
class Account {
    private AccountId $accountId;
    private CompanyId $companyId;
    private AccountCode $accountCode;      // e.g., "1000", "2100"
    private string $accountName;           // e.g., "Cash", "Accounts Payable"
    private AccountType $accountType;      // Asset, Liability, Equity, Revenue, Expense
    private NormalBalance $normalBalance;  // Derived from type
    private ?AccountId $parentAccountId;   // For hierarchical structure
    private Money $openingBalance;
    private bool $isActive;
    private bool $isSystemAccount;         // Cannot be deleted/modified
    private DateTime $createdAt;
    private ?DateTime $deactivatedAt;
}
```

#### AccountHierarchy
```php
class AccountHierarchy {
    private AccountId $accountId;
    private int $level;                    // 0 = root, 1 = child, etc.
    private string $path;                  // "1000.1010.1011" for hierarchy
    private array $childAccountIds;
}
```

### Value Objects

#### AccountId
```php
final class AccountId {
    private string $value;  // UUID v4

    public static function generate(): self;
    public static function fromString(string $value): self;
    public function value(): string;
    public function equals(AccountId $other): bool;
}
```

#### AccountCode
```php
final class AccountCode {
    private string $value;  // 4-digit code: "1000"-"9999"

    public static function fromString(string $code): self;
    public function value(): string;
    public function getTypePrefix(): int;  // First digit determines type
    public function isSubAccountOf(AccountCode $parent): bool;

    // Validation: Must be 4 digits, first digit 1-9
    // 1xxx = Assets
    // 2xxx = Liabilities
    // 3xxx = Equity
    // 4xxx = Revenue
    // 5xxx = Expenses
}
```

#### AccountType
```php
enum AccountType: string {
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    public function getNormalBalance(): NormalBalance;
    public function canHaveNegativeBalance(): bool;
    public function getCodeRange(): array;  // [min, max]
}
```

#### NormalBalance
```php
enum NormalBalance: string {
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public static function forAccountType(AccountType $type): self;
}
```

---

## Domain Services

### AccountCodeGenerator
```php
interface AccountCodeGenerator {
    /**
     * Generate next available account code for given type
     */
    public function generateNextCode(
        CompanyId $companyId,
        AccountType $type
    ): AccountCode;

    /**
     * Generate sub-account code under parent
     */
    public function generateSubAccountCode(
        CompanyId $companyId,
        AccountCode $parentCode
    ): AccountCode;
}
```

### DefaultChartOfAccountsInitializer
```php
interface DefaultChartOfAccountsInitializer {
    /**
     * Create standard chart of accounts for new company
     */
    public function initializeForCompany(CompanyId $companyId): array;
}
```

---

## Repository Interface

```php
interface AccountRepositoryInterface {
    public function save(Account $account): void;

    public function findById(AccountId $id): ?Account;

    public function findByCode(
        CompanyId $companyId,
        AccountCode $code
    ): ?Account;

    public function findByCompany(CompanyId $companyId): array;

    public function findByType(
        CompanyId $companyId,
        AccountType $type
    ): array;

    public function findActiveAccounts(CompanyId $companyId): array;

    public function findChildren(AccountId $parentId): array;

    public function existsByCode(
        CompanyId $companyId,
        AccountCode $code
    ): bool;

    public function delete(AccountId $id): void;
}
```

---

## Domain Events

### AccountCreated
```json
{
  "eventId": "uuid",
  "eventType": "AccountCreated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "accountId": "uuid",
    "companyId": "uuid",
    "accountCode": "1000",
    "accountName": "Cash",
    "accountType": "asset",
    "normalBalance": "debit",
    "parentAccountId": "uuid|null",
    "openingBalance": "0.00",
    "isSystemAccount": false,
    "createdBy": "uuid"
  }
}
```

### AccountActivated
```json
{
  "eventId": "uuid",
  "eventType": "AccountActivated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "accountId": "uuid",
    "activatedBy": "uuid"
  }
}
```

### AccountDeactivated
```json
{
  "eventId": "uuid",
  "eventType": "AccountDeactivated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "accountId": "uuid",
    "reason": "string",
    "deactivatedBy": "uuid"
  }
}
```

### AccountRenamed
```json
{
  "eventId": "uuid",
  "eventType": "AccountRenamed",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "accountId": "uuid",
    "previousName": "string",
    "newName": "string",
    "renamedBy": "uuid"
  }
}
```

---

## Business Rules

### BR-COA-001: Account Code Uniqueness
- Account codes MUST be unique within a company
- Different companies CAN have the same account codes

### BR-COA-002: Account Type from Code
- Account type is determined by first digit of code:
  - 1xxx → Asset
  - 2xxx → Liability
  - 3xxx → Equity
  - 4xxx → Revenue
  - 5xxx → Expense

### BR-COA-003: Normal Balance Derivation
- Normal balance is derived from account type:
  - Asset → Debit
  - Liability → Credit
  - Equity → Credit
  - Revenue → Credit
  - Expense → Debit

### BR-COA-004: Account Deactivation Rules
- Cannot deactivate accounts with non-zero balance
- Cannot deactivate system accounts (isSystemAccount = true)
- Cannot deactivate parent accounts with active children

### BR-COA-005: Account Deletion Rules
- Cannot delete system accounts
- Cannot delete accounts with transaction history
- Cannot delete parent accounts with children

### BR-COA-006: Account Hierarchy
- Maximum hierarchy depth: 4 levels
- Child accounts must have codes starting with parent code prefix
- Example: Parent "1000" → Children "1001", "1002", etc.

### BR-COA-007: Opening Balance
- Opening balance must be provided at account creation
- Opening balance creates implicit system transaction
- Opening balance must maintain accounting equation

---

## Default Chart of Accounts Template

```yaml
assets:
  - code: "1000"
    name: "Cash"
    system: true
  - code: "1100"
    name: "Accounts Receivable"
    system: true
  - code: "1200"
    name: "Inventory"
    system: false
  - code: "1500"
    name: "Prepaid Expenses"
    system: false
  - code: "1700"
    name: "Fixed Assets"
    system: false
  - code: "1800"
    name: "Accumulated Depreciation"
    system: false

liabilities:
  - code: "2000"
    name: "Accounts Payable"
    system: true
  - code: "2100"
    name: "Accrued Liabilities"
    system: false
  - code: "2200"
    name: "Unearned Revenue"
    system: false
  - code: "2500"
    name: "Notes Payable"
    system: false
  - code: "2700"
    name: "Long-term Debt"
    system: false

equity:
  - code: "3000"
    name: "Owner's Capital"
    system: true
  - code: "3100"
    name: "Owner's Drawings"
    system: true
  - code: "3200"
    name: "Retained Earnings"
    system: true

revenue:
  - code: "4000"
    name: "Sales Revenue"
    system: true
  - code: "4100"
    name: "Service Revenue"
    system: true
  - code: "4200"
    name: "Interest Income"
    system: false
  - code: "4900"
    name: "Other Income"
    system: false

expenses:
  - code: "5000"
    name: "Cost of Goods Sold"
    system: true
  - code: "5100"
    name: "Salaries Expense"
    system: false
  - code: "5200"
    name: "Rent Expense"
    system: false
  - code: "5300"
    name: "Utilities Expense"
    system: false
  - code: "5400"
    name: "Depreciation Expense"
    system: false
  - code: "5500"
    name: "Insurance Expense"
    system: false
  - code: "5900"
    name: "Miscellaneous Expense"
    system: false
```

---

## Use Cases

### UC-COA-001: Create Account
**Actor:** Admin or Tenant
**Preconditions:** User authenticated, company exists
**Flow:**
1. Validate account code format
2. Check code uniqueness within company
3. Derive account type from code
4. Derive normal balance from type
5. Create Account entity
6. Persist account
7. Publish AccountCreated event

### UC-COA-002: Initialize Company Accounts
**Actor:** System (triggered by CompanyCreated event)
**Preconditions:** Company just created
**Flow:**
1. Receive CompanyCreated event
2. Load default chart of accounts template
3. Create each account for the company
4. Mark system accounts appropriately
5. Publish AccountCreated events for each

### UC-COA-003: Deactivate Account
**Actor:** Admin
**Preconditions:** Account exists, balance is zero
**Flow:**
1. Validate account exists
2. Check account balance is zero
3. Check no active child accounts
4. Check not a system account
5. Mark account as inactive
6. Publish AccountDeactivated event

---

## Integration Points

### Consumes Events:
- `CompanyCreated` → Initialize default chart of accounts

### Publishes Events:
- `AccountCreated` → Consumed by Transaction Processing
- `AccountDeactivated` → Consumed by Transaction Processing
- `AccountActivated` → Consumed by Transaction Processing

### Dependencies:
- Company Management (for CompanyId validation)

---

## Database Schema (Reference)

```sql
CREATE TABLE accounts (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    account_code VARCHAR(10) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type VARCHAR(20) NOT NULL,
    normal_balance VARCHAR(10) NOT NULL,
    parent_account_id UUID REFERENCES accounts(id),
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    is_system_account BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP,

    UNIQUE(company_id, account_code),

    CONSTRAINT valid_account_type CHECK (
        account_type IN ('asset', 'liability', 'equity', 'revenue', 'expense')
    ),
    CONSTRAINT valid_normal_balance CHECK (
        normal_balance IN ('debit', 'credit')
    )
);

CREATE INDEX idx_accounts_company ON accounts(company_id);
CREATE INDEX idx_accounts_type ON accounts(company_id, account_type);
CREATE INDEX idx_accounts_parent ON accounts(parent_account_id);
CREATE INDEX idx_accounts_active ON accounts(company_id, is_active);
```
