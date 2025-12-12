# Transaction Processing Subsystem

## Overview

The Transaction Processing bounded context handles the creation, validation, and management of financial transactions using double-entry bookkeeping principles.

## Purpose

This subsystem is responsible for:

- **Transaction Creation**: Creating multi-line financial transactions
- **Double-Entry Validation**: Ensuring debits equal credits
- **Balance Validation**: Preventing negative balances (except equity)
- **Transaction Workflow**: Managing pending → posted → voided states
- **Approval Triggering**: Flagging transactions requiring approval
- **Transaction Numbering**: Auto-generating unique transaction numbers
- **Transaction History**: Tracking all changes and status transitions

## Core Entities

### Transaction
The main aggregate representing a financial transaction with:
- Unique transaction number (TXN-YYYYMM-XXXXX)
- Transaction date and description
- Multiple transaction lines (debits and credits)
- Status (pending, posted, voided)
- Approval requirements
- Creator and poster tracking

### TransactionLine
Individual debit or credit entry with:
- Reference to parent transaction
- Account reference
- Line type (debit or credit)
- Amount (always positive)
- Line order for display

## Key Value Objects

- **TransactionId**: UUID-based transaction identifier
- **LineId**: UUID-based line identifier
- **TransactionDate**: Date with validation rules
- **LineType**: Enum (debit, credit)
- **TransactionStatus**: Enum (pending, posted, voided)
- **Money**: Amount with currency support
- **ValidationResult**: Validation outcome with errors/warnings

## Business Rules

### Core Rules

1. **BR-TXN-001: Double-Entry Rule**
   - Sum of debits MUST equal sum of credits
   - Tolerance: 0.01 (one cent) for rounding

2. **BR-TXN-002: Minimum Lines**
   - At least 2 lines required
   - At least one debit and one credit

3. **BR-TXN-003: Positive Amounts**
   - All amounts must be positive (> 0)
   - Direction indicated by line type

4. **BR-TXN-004: Status Transitions**
   - PENDING → POSTED (after validation)
   - PENDING → VOIDED (cancel)
   - POSTED → VOIDED (admin only)
   - Cannot reverse posted or voided transactions

5. **BR-TXN-005: Immutability After Posting**
   - Posted transactions cannot be edited
   - Must void and create new transaction

### Approval Rules

6. **BR-TXN-006: Approval Required When**
   - Would cause negative equity balance
   - Amount exceeds company threshold
   - Transaction backdated beyond limit

7. **BR-TXN-007: Void Requirements**
   - Void reason required (min 10 chars)
   - Only admin can void posted transactions
   - Creates reversal entries in ledger

8. **BR-TXN-008: Backdating Limits**
   - Limited by company setting
   - Future-dated not allowed
   - Excessive backdating requires approval

9. **BR-TXN-009: Transaction Number Format**
   - Format: TXN-YYYYMM-XXXXX
   - Unique per company
   - Sequential within month

## Domain Services

### TransactionValidator
Validates complete transactions including:
- Double-entry balance
- Minimum line requirements
- Account validity
- Balance impacts
- Accounting equation integrity

### BalanceCalculator
Calculates balance changes based on:
- Account normal balance
- Line type (debit/credit)
- Amount
- Projects future balances

### TransactionNumberGenerator
Generates unique transaction numbers:
- Format: TXN-YYYYMM-XXXXX
- Sequential per company
- Month-based grouping

### TransactionPostingService
Posts transactions to ledger:
- Full validation before posting
- Updates all account balances
- Creates balance change records
- Publishes domain events

## Use Cases

### Primary Use Cases

1. **UC-TXN-001: Create Transaction**
   - Actor: User
   - Creates pending transaction
   - Validates double-entry
   - Saves to repository

2. **UC-TXN-002: Edit Transaction**
   - Actor: User
   - Only for pending transactions
   - Re-validates after changes

3. **UC-TXN-003: Post Transaction**
   - Actor: User
   - Validates and posts to ledger
   - Updates all balances
   - Cannot be reversed

4. **UC-TXN-004: Void Transaction**
   - Actor: Admin
   - Requires void reason
   - Reverses all balance changes
   - Terminal status

5. **UC-TXN-005: View Transaction History**
   - Actor: User
   - Lists transactions with filters
   - Shows full line details

## Transaction Examples

