# Transaction Processing - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Transaction Processing bounded context.

## Aggregate: Transaction

**Aggregate Root:** Transaction entity

### Entities

#### Transaction
```php
class Transaction {
    private TransactionId $transactionId;
    private CompanyId $companyId;
    private string $transactionNumber;     // Auto-generated: TXN-XXXXXX
    private TransactionDate $transactionDate;
    private string $description;
    private array $lines;                  // TransactionLine[]
    private TransactionStatus $status;
    private Money $totalAmount;
    private bool $requiresApproval;

    private UserId $createdBy;
    private DateTime $createdAt;
    private ?UserId $postedBy;
    private ?DateTime $postedAt;
    private ?UserId $voidedBy;
    private ?DateTime $voidedAt;
    private ?string $voidReason;

    public function addLine(TransactionLine $line): void;
    public function removeLine(LineId $lineId): void;
    public function validate(): ValidationResult;
    public function post(UserId $postedBy): void;
    public function void(UserId $voidedBy, string $reason): void;
    public function getTotalDebits(): Money;
    public function getTotalCredits(): Money;
    public function isBalanced(): bool;
}
```

#### TransactionLine
```php
class TransactionLine {
    private LineId $lineId;
    private TransactionId $transactionId;
    private AccountId $accountId;
    private LineType $lineType;
    private Money $amount;
    private int $lineOrder;
    private DateTime $createdAt;

    public function getBalanceChange(NormalBalance $normalBalance): Money;
}
```

### Value Objects

#### TransactionId
```php
final class TransactionId {
    private string $value;  // UUID v4

    public static function generate(): self;
    public static function fromString(string $value): self;
    public function value(): string;
    public function equals(TransactionId $other): bool;
}
```

#### LineId
```php
final class LineId {
    private string $value;  // UUID v4
}
```

#### TransactionDate
```php
final class TransactionDate {
    private DateTimeImmutable $value;

    public static function fromString(string $date): self;
    public static function today(): self;
    public function value(): DateTimeImmutable;
    public function isInFuture(): bool;
    public function daysFromToday(): int;
    public function format(string $format = 'Y-m-d'): string;
}
```

#### LineType
```php
enum LineType: string {
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function opposite(): self;
    public function matches(NormalBalance $normalBalance): bool;
}
```

#### TransactionStatus
```php
enum TransactionStatus: string {
    case PENDING = 'pending';
    case POSTED = 'posted';
    case VOIDED = 'voided';

    public function canEdit(): bool;
    public function canPost(): bool;
    public function canVoid(): bool;
    public function canTransitionTo(TransactionStatus $newStatus): bool;
}
```

#### Money
```php
final class Money {
    private float $amount;      // Stored as cents internally
    private Currency $currency;

    public static function fromFloat(float $amount, Currency $currency = null): self;
    public static function zero(Currency $currency = null): self;
    public function add(Money $other): self;
    public function subtract(Money $other): self;
    public function multiply(float $multiplier): self;
    public function negate(): self;
    public function isPositive(): bool;
    public function isNegative(): bool;
    public function isZero(): bool;
    public function toFloat(): float;
    public function equals(Money $other): bool;
    public function format(): string;
}
```

#### ValidationResult
```php
final class ValidationResult {
    private bool $isValid;
    private array $errors;      // ValidationError[]
    private array $warnings;    // ValidationWarning[]

    public static function valid(string $message = ''): self;
    public static function invalid(string $message): self;
    public function addError(string $field, string $message): self;
    public function addWarning(string $field, string $message): self;
    public function merge(ValidationResult $other): self;
    public function isValid(): bool;
    public function getErrors(): array;
}
```

---

## Domain Services

