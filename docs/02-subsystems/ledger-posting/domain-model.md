# Ledger & Posting - Domain Model

> **Master Architect Reference**: This document provides the complete domain specification for implementing the Ledger & Posting bounded context.

## Aggregate: Ledger

**Aggregate Root:** Ledger entity (per company)

### Entities

#### Ledger
```php
class Ledger {
    private LedgerId $ledgerId;
    private CompanyId $companyId;
    private array $accountBalances;      // Map<AccountId, AccountBalance>
    private DateTime $lastUpdatedAt;
    private int $version;                // Optimistic locking

    public function postTransaction(PostedTransaction $transaction): array;
    public function reverseTransaction(TransactionId $transactionId): array;
    public function getBalance(AccountId $accountId): Money;
    public function getBalancesByType(AccountType $type): array;
}
```

#### AccountBalance
```php
class AccountBalance {
    private AccountBalanceId $balanceId;
    private AccountId $accountId;
    private CompanyId $companyId;
    private AccountType $accountType;
    private NormalBalance $normalBalance;
    private Money $currentBalance;
    private Money $openingBalance;
    private Money $totalDebits;
    private Money $totalCredits;
    private int $transactionCount;
    private DateTime $lastTransactionAt;
    private int $version;

    public function applyChange(BalanceChange $change): void;
    public function reverseChange(BalanceChange $change): void;
}
```

#### BalanceChange
```php
class BalanceChange {
    private BalanceChangeId $changeId;
    private AccountId $accountId;
    private TransactionId $transactionId;
    private LineType $lineType;          // Debit or Credit
    private Money $amount;
    private Money $previousBalance;
    private Money $newBalance;
    private Money $change;               // Can be negative (decrease)
    private DateTime $occurredAt;
    private bool $isReversal;
}
```

#### PostedTransaction
```php
class PostedTransaction {
    private TransactionId $transactionId;
    private CompanyId $companyId;
    private array $lines;                // TransactionLine[]
    private Money $totalAmount;
    private DateTime $transactionDate;
    private UserId $postedBy;
    private DateTime $postedAt;
}
```

### Value Objects

#### LedgerId
```php
final class LedgerId {
    private string $value;  // UUID v4
}
```

#### AccountBalanceId
```php
final class AccountBalanceId {
    private string $value;  // UUID v4
}
```

#### BalanceChangeId
```php
final class BalanceChangeId {
    private string $value;  // UUID v4
}
```

#### BalanceSummary
```php
final class BalanceSummary {
    private Money $totalAssets;
    private Money $totalLiabilities;
    private Money $totalEquity;
    private Money $totalRevenue;
    private Money $totalExpenses;

    public function isBalanced(): bool;
    public function getNetIncome(): Money;
    public function getRetainedEarnings(): Money;
}
```

---

## Domain Services

### BalanceCalculationService
```php
interface BalanceCalculationService {
    /**
     * Calculate balance change for a transaction line
     */
    public function calculateChange(
        NormalBalance $normalBalance,
        LineType $lineType,
        Money $amount
    ): Money;

    /**
     * Calculate projected balance after transaction
     */
    public function projectBalance(
        AccountBalance $currentBalance,
        BalanceChange $change
    ): Money;
}
```

### LedgerPostingService
```php
interface LedgerPostingService {
    /**
     * Post a validated transaction to the ledger
     */
    public function post(PostedTransaction $transaction): PostingResult;

    /**
     * Reverse a posted transaction (for voids)
     */
    public function reverse(
        TransactionId $transactionId,
        string $reason
    ): ReversalResult;
}
```

### AccountingEquationValidator
```php
interface AccountingEquationValidator {
    /**
     * Validate that accounting equation is balanced
     * Assets = Liabilities + Equity + (Revenue - Expenses)
     */
    public function validate(Ledger $ledger): ValidationResult;

    /**
     * Validate equation would remain balanced after changes
     */
    public function validateWithChanges(
        Ledger $ledger,
        array $balanceChanges
    ): ValidationResult;
}
```

---

## Repository Interface

```php
interface LedgerRepositoryInterface {
    public function save(Ledger $ledger): void;

    public function findByCompany(CompanyId $companyId): ?Ledger;

    public function getAccountBalance(
        CompanyId $companyId,
        AccountId $accountId
    ): ?AccountBalance;

    public function getAllBalances(CompanyId $companyId): array;

    public function getBalancesByType(
        CompanyId $companyId,
        AccountType $type
    ): array;

    public function getBalanceHistory(
        AccountId $accountId,
        DateTime $from,
        DateTime $to
    ): array;
}

interface BalanceChangeRepositoryInterface {
    public function save(BalanceChange $change): void;

    public function findByTransaction(TransactionId $transactionId): array;

    public function findByAccount(
        AccountId $accountId,
        DateTime $from,
        DateTime $to
    ): array;
}
```

---

## Domain Events