### Example 1: Cash Sale
```json
{
  "description": "Cash sale to customer",
  "lines": [
    { "account": "Cash (1000)", "type": "debit", "amount": 1000.00 },
    { "account": "Revenue (4100)", "type": "credit", "amount": 1000.00 }
  ]
}
```

### Example 2: Pay Rent
```json
{
  "description": "Monthly rent payment",
  "lines": [
    { "account": "Rent Expense (5200)", "type": "debit", "amount": 800.00 },
    { "account": "Cash (1000)", "type": "credit", "amount": 800.00 }
  ]
}
```

### Example 3: Complex Transaction
```json
{
  "description": "Payment with discount",
  "lines": [
    { "account": "Cash (1000)", "type": "debit", "amount": 2400.00 },
    { "account": "Sales Discount (4900)", "type": "debit", "amount": 100.00 },
    { "account": "Accounts Receivable (1100)", "type": "credit", "amount": 2500.00 }
  ]
}
```

## Domain Events

- **TransactionCreated**: New transaction created
- **TransactionValidated**: Validation completed
- **TransactionApprovalRequired**: Needs admin approval
- **TransactionPosted**: Posted to ledger
- **TransactionVoided**: Transaction voided
- **TransactionUpdated**: Pending transaction edited

## Integration Points

### Consumes Events
- `AccountCreated` → Account available for transactions
- `AccountDeactivated` → Validate existing transactions
- `ApprovalGranted` → Process pending transaction

### Publishes Events
- All transaction events → Audit Trail
- `TransactionPosted` → Ledger & Posting
- `TransactionApprovalRequired` → Approval Workflow

### Dependencies
- **Chart of Accounts**: For account validation
- **Approval Workflow**: For approval processing
- **Ledger & Posting**: For balance updates
- **Company Management**: For settings and validation

## Algorithms

### Double-Entry Validation
```
totalDebits = SUM(lines WHERE type=DEBIT)
totalCredits = SUM(lines WHERE type=CREDIT)
difference = ABS(totalDebits - totalCredits)

IF difference < 0.01:
  VALID
ELSE:
  INVALID
```

### Balance Change Calculation
```
IF normalBalance == lineType:
  change = +amount  (INCREASE)
ELSE:
  change = -amount  (DECREASE)
```

## API Endpoints

- `GET /api/v1/transactions` - List transactions
- `POST /api/v1/transactions` - Create transaction
- `GET /api/v1/transactions/:id` - Get transaction
- `PUT /api/v1/transactions/:id` - Update pending transaction
- `POST /api/v1/transactions/:id/post` - Post transaction
- `POST /api/v1/transactions/:id/void` - Void transaction
- `DELETE /api/v1/transactions/:id` - Delete pending transaction

## Validation Flow

```
Create Transaction
    ↓
Validate Structure (min 2 lines)
    ↓
Validate Amounts (all positive)
    ↓
Validate Double-Entry (debits = credits)
    ↓
Validate Accounts (exist & active)
    ↓
Calculate Balance Changes
    ↓
Check for Negative Balances
    ↓
Require Approval? → Yes → Flag for Approval
    ↓                 No
Save as PENDING
    ↓
Post Transaction
    ↓
Update Ledger Balances
    ↓
Status = POSTED
```

## Implementation Status

- [ ] Domain Model: Documented
- [ ] Value Objects: Not implemented
- [ ] Entities: Not implemented
- [ ] Services: Not implemented
- [ ] Repositories: Not implemented
- [ ] Use Cases: Not implemented
- [ ] API Endpoints: Not implemented
- [ ] Tests: Not implemented

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)
- **Double-Entry Algorithm**: [../../03-algorithms/double-entry-bookkeeping.md](../../03-algorithms/double-entry-bookkeeping.md)
- **Database Schema**: [../../03-algorithms/database-schema.md](../../03-algorithms/database-schema.md)
- **API Specification**: [../../04-api/api-specification.md](../../04-api/api-specification.md)

## Related Subsystems

- [Chart of Accounts](../chart-of-accounts/) - Account definitions
- [Ledger & Posting](../ledger-posting/) - Balance tracking
- [Approval Workflow](../approval-workflow/) - Transaction approvals
- [Audit Trail](../audit-trail/) - Transaction history

---

**Next Steps**: Begin TDD implementation following [Implementation Plan](../../plans/implementation-plan.md), Phase 3: Transaction Processing