### TransactionValidator
```php
interface TransactionValidator {
    /**
     * Validate complete transaction
     */
    public function validate(Transaction $transaction): ValidationResult;

    /**
     * Validate double-entry rule (debits = credits)
     */
    public function validateDoubleEntry(array $lines): ValidationResult;

    /**
     * Check if transaction would cause negative balances
     */
    public function validateBalances(
        Transaction $transaction,
        array $accountBalances
    ): ValidationResult;

    /**
     * Validate accounting equation integrity
     */
    public function validateAccountingEquation(
        Transaction $transaction,
        array $currentTotalsByType
    ): ValidationResult;
}
```

### BalanceCalculator
```php
interface BalanceCalculator {
    /**
     * Calculate balance change for a transaction line
     */
    public function calculateBalanceChange(
        NormalBalance $normalBalance,
        LineType $lineType,
        Money $amount
    ): Money;

    /**
     * Calculate projected balances after transaction
     */
    public function calculateProjectedBalances(
        Transaction $transaction,
        array $currentBalances
    ): array;
}
```

### TransactionNumberGenerator
```php
interface TransactionNumberGenerator {
    /**
     * Generate unique transaction number for company
     * Format: TXN-YYYYMM-XXXXX
     */
    public function generateNextNumber(CompanyId $companyId): string;
}
```

### TransactionPostingService
```php
interface TransactionPostingService {
    /**
     * Post transaction (validate and apply to ledger)
     */
    public function post(
        TransactionId $transactionId,
        UserId $postedBy
    ): PostingResult;

    /**
     * Void a posted transaction
     */
    public function void(
        TransactionId $transactionId,
        UserId $voidedBy,
        string $reason
    ): VoidResult;
}
```

---

## Repository Interface

```php
interface TransactionRepositoryInterface {
    public function save(Transaction $transaction): void;

    public function findById(TransactionId $id): ?Transaction;

    public function findByNumber(
        CompanyId $companyId,
        string $transactionNumber
    ): ?Transaction;

    public function findByCompany(
        CompanyId $companyId,
        ?TransactionStatus $status = null
    ): array;

    public function findByDateRange(
        CompanyId $companyId,
        TransactionDate $from,
        TransactionDate $to
    ): array;

    public function findByAccount(
        AccountId $accountId,
        ?TransactionStatus $status = null
    ): array;

    public function findPendingApproval(CompanyId $companyId): array;

    public function getNextTransactionNumber(CompanyId $companyId): string;

    public function countByStatus(
        CompanyId $companyId,
        TransactionStatus $status
    ): int;
}

interface TransactionLineRepositoryInterface {
    public function save(TransactionLine $line): void;

    public function findByTransaction(TransactionId $transactionId): array;

    public function findByAccount(AccountId $accountId): array;

    public function deleteByTransaction(TransactionId $transactionId): void;
}
```

---

## Domain Events

### TransactionCreated
```json
{
  "eventId": "uuid",
  "eventType": "TransactionCreated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "transactionId": "uuid",
    "companyId": "uuid",
    "transactionNumber": "TXN-202512-00001",
    "transactionDate": "2025-12-12",
    "description": "string",
    "totalAmount": "1000.00",
    "lines": [
      {
        "lineId": "uuid",
        "accountId": "uuid",
        "lineType": "debit|credit",
        "amount": "1000.00"
      }
    ],
    "createdBy": "uuid"
  }
}
```

### TransactionValidated
```json
{
  "eventId": "uuid",
  "eventType": "TransactionValidated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "transactionId": "uuid",
    "isValid": true,
    "validationResults": {
      "debitsEqualCredits": true,
      "noNegativeBalances": true,
      "accountingEquationMaintained": true
    },
    "requiresApproval": false
  }
}
```

### TransactionApprovalRequired
```json
{
  "eventId": "uuid",
  "eventType": "TransactionApprovalRequired",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "transactionId": "uuid",
    "reason": "negative_equity",
    "details": {
      "accountId": "uuid",
      "accountName": "Owner's Capital",
      "currentBalance": "5000.00",
      "projectedBalance": "-500.00"
    }
  }
}
```

