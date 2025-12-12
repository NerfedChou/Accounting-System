# Company Management - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Company Management bounded context.

## Aggregate: Company

**Aggregate Root:** Company entity

### Entities

#### Company
```php
class Company {
    private CompanyId $companyId;
    private string $companyName;
    private string $legalName;
    private TaxIdentifier $taxId;
    private Address $address;
    private Currency $defaultCurrency;
    private FiscalYear $fiscalYear;
    private CompanyStatus $status;
    private CompanySettings $settings;
    private DateTime $createdAt;
    private ?DateTime $activatedAt;
    private ?DateTime $deactivatedAt;
}
```

#### CompanySettings
```php
class CompanySettings {
    private bool $requireApprovalForNegativeEquity;
    private int $transactionApprovalThreshold;   // Amount above which needs approval
    private bool $allowBackdatedTransactions;
    private int $maxBackdateDays;                // How far back transactions can be dated
    private bool $autoPostTransactions;          // Skip pending status
    private string $defaultTimezone;
    private string $dateFormat;
    private string $numberFormat;
}
```

### Value Objects

#### CompanyId
```php
final class CompanyId {
    private string $value;  // UUID v4

    public static function generate(): self;
    public static function fromString(string $value): self;
    public function value(): string;
    public function equals(CompanyId $other): bool;
}
```

#### TaxIdentifier
```php
final class TaxIdentifier {
    private string $value;
    private TaxIdType $type;

    // Types: TIN, EIN, VAT, GST, etc.
    // Validation based on type and country
}
```

#### Address
```php
final class Address {
    private string $street1;
    private ?string $street2;
    private string $city;
    private string $state;
    private string $postalCode;
    private Country $country;

    public function toString(): string;
    public function equals(Address $other): bool;
}
```

#### Currency
```php
final class Currency {
    private string $code;     // ISO 4217: USD, EUR, PHP, etc.
    private string $symbol;   // $, €, ₱
    private int $decimals;    // Usually 2

    public static function fromCode(string $code): self;
    public function code(): string;
    public function symbol(): string;
    public function format(float $amount): string;
}
```

#### FiscalYear
```php
final class FiscalYear {
    private int $startMonth;  // 1-12 (1 = January)
    private int $startDay;    // 1-31

    public static function calendar(): self;  // Jan 1
    public function getStartDate(int $year): DateTime;
    public function getEndDate(int $year): DateTime;
    public function getCurrentPeriod(): FiscalPeriod;
}
```

#### CompanyStatus
```php
enum CompanyStatus: string {
    case PENDING = 'pending';      // Just created, awaiting setup
    case ACTIVE = 'active';        // Fully operational
    case SUSPENDED = 'suspended';  // Temporarily disabled
    case DEACTIVATED = 'deactivated';  // Permanently disabled

    public function canTransact(): bool;
    public function canBeActivated(): bool;
    public function canBeDeactivated(): bool;
}
```

---

## Domain Services

### CompanyActivationService
```php
interface CompanyActivationService {
    /**
     * Activate a pending company after setup is complete
     */
    public function activate(CompanyId $companyId): void;

    /**
     * Check if company meets activation requirements
     */
    public function canActivate(CompanyId $companyId): ActivationCheckResult;
}
```

### CompanyDeactivationService
```php
interface CompanyDeactivationService {
    /**
     * Deactivate company and cascade to users
     */
    public function deactivate(
        CompanyId $companyId,
        string $reason,
        UserId $deactivatedBy
    ): void;

    /**
     * Check if company can be safely deactivated
     */
    public function canDeactivate(CompanyId $companyId): DeactivationCheckResult;
}
```

---

## Repository Interface

