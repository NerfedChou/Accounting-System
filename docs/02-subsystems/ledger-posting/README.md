# Ledger & Posting Subsystem

## Overview

The Ledger & Posting bounded context maintains account balances and processes posted transactions to update the general ledger.

## Purpose

This subsystem is responsible for:

- **Balance Tracking**: Maintaining current balances for all accounts
- **Balance History**: Recording all balance changes
- **Posting Process**: Applying transactions to ledger
- **Balance Validation**: Ensuring accounting equation integrity
- **Balance Inquiry**: Querying current and historical balances

## Core Entities

### AccountBalance
Current balance for each account:
- Current balance
- Total debits and credits
- Transaction count
- Last transaction date
- Version for optimistic locking

### BalanceChange
Historical record of each balance change:
- Previous and new balance
- Change amount
- Related transaction
- Timestamp

## Key Value Objects

- **LedgerId**: UUID-based ledger identifier
- **BalanceChangeId**: UUID for each change record
- **BalanceSummary**: Aggregated balance information

## Business Rules

1. **BR-LED-001**: Balance updated only on transaction posting
2. **BR-LED-002**: Assets = Liabilities + Equity + (Revenue - Expenses)
3. **BR-LED-003**: Asset/Liability/Revenue/Expense cannot go negative
4. **BR-LED-004**: Equity can go negative with approval
5. **BR-LED-005**: Voiding reverses all balance changes

## Domain Services

### LedgerPostingService
Posts transactions and updates balances

### AccountingEquationValidator
Ensures equation remains balanced

### BalanceCalculationService
Calculates balance changes from transactions

## Use Cases

- **UC-LED-001**: View Account Balance
- **UC-LED-002**: View All Balances
- **UC-LED-003**: View Balance History
- **UC-LED-004**: View General Ledger

## Domain Events

- **LedgerUpdated**
- **AccountBalanceChanged**
- **NegativeBalanceDetected**

## Integration

Consumes:
- **TransactionPosted** → Update balances
- **TransactionVoided** → Reverse balances

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)

---

**Status**: Documented, awaiting implementation