### TransactionPosted
```json
{
  "eventId": "uuid",
  "eventType": "TransactionPosted",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "transactionId": "uuid",
    "companyId": "uuid",
    "transactionNumber": "TXN-202512-00001",
    "postedBy": "uuid",
    "postedAt": "2025-12-12T10:30:00Z",
    "balanceChanges": [
      {
        "accountId": "uuid",
        "previousBalance": "1000.00",
        "newBalance": "2000.00",
        "change": "1000.00"
      }
    ]
  }
}
```

### TransactionVoided
```json
{
  "eventId": "uuid",
  "eventType": "TransactionVoided",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "transactionId": "uuid",
    "transactionNumber": "TXN-202512-00001",
    "voidedBy": "uuid",
    "voidReason": "string",
    "balanceReversals": [
      {
        "accountId": "uuid",
        "previousBalance": "2000.00",
        "newBalance": "1000.00",
        "change": "-1000.00"
      }
    ]
  }
}
```

---

## Business Rules

### BR-TXN-001: Double-Entry Rule
- Sum of all debit amounts MUST equal sum of all credit amounts
- Tolerance: 0.01 (one cent) for rounding
- Validation runs before saving and before posting

### BR-TXN-002: Minimum Lines
- Every transaction MUST have at least 2 lines
- At least one debit line and one credit line

### BR-TXN-003: Positive Amounts
- All line amounts MUST be positive (> 0)
- Use line type (debit/credit) to indicate direction

### BR-TXN-004: Status Transitions
```
PENDING → POSTED (post action, after validation)
PENDING → VOIDED (cancel/delete action)
POSTED → VOIDED (void action, admin only)

Cannot transition:
POSTED → PENDING (cannot unpost)
VOIDED → any (voided is terminal)
```

### BR-TXN-005: Immutability After Posting
- Posted transactions CANNOT be edited
- Only action on posted transaction is VOID
- Changes require voiding and creating new transaction

### BR-TXN-006: Approval Required
Transaction requires approval when:
- Would cause negative equity account balance
- Amount exceeds company's approval threshold
- Transaction date is backdated beyond allowed days

### BR-TXN-007: Void Requirements
- Void reason MUST be provided (min 10 characters)
- Posted transactions can only be voided by admin
- Voiding creates reversal entries in ledger

### BR-TXN-008: Backdating Limits
- Backdating allowed up to `maxBackdateDays` (company setting)
- Backdating beyond limit requires approval
- Future-dated transactions not allowed

### BR-TXN-009: Transaction Number Format
- Format: `TXN-YYYYMM-XXXXX`
- YYYYMM: Year and month of creation
- XXXXX: Sequential number, zero-padded
- Unique per company

---

## Algorithms

### Algorithm: Validate Double-Entry
```
FUNCTION validateDoubleEntry(lines):
    totalDebits = 0
    totalCredits = 0

    FOR EACH line IN lines:
        IF line.amount <= 0:
            RETURN ValidationResult.invalid("Amount must be positive")

        IF line.lineType == DEBIT:
            totalDebits += line.amount
        ELSE IF line.lineType == CREDIT:
            totalCredits += line.amount

    difference = abs(totalDebits - totalCredits)

    IF difference < 0.01:  # 1 cent tolerance
        RETURN ValidationResult.valid("Transaction balanced")
    ELSE:
        RETURN ValidationResult.invalid(
            "Debits (${totalDebits}) must equal Credits (${totalCredits}). " +
            "Difference: ${difference}"
        )
END FUNCTION
```