```php
interface CompanyRepositoryInterface {
    public function save(Company $company): void;

    public function findById(CompanyId $id): ?Company;

    public function findByName(string $name): ?Company;

    public function findByTaxId(TaxIdentifier $taxId): ?Company;

    public function findAll(): array;

    public function findActive(): array;

    public function findPending(): array;

    public function existsByTaxId(TaxIdentifier $taxId): bool;

    public function delete(CompanyId $id): void;
}
```

---

## Domain Events

### CompanyCreated
```json
{
  "eventId": "uuid",
  "eventType": "CompanyCreated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "companyId": "uuid",
    "companyName": "string",
    "legalName": "string",
    "taxId": "string",
    "currency": "PHP",
    "fiscalYearStart": "01-01",
    "createdBy": "uuid"
  }
}
```

### CompanyActivated
```json
{
  "eventId": "uuid",
  "eventType": "CompanyActivated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "companyId": "uuid",
    "activatedBy": "uuid"
  }
}
```

### CompanyDeactivated
```json
{
  "eventId": "uuid",
  "eventType": "CompanyDeactivated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "companyId": "uuid",
    "reason": "string",
    "deactivatedBy": "uuid"
  }
}
```

### CompanySettingsUpdated
```json
{
  "eventId": "uuid",
  "eventType": "CompanySettingsUpdated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "companyId": "uuid",
    "previousSettings": {},
    "newSettings": {},
    "updatedBy": "uuid"
  }
}
```

### CompanyAddressChanged
```json
{
  "eventId": "uuid",
  "eventType": "CompanyAddressChanged",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "companyId": "uuid",
    "previousAddress": {},
    "newAddress": {},
    "changedBy": "uuid"
  }
}
```

---

## Business Rules

### BR-CM-001: Tax ID Uniqueness
- Tax identifier MUST be unique across all companies
- Prevents duplicate company registration

### BR-CM-002: Company Name Requirements
- Company name MUST be 3-255 characters
- Company name MUST be unique (case-insensitive)
- Legal name can be different from display name

### BR-CM-003: Currency Immutability
- Default currency cannot be changed after company has transactions
- Prevents currency conversion complications

### BR-CM-004: Fiscal Year Immutability
- Fiscal year cannot be changed after first fiscal period closes
- Can only be set during company setup

### BR-CM-005: Activation Requirements
Before a company can be activated:
- Must have at least one admin user
- Must have chart of accounts initialized
- Must have valid tax identifier
- Must have complete address

### BR-CM-006: Deactivation Cascade
When company is deactivated:
- All associated users are deactivated
- No new transactions can be created
- Existing data remains for audit purposes
- Company can be reactivated by system admin

### BR-CM-007: Status Transitions
```
PENDING → ACTIVE (activation)
ACTIVE → SUSPENDED (temporary disable)
ACTIVE → DEACTIVATED (permanent disable)
SUSPENDED → ACTIVE (reactivation)
SUSPENDED → DEACTIVATED (permanent disable)
DEACTIVATED → (no transitions, terminal state)
```

### BR-CM-008: Settings Constraints
- `transactionApprovalThreshold` must be >= 0
- `maxBackdateDays` must be 0-365
- Auto-post disabled if approval threshold is set

---

## Multi-Tenancy Model

### Data Isolation
- All data is isolated by `company_id`
- Users belong to exactly one company (except system admins)
- Cross-company queries are prohibited at repository level
- All queries include `WHERE company_id = ?`

### Tenant Context
```php
interface TenantContext {
    public function getCurrentCompanyId(): CompanyId;
    public function setCurrentCompanyId(CompanyId $companyId): void;
    public function isSystemAdmin(): bool;
}
```

### Query Scoping
```php
// Automatic scoping in repositories
class MySQLAccountRepository implements AccountRepositoryInterface {
    private TenantContext $tenantContext;

    public function findAll(): array {
        $companyId = $this->tenantContext->getCurrentCompanyId();
        return $this->query(
            "SELECT * FROM accounts WHERE company_id = ?",
            [$companyId->value()]
        );
    }
}
```

---

## Use Cases