### LedgerUpdated
```json
{
  "eventId": "uuid",
  "eventType": "LedgerUpdated",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "ledgerId": "uuid",
    "companyId": "uuid",
    "transactionId": "uuid",
    "balanceChanges": [
      {
        "accountId": "uuid",
        "previousBalance": "1000.00",
        "newBalance": "1500.00",
        "change": "500.00"
      }
    ]
  }
}
```

### AccountBalanceChanged
```json
{
  "eventId": "uuid",
  "eventType": "AccountBalanceChanged",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "accountId": "uuid",
    "companyId": "uuid",
    "accountType": "asset",
    "previousBalance": "1000.00",
    "newBalance": "1500.00",
    "change": "500.00",
    "transactionId": "uuid",
    "isReversal": false
  }
}
```

### NegativeBalanceDetected
```json
{
  "eventId": "uuid",
  "eventType": "NegativeBalanceDetected",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "accountId": "uuid",
    "accountName": "string",
    "accountType": "equity",
    "projectedBalance": "-500.00",
    "transactionId": "uuid",
    "requiresApproval": true
  }
}
```

### TransactionReversed
```json
{
  "eventId": "uuid",
  "eventType": "TransactionReversed",
  "occurredAt": "2025-12-12T10:30:00Z",
  "aggregateId": "uuid",
  "payload": {
    "transactionId": "uuid",
    "reason": "string",
    "reversedBy": "uuid",
    "balanceRestorations": [
      {
        "accountId": "uuid",
        "restoredBalance": "1000.00"
      }
    ]
  }
}
```

---

## Business Rules

### BR-LP-001: Balance Change Calculation
```
IF transaction_line_type == account_normal_balance:
    balance_change = +amount  (increase)
ELSE:
    balance_change = -amount  (decrease)
```

### BR-LP-002: Negative Balance Restrictions
| Account Type | Can Be Negative | Action Required |
|--------------|-----------------|-----------------|
| Asset | No | Reject transaction |
| Liability | No | Reject transaction |
| Revenue | No | Reject transaction |
| Expense | No | Reject transaction |
| Equity | Yes* | Requires approval |

