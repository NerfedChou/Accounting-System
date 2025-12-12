# Double-Entry Bookkeeping Algorithm

> **Master Architect Reference**: Core accounting algorithm documentation for the system.

## Fundamental Principle

Every financial transaction affects at least two accounts. The total of all debits must equal the total of all credits. This is the cornerstone of the accounting equation:

```
Assets = Liabilities + Equity + (Revenue - Expenses)
```

---

## The Five Account Types

| Account Type | Normal Balance | Increases When | Decreases When |
|--------------|----------------|----------------|----------------|
| **Asset**    | Debit          | Debited        | Credited       |
| **Liability**| Credit         | Credited       | Debited        |
| **Equity**   | Credit         | Credited       | Debited        |
| **Revenue**  | Credit         | Credited       | Debited        |
| **Expense**  | Debit          | Debited        | Credited       |

### Memory Aid: DEALER

- **D**ebits: Assets, Expenses (normal balance = Debit)
- **C**redits: Liabilities, Equity, Revenue (normal balance = Credit)

---

## Balance Change Formula

```
IF (transaction_line_type == account_normal_balance):
    balance_change = +amount  // INCREASE
ELSE:
    balance_change = -amount  // DECREASE
```

### PHP Implementation

```php
public function calculateBalanceChange(
    NormalBalance $normalBalance,
    LineType $lineType,
    Money $amount
): Money {
    if ($normalBalance->value() === $lineType->value()) {
        return $amount; // Same side = Increase
    }
    return $amount->negate(); // Opposite side = Decrease
}
```

---

## Account Type Matrix

| Account Type | Code Range | Normal Balance | Debit Effect | Credit Effect |
|--------------|------------|----------------|--------------|---------------|
| Asset        | 1000-1999  | Debit          | +Increase    | -Decrease     |
| Liability    | 2000-2999  | Credit         | -Decrease    | +Increase     |
| Equity       | 3000-3999  | Credit         | -Decrease    | +Increase     |
| Revenue      | 4000-4999  | Credit         | -Decrease    | +Increase     |
| Expense      | 5000-5999  | Debit          | +Increase    | -Decrease     |

---

## Transaction Examples

### Example 1: Customer Pays Cash for Service

**Scenario:** Customer pays $1,000 cash for services rendered

**Accounts Affected:**
- Cash (Asset, normal balance: Debit)
- Service Revenue (Revenue, normal balance: Credit)

**Journal Entry:**
```
Debit:  Cash                 $1,000  (Asset increases)
Credit: Service Revenue      $1,000  (Revenue increases)
```

**Balance Changes:**
- Cash: `DEBIT == DEBIT` → +$1,000 ✓
- Service Revenue: `CREDIT == CREDIT` → +$1,000 ✓

**Accounting Equation:**
- Assets +$1,000
- Revenue +$1,000
- Equation: Assets = L + E + (R - Ex) remains balanced ✓

---

### Example 2: Pay Rent with Cash

**Scenario:** Pay $800 rent in cash

**Accounts Affected:**
- Cash (Asset, normal balance: Debit)
- Rent Expense (Expense, normal balance: Debit)

**Journal Entry:**
```
Debit:  Rent Expense         $800  (Expense increases)
Credit: Cash                 $800  (Asset decreases)
```

**Balance Changes:**
- Rent Expense: `DEBIT == DEBIT` → +$800 ✓
- Cash: `CREDIT != DEBIT` → -$800 ✓

**Accounting Equation:**
- Assets -$800
- Expenses +$800 (reduces right side)
- Equation balanced ✓

---

### Example 3: Purchase Equipment on Credit

**Scenario:** Buy $5,000 equipment, pay later (accounts payable)

**Accounts Affected:**
- Equipment (Asset, normal balance: Debit)
- Accounts Payable (Liability, normal balance: Credit)

**Journal Entry:**
```
Debit:  Equipment            $5,000  (Asset increases)
Credit: Accounts Payable     $5,000  (Liability increases)
```

**Balance Changes:**
- Equipment: `DEBIT == DEBIT` → +$5,000 ✓
- Accounts Payable: `CREDIT == CREDIT` → +$5,000 ✓

**Accounting Equation:**
- Assets +$5,000
- Liabilities +$5,000
- Equation balanced ✓

---

### Example 4: Owner Invests Cash

**Scenario:** Owner invests $10,000 cash into business

**Accounts Affected:**
- Cash (Asset, normal balance: Debit)
- Owner's Capital (Equity, normal balance: Credit)

**Journal Entry:**
```
Debit:  Cash                 $10,000  (Asset increases)
Credit: Owner's Capital      $10,000  (Equity increases)
```

**Balance Changes:**
- Cash: `DEBIT == DEBIT` → +$10,000 ✓
- Owner's Capital: `CREDIT == CREDIT` → +$10,000 ✓