### Algorithm: Calculate Balance Change
```
FUNCTION calculateBalanceChange(normalBalance, lineType, amount):
    # Core principle:
    # Same side as normal balance = INCREASE
    # Opposite side = DECREASE

    IF normalBalance.value() == lineType.value():
        RETURN +amount  # Increase
    ELSE:
        RETURN -amount  # Decrease
END FUNCTION

# Examples:
# Asset (normal: DEBIT)
#   Debit $100 → +100 (balance increases)
#   Credit $100 → -100 (balance decreases)

# Liability (normal: CREDIT)
#   Credit $100 → +100 (balance increases)
#   Debit $100 → -100 (balance decreases)
```

### Algorithm: Validate Negative Balances
```
FUNCTION validateNoNegativeBalances(transaction, currentBalances):
    issues = []

    FOR EACH line IN transaction.lines:
        account = getAccount(line.accountId)
        currentBalance = currentBalances[line.accountId] ?? 0

        change = calculateBalanceChange(
            account.normalBalance,
            line.lineType,
            line.amount
        )

        projectedBalance = currentBalance + change

        IF projectedBalance < 0:
            IF account.type IN [ASSET, LIABILITY, REVENUE, EXPENSE]:
                # These cannot go negative
                issues.append({
                    type: "ERROR",
                    accountId: account.id,
                    accountName: account.name,
                    accountType: account.type,
                    currentBalance: currentBalance,
                    projectedBalance: projectedBalance
                })
            ELSE IF account.type == EQUITY:
                # Equity can go negative with approval
                issues.append({
                    type: "APPROVAL_REQUIRED",
                    accountId: account.id,
                    accountName: account.name,
                    accountType: account.type,
                    currentBalance: currentBalance,
                    projectedBalance: projectedBalance
                })

    IF hasErrors(issues):
        RETURN ValidationResult.invalid(formatErrors(issues))

    IF hasApprovalRequired(issues):
        transaction.setRequiresApproval(TRUE)

    RETURN ValidationResult.valid()
END FUNCTION
```

### Algorithm: Post Transaction
```
FUNCTION postTransaction(transactionId, postedBy):
    transaction = repository.findById(transactionId)

    IF transaction IS NULL:
        THROW "Transaction not found"

    IF transaction.status != PENDING:
        THROW "Transaction must be pending to post"

    # Full validation
    validationResult = validator.validate(transaction)

    IF NOT validationResult.isValid():
        THROW validationResult.getErrors()

    IF transaction.requiresApproval:
        IF NOT hasApproval(transactionId):
            THROW "Transaction requires approval before posting"

    # Update status
    transaction.setStatus(POSTED)
    transaction.setPostedBy(postedBy)
    transaction.setPostedAt(NOW())

    repository.save(transaction)

    # Publish event for ledger to process
    publishEvent(TransactionPosted(
        transactionId: transaction.id,
        companyId: transaction.companyId,
        lines: transaction.lines,
        postedBy: postedBy
    ))

    RETURN PostingResult.success(transaction)
END FUNCTION
```

### Algorithm: Void Transaction
```
FUNCTION voidTransaction(transactionId, voidedBy, reason):
    transaction = repository.findById(transactionId)

    IF transaction IS NULL:
        THROW "Transaction not found"

    IF transaction.status == VOIDED:
        THROW "Transaction already voided"

    IF length(reason) < 10:
        THROW "Void reason must be at least 10 characters"

    # Check authorization
    user = getUser(voidedBy)
    IF transaction.status == POSTED AND user.role != ADMIN:
        THROW "Only admins can void posted transactions"

    # Update status
    transaction.setStatus(VOIDED)
    transaction.setVoidedBy(voidedBy)
    transaction.setVoidedAt(NOW())
    transaction.setVoidReason(reason)

    repository.save(transaction)

    # Publish event for ledger to reverse balances
    publishEvent(TransactionVoided(
        transactionId: transaction.id,
        transactionNumber: transaction.transactionNumber,
        voidedBy: voidedBy,
        voidReason: reason
    ))

    RETURN VoidResult.success(transaction)
END FUNCTION
```

---

## Use Cases