*Equity accounts (like Owner's Drawings) can go negative with admin approval

### BR-LP-003: Atomic Posting
- All balance changes from a transaction MUST be applied atomically
- If any balance update fails, ALL changes must be rolled back
- Use database transactions for atomicity

### BR-LP-004: Optimistic Locking
- Balance updates use version numbers to prevent concurrent modification
- If version mismatch, retry the operation
- Maximum 3 retries before failing

### BR-LP-005: Accounting Equation Integrity
After every transaction:
```
Assets = Liabilities + Equity + (Revenue - Expenses)
```
- This MUST always hold true
- System should alert if equation becomes unbalanced
- Unbalanced state indicates a bug

### BR-LP-006: Reversal Rules
- Reversals create opposite balance changes
- Reversal entries reference original transaction
- Reversals are recorded, not deleted
- Cannot reverse already-reversed transactions

### BR-LP-007: Balance Precision
- All balances stored with 2 decimal places
- Calculations performed with higher precision (4 decimals)
- Final storage rounded using banker's rounding

---

## Algorithms

### Algorithm: Post Transaction to Ledger
```
FUNCTION postTransaction(transaction):
    ledger = getLedger(transaction.companyId)
    balanceChanges = []

    BEGIN TRANSACTION

    FOR EACH line IN transaction.lines:
        account = getAccount(line.accountId)
        currentBalance = ledger.getBalance(line.accountId)

        # Calculate change
        change = calculateBalanceChange(
            account.normalBalance,
            line.lineType,
            line.amount
        )

        newBalance = currentBalance + change

        # Validate negative balance
        IF newBalance < 0:
            IF account.type != EQUITY:
                ROLLBACK
                RETURN Error("Cannot have negative {account.type} balance")
            ELSE:
                # Flag for approval (handled by caller)
                transaction.requiresApproval = TRUE

        balanceChange = new BalanceChange(
            accountId: line.accountId,
            transactionId: transaction.id,
            lineType: line.lineType,
            amount: line.amount,
            previousBalance: currentBalance,
            newBalance: newBalance,
            change: change
        )

        balanceChanges.append(balanceChange)

    # Apply all changes atomically
    FOR EACH change IN balanceChanges:
        ledger.applyChange(change)
        publishEvent(AccountBalanceChanged(change))

    # Validate accounting equation
    IF NOT ledger.isEquationBalanced():
        ROLLBACK
        RETURN Error("Accounting equation violated")

    COMMIT

    publishEvent(LedgerUpdated(ledger, balanceChanges))
    RETURN Success(balanceChanges)
END FUNCTION
```

### Algorithm: Calculate Balance Change
```
FUNCTION calculateBalanceChange(normalBalance, lineType, amount):
    # Same side = increase, opposite side = decrease
    IF normalBalance == lineType:
        RETURN +amount
    ELSE:
        RETURN -amount
END FUNCTION

# Examples:
# Asset (normal: DEBIT)
#   Debit $100 → +100 (increase)
#   Credit $100 → -100 (decrease)

# Liability (normal: CREDIT)
#   Credit $100 → +100 (increase)
#   Debit $100 → -100 (decrease)
```

### Algorithm: Reverse Transaction
```
FUNCTION reverseTransaction(transactionId, reason, userId):
    originalChanges = getBalanceChanges(transactionId)

    IF originalChanges.isEmpty():
        RETURN Error("Transaction not found or not posted")

    IF isAlreadyReversed(transactionId):
        RETURN Error("Transaction already reversed")

    BEGIN TRANSACTION

    reversalChanges = []

    FOR EACH originalChange IN originalChanges:
        # Create opposite change
        reversalChange = new BalanceChange(
            accountId: originalChange.accountId,
            transactionId: transactionId,
            lineType: opposite(originalChange.lineType),
            amount: originalChange.amount,
            previousBalance: getCurrentBalance(originalChange.accountId),
            change: -originalChange.change,  # Opposite
            isReversal: TRUE
        )

        reversalChanges.append(reversalChange)
        applyChange(reversalChange)

    COMMIT

    publishEvent(TransactionReversed(transactionId, reason, reversalChanges))
    RETURN Success(reversalChanges)
END FUNCTION
```

---

## Use Cases

### UC-LP-001: Post Transaction
**Actor:** System (triggered by TransactionPosted event)
**Preconditions:** Transaction validated, approval granted if needed
**Flow:**
1. Receive TransactionPosted event
2. Calculate balance changes for each line
3. Validate no prohibited negative balances
4. Apply changes atomically
5. Validate accounting equation
6. Publish LedgerUpdated event
7. Publish AccountBalanceChanged events

### UC-LP-002: Get Account Balance
**Actor:** User, System
**Preconditions:** Account exists
**Flow:**
1. Query ledger for account balance
2. Return current balance with metadata

### UC-LP-003: Reverse Transaction (Void)
**Actor:** System (triggered by TransactionVoided event)
**Preconditions:** Transaction was posted
**Flow:**
1. Receive TransactionVoided event
2. Retrieve original balance changes
3. Create reversal entries
4. Apply reversals
5. Publish TransactionReversed event

### UC-LP-004: Generate Balance Summary
**Actor:** Financial Reporting context
**Preconditions:** Company exists
**Flow:**
1. Aggregate balances by account type
2. Calculate totals
3. Verify accounting equation
4. Return BalanceSummary

---

## Integration Points

### Consumes Events:
- `TransactionPosted` → Post transaction to ledger
- `TransactionVoided` → Reverse transaction in ledger

### Publishes Events:
- `LedgerUpdated` → General ledger update notification
- `AccountBalanceChanged` → Per-account change (for reporting)
- `NegativeBalanceDetected` → Triggers approval workflow
- `TransactionReversed` → Notifies of void completion

### Dependencies:
- Chart of Accounts (for account metadata)
- Transaction Processing (for transaction data)

---

## Database Schema (Reference)

```sql
-- Main balance tracking table
CREATE TABLE account_balances (
    id UUID PRIMARY KEY,
    account_id UUID NOT NULL REFERENCES accounts(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_debits DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_credits DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    transaction_count INT NOT NULL DEFAULT 0,
    last_transaction_at TIMESTAMP,
    version INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(account_id),
    CONSTRAINT positive_counts CHECK (transaction_count >= 0)
);

-- Detailed change history (event sourcing)
CREATE TABLE balance_changes (
    id UUID PRIMARY KEY,
    account_id UUID NOT NULL REFERENCES accounts(id),
    transaction_id UUID NOT NULL,
    line_type VARCHAR(10) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    previous_balance DECIMAL(15,2) NOT NULL,
    new_balance DECIMAL(15,2) NOT NULL,
    change_amount DECIMAL(15,2) NOT NULL,
    is_reversal BOOLEAN DEFAULT FALSE,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT valid_line_type CHECK (line_type IN ('debit', 'credit'))
);

CREATE INDEX idx_balance_changes_account ON balance_changes(account_id);
CREATE INDEX idx_balance_changes_transaction ON balance_changes(transaction_id);
CREATE INDEX idx_balance_changes_date ON balance_changes(occurred_at);
CREATE INDEX idx_account_balances_company ON account_balances(company_id);
```

---

## Read Models

### AccountBalanceView
For fast balance queries:
```sql
CREATE VIEW account_balance_view AS
SELECT
    ab.account_id,
    a.account_code,
    a.account_name,
    a.account_type,
    ab.current_balance,
    ab.total_debits,
    ab.total_credits,
    ab.transaction_count,
    ab.last_transaction_at
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
WHERE a.is_active = TRUE;
```

### BalanceSummaryView
For financial reporting:
```sql
CREATE VIEW balance_summary_view AS
SELECT
    ab.company_id,
    a.account_type,
    SUM(ab.current_balance) as total_balance,
    COUNT(*) as account_count
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
WHERE a.is_active = TRUE
GROUP BY ab.company_id, a.account_type;
```