### UC-CM-001: Create Company
**Actor:** System Admin
**Preconditions:** Admin authenticated
**Flow:**
1. Validate company name uniqueness
2. Validate tax ID uniqueness
3. Validate currency code
4. Create Company entity with PENDING status
5. Persist company
6. Publish CompanyCreated event
7. Chart of Accounts listens and initializes accounts

### UC-CM-002: Activate Company
**Actor:** System Admin
**Preconditions:** Company in PENDING status
**Flow:**
1. Verify activation requirements met
2. Check admin user exists
3. Check chart of accounts initialized
4. Update status to ACTIVE
5. Set activatedAt timestamp
6. Publish CompanyActivated event

### UC-CM-003: Update Company Settings
**Actor:** Admin
**Preconditions:** Company ACTIVE
**Flow:**
1. Validate setting values
2. Apply business rule constraints
3. Update settings
4. Publish CompanySettingsUpdated event

### UC-CM-004: Deactivate Company
**Actor:** System Admin
**Preconditions:** Company not already deactivated
**Flow:**
1. Verify no pending transactions
2. Update status to DEACTIVATED
3. Cascade deactivation to users
4. Set deactivatedAt timestamp
5. Publish CompanyDeactivated event

---

## Integration Points

### Consumes Events:
- `UserRegistered` → Associate user with company (if companyId provided)

### Publishes Events:
- `CompanyCreated` → Triggers Chart of Accounts initialization
- `CompanyActivated` → Notifies other contexts company is ready
- `CompanyDeactivated` → Triggers cascade to Identity context

### Dependencies:
- Identity & Access Management (for user associations)

---

## Database Schema (Reference)

```sql
CREATE TABLE companies (
    id UUID PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    legal_name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(50) NOT NULL UNIQUE,
    tax_id_type VARCHAR(20) NOT NULL,

    -- Address
    street1 VARCHAR(255) NOT NULL,
    street2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country_code CHAR(2) NOT NULL,

    -- Configuration
    default_currency CHAR(3) NOT NULL DEFAULT 'PHP',
    fiscal_year_start_month INT NOT NULL DEFAULT 1,
    fiscal_year_start_day INT NOT NULL DEFAULT 1,
    default_timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Manila',

    -- Settings
    require_approval_negative_equity BOOLEAN DEFAULT TRUE,
    transaction_approval_threshold DECIMAL(15,2) DEFAULT 0,
    allow_backdated_transactions BOOLEAN DEFAULT TRUE,
    max_backdate_days INT DEFAULT 30,
    auto_post_transactions BOOLEAN DEFAULT FALSE,

    -- Status
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP,
    deactivated_at TIMESTAMP,

    CONSTRAINT valid_status CHECK (
        status IN ('pending', 'active', 'suspended', 'deactivated')
    ),
    CONSTRAINT valid_fiscal_month CHECK (
        fiscal_year_start_month BETWEEN 1 AND 12
    ),
    CONSTRAINT valid_fiscal_day CHECK (
        fiscal_year_start_day BETWEEN 1 AND 31
    )
);

CREATE INDEX idx_companies_status ON companies(status);
CREATE INDEX idx_companies_tax_id ON companies(tax_id);
```

---

## Company Settings Reference

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| requireApprovalForNegativeEquity | bool | true | Require admin approval for transactions causing negative equity |
| transactionApprovalThreshold | decimal | 0 | Transactions above this amount need approval (0 = disabled) |
| allowBackdatedTransactions | bool | true | Allow transactions dated in the past |
| maxBackdateDays | int | 30 | Maximum days a transaction can be backdated |
| autoPostTransactions | bool | false | Skip pending status, post immediately |
| defaultTimezone | string | Asia/Manila | Timezone for date/time operations |
| dateFormat | string | Y-m-d | PHP date format for display |
| numberFormat | string | #,##0.00 | Number formatting pattern |