### UC-TXN-001: Create Transaction
**Actor:** User
**Preconditions:** User authenticated, company active
**Flow:**
1. Generate transaction number
2. Validate transaction date (not future, within backdate limit)
3. Validate at least 2 lines (1 debit, 1 credit)
4. Validate all amounts positive
5. Validate double-entry (debits = credits)
6. Create Transaction entity with PENDING status
7. Save transaction
8. Publish TransactionCreated event

### UC-TXN-002: Edit Transaction
**Actor:** User
**Preconditions:** Transaction status is PENDING
**Flow:**
1. Verify transaction is editable (PENDING status)
2. Update transaction fields
3. Re-validate double-entry
4. Save changes
5. Publish TransactionUpdated event

### UC-TXN-003: Post Transaction
**Actor:** User
**Preconditions:** Transaction validated, approval granted if required
**Flow:**
1. Run full validation
2. Check for approval requirement
3. Verify approval granted (if required)
4. Update status to POSTED
5. Publish TransactionPosted event
6. Ledger context updates balances

### UC-TXN-004: Void Transaction
**Actor:** Admin
**Preconditions:** Transaction exists, admin authenticated
**Flow:**
1. Verify user is admin (for posted transactions)
2. Require void reason
3. Update status to VOIDED
4. Publish TransactionVoided event
5. Ledger context reverses balances

### UC-TXN-005: View Transaction History
**Actor:** User
**Preconditions:** User has access to company
**Flow:**
1. Query transactions by company and filters
2. Include related lines and accounts
3. Return paginated results

---

## Integration Points

### Consumes Events:
- `AccountCreated` → Account available for transactions
- `AccountDeactivated` → Validate existing transactions
- `ApprovalGranted` → Process pending transaction

### Publishes Events:
- `TransactionCreated` → Audit Trail
- `TransactionValidated` → Audit Trail
- `TransactionApprovalRequired` → Approval Workflow
- `TransactionPosted` → Ledger & Posting, Financial Reporting, Audit Trail
- `TransactionVoided` → Ledger & Posting, Financial Reporting, Audit Trail

### Dependencies:
- Chart of Accounts (for account validation)
- Approval Workflow (for approval processing)
- Ledger & Posting (for balance updates)

---

## Database Schema (Reference)

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

    CONSTRAINT fk_transactions_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON DELETE RESTRICT,
    CONSTRAINT chk_amount_positive CHECK (total_amount >= 0)
);

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
);
```

---

## Transaction Line Examples

### Example 1: Cash Sale
Customer pays $1,000 cash for services:
```json
{
  "description": "Cash sale to customer ABC",
  "lines": [
    { "account": "Cash (1000)", "type": "debit", "amount": 1000.00 },
    { "account": "Service Revenue (4100)", "type": "credit", "amount": 1000.00 }
  ]
}
```

### Example 2: Pay Rent
Pay $800 rent expense:
```json
{
  "description": "Monthly rent payment",
  "lines": [
    { "account": "Rent Expense (5200)", "type": "debit", "amount": 800.00 },
    { "account": "Cash (1000)", "type": "credit", "amount": 800.00 }
  ]
}
```

### Example 3: Purchase on Credit
Buy $5,000 equipment on credit:
```json
{
  "description": "Equipment purchase - NET 30",
  "lines": [
    { "account": "Equipment (1700)", "type": "debit", "amount": 5000.00 },
    { "account": "Accounts Payable (2000)", "type": "credit", "amount": 5000.00 }
  ]
}
```

### Example 4: Complex Transaction
Receive $2,500 payment with $100 discount:
```json
{
  "description": "Invoice #1234 payment with discount",
  "lines": [
    { "account": "Cash (1000)", "type": "debit", "amount": 2400.00 },
    { "account": "Sales Discount (4900)", "type": "debit", "amount": 100.00 },
    { "account": "Accounts Receivable (1100)", "type": "credit", "amount": 2500.00 }
  ]
}
```