**Accounting Equation:**
- Assets +$10,000
- Equity +$10,000
- Equation balanced ✓

---

### Example 5: Pay Down Loan

**Scenario:** Pay $2,000 cash to reduce bank loan

**Accounts Affected:**
- Bank Loan (Liability, normal balance: Credit)
- Cash (Asset, normal balance: Debit)

**Journal Entry:**
```
Debit:  Bank Loan            $2,000  (Liability decreases)
Credit: Cash                 $2,000  (Asset decreases)
```

**Balance Changes:**
- Bank Loan: `DEBIT != CREDIT` → -$2,000 ✓
- Cash: `CREDIT != DEBIT` → -$2,000 ✓

**Accounting Equation:**
- Assets -$2,000
- Liabilities -$2,000
- Equation balanced ✓

---

### Example 6: Complex Transaction (Multiple Lines)

**Scenario:** Receive $2,500 payment with $100 early payment discount

**Accounts Affected:**
- Cash (Asset)
- Sales Discount (Contra-Revenue or Expense)
- Accounts Receivable (Asset)

**Journal Entry:**
```
Debit:  Cash                 $2,400  (Asset increases)
Debit:  Sales Discount       $100    (Expense increases)
Credit: Accounts Receivable  $2,500  (Asset decreases)
```

**Validation:**
- Total Debits: $2,400 + $100 = $2,500
- Total Credits: $2,500
- Balanced ✓

---

## Implementation Pseudocode

### Full Transaction Processing

```
FUNCTION processTransaction(transaction):
    # Step 1: Validate double-entry
    totalDebits = SUM(line.amount WHERE line.type = DEBIT)
    totalCredits = SUM(line.amount WHERE line.type = CREDIT)

    IF abs(totalDebits - totalCredits) >= 0.01:
        THROW "Transaction unbalanced: debits must equal credits"

    # Step 2: Validate minimum lines
    IF transaction.lines.count() < 2:
        THROW "Transaction must have at least 2 lines"

    debitCount = COUNT(line WHERE line.type = DEBIT)
    creditCount = COUNT(line WHERE line.type = CREDIT)

    IF debitCount == 0 OR creditCount == 0:
        THROW "Transaction must have at least one debit and one credit"

    # Step 3: Validate all amounts positive
    FOR EACH line IN transaction.lines:
        IF line.amount <= 0:
            THROW "All line amounts must be positive"

    # Step 4: Calculate balance changes
    balanceChanges = []

    FOR EACH line IN transaction.lines:
        account = getAccount(line.accountId)

        change = calculateBalanceChange(
            account.normalBalance,
            line.lineType,
            line.amount
        )

        balanceChanges.append({
            accountId: line.accountId,
            accountType: account.type,
            change: change,
            newBalance: account.currentBalance + change
        })

    # Step 5: Validate no prohibited negative balances
    FOR EACH balanceChange IN balanceChanges:
        IF balanceChange.newBalance < 0:
            cannotBeNegative = [ASSET, LIABILITY, REVENUE, EXPENSE]

            IF balanceChange.accountType IN cannotBeNegative:
                THROW "{accountType} accounts cannot have negative balance"

            IF balanceChange.accountType == EQUITY:
                transaction.requiresApproval = TRUE

    # Step 6: Validate accounting equation
    projectedTotals = calculateProjectedTotals(balanceChanges)

    assets = projectedTotals[ASSET]
    liabilities = projectedTotals[LIABILITY]
    equity = projectedTotals[EQUITY]
    revenue = projectedTotals[REVENUE]
    expenses = projectedTotals[EXPENSE]

    leftSide = assets
    rightSide = liabilities + equity + (revenue - expenses)

    IF abs(leftSide - rightSide) >= 0.01:
        THROW "Accounting equation violated"

    # Step 7: Apply changes or request approval
    IF transaction.requiresApproval:
        transaction.status = PENDING_APPROVAL
        publishEvent(ApprovalRequested)
    ELSE:
        FOR EACH balanceChange IN balanceChanges:
            updateAccountBalance(balanceChange.accountId, balanceChange.change)

        transaction.status = POSTED
        publishEvent(TransactionPosted)

    RETURN transaction
END FUNCTION
```

### Balance Change Calculation

```
FUNCTION calculateBalanceChange(normalBalance, lineType, amount):
    IF normalBalance == lineType:
        RETURN +amount  # Same side = Increase
    ELSE:
        RETURN -amount  # Opposite side = Decrease
END FUNCTION
```

### Accounting Equation Validation

```
FUNCTION validateAccountingEquation(balancesByType):
    assets = balancesByType[ASSET] ?? 0
    liabilities = balancesByType[LIABILITY] ?? 0
    equity = balancesByType[EQUITY] ?? 0
    revenue = balancesByType[REVENUE] ?? 0
    expenses = balancesByType[EXPENSE] ?? 0

    # Assets = Liabilities + Equity + (Revenue - Expenses)
    leftSide = assets
    rightSide = liabilities + equity + (revenue - expenses)

    difference = abs(leftSide - rightSide)

    IF difference < 0.01:
        RETURN ValidationResult.valid()
    ELSE:
        RETURN ValidationResult.invalid(
            "Equation unbalanced: Assets={assets}, L+E+R-Ex={rightSide}"
        )
END FUNCTION
```

---

## Negative Balance Rules

| Account Type | Negative Allowed | Action |
|--------------|------------------|--------|
| Asset        | No               | Reject transaction |
| Liability    | No               | Reject transaction |
| Revenue      | No               | Reject transaction |
| Expense      | No               | Reject transaction |
| Equity       | Yes*             | Requires admin approval |

*Owner's Drawings (equity) is a common example where negative equity may occur temporarily.

---

## Testing Checklist

For every transaction, verify:

- [ ] Debits equal credits (within 0.01 tolerance)
- [ ] Minimum 2 lines (at least 1 debit, 1 credit)
- [ ] All amounts are positive
- [ ] All account IDs are valid
- [ ] Account balances updated correctly
- [ ] No prohibited negative balances
- [ ] Accounting equation still balanced
- [ ] Proper events published
- [ ] Audit trail recorded

---

## Common Mistakes to Avoid

### 1. Forgetting to Balance
Always verify debits = credits before posting. The system should never allow unbalanced transactions.

### 2. Wrong Sides
Remember normal balances:
- **Debit Normal**: Assets, Expenses
- **Credit Normal**: Liabilities, Equity, Revenue

### 3. Using Negative Amounts
Use line type (debit/credit) to indicate direction, not negative numbers. All amounts should be positive.

### 4. Single Entry
Every transaction needs at least 2 lines with opposite types.

### 5. Breaking the Equation
Always verify the accounting equation after changes. An unbalanced equation indicates a bug.

---

## PHP Implementation Reference

### TransactionValidator.php

```php
<?php

declare(strict_types=1);

namespace Domain\Transaction\Service;

use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\ValueObject\LineType;
use Domain\Transaction\ValueObject\ValidationResult;

final class TransactionValidator
{
    public function validate(Transaction $transaction): ValidationResult
    {
        $result = ValidationResult::valid();

        // Check minimum lines
        if (count($transaction->getLines()) < 2) {
            return ValidationResult::invalid(
                "Transaction must have at least 2 lines"
            );
        }

        // Check double-entry
        $doubleEntryResult = $this->validateDoubleEntry($transaction);
        if (!$doubleEntryResult->isValid()) {
            return $doubleEntryResult;
        }

        // Check positive amounts
        foreach ($transaction->getLines() as $line) {
            if ($line->getAmount()->toFloat() <= 0) {
                return ValidationResult::invalid(
                    "All line amounts must be positive"
                );
            }
        }

        return $result;
    }

    private function validateDoubleEntry(Transaction $transaction): ValidationResult
    {
        $totalDebits = 0.0;
        $totalCredits = 0.0;
        $hasDebit = false;
        $hasCredit = false;

        foreach ($transaction->getLines() as $line) {
            if ($line->getLineType()->equals(LineType::debit())) {
                $totalDebits += $line->getAmount()->toFloat();
                $hasDebit = true;
            } else {
                $totalCredits += $line->getAmount()->toFloat();
                $hasCredit = true;
            }
        }

        if (!$hasDebit || !$hasCredit) {
            return ValidationResult::invalid(
                "Transaction must have at least one debit and one credit line"
            );
        }

        $difference = abs($totalDebits - $totalCredits);

        if ($difference >= 0.01) {
            return ValidationResult::invalid(
                sprintf(
                    "Debits (%.2f) must equal Credits (%.2f). Difference: %.2f",
                    $totalDebits,
                    $totalCredits,
                    $difference
                )
            );
        }

        return ValidationResult::valid("Transaction balanced");
    }
}
```

### BalanceCalculator.php

```php
<?php

declare(strict_types=1);

namespace Domain\Ledger\Service;

use Domain\Account\ValueObject\NormalBalance;
use Domain\Transaction\ValueObject\LineType;
use Domain\Transaction\ValueObject\Money;

final class BalanceCalculator
{
    public function calculateBalanceChange(
        NormalBalance $normalBalance,
        LineType $lineType,
        Money $amount
    ): Money {
        // Same side = increase, opposite side = decrease
        if ($normalBalance->value() === $lineType->value()) {
            return $amount; // Increase
        }

        return $amount->negate(); // Decrease
    }
}
```

---

## Summary

The double-entry bookkeeping system ensures:

1. **Accuracy**: Every transaction is balanced
2. **Completeness**: All effects are recorded
3. **Traceability**: Clear audit trail
4. **Integrity**: Accounting equation always holds

These algorithms form the foundation of the entire accounting system.
