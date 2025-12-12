# Financial Reporting Subsystem

## Overview

The Financial Reporting bounded context generates financial statements and reports based on ledger data.

## Purpose

This subsystem is responsible for:

- **Balance Sheet**: Assets, Liabilities, Equity at point in time
- **Income Statement**: Revenue and Expenses for a period
- **Trial Balance**: All account balances with debits/credits
- **Report Generation**: Creating reports in various formats
- **Report Filtering**: Date ranges, account filters
- **Report Export**: PDF, Excel, CSV formats

## Reports

### Balance Sheet
Shows financial position at a specific date:
- **Assets** (debit balances)
- **Liabilities** (credit balances)
- **Equity** (credit balances)
- Formula: Assets = Liabilities + Equity

### Income Statement
Shows profit/loss for a period:
- **Revenue** (credit balances)
- **Expenses** (debit balances)
- Formula: Net Income = Revenue - Expenses

### Trial Balance
Lists all accounts with debit/credit balances:
- Verifies double-entry accuracy
- Total debits = Total credits

## Key Value Objects

- **ReportId**: UUID-based report identifier
- **ReportType**: Enum (balance_sheet, income_statement, trial_balance)
- **DateRange**: Start and end dates for reports
- **ReportFormat**: Enum (html, pdf, excel, csv)

## Business Rules

1. **BR-REP-001**: Reports use posted transactions only
2. **BR-REP-002**: Balance sheet is point-in-time
3. **BR-REP-003**: Income statement requires date range
4. **BR-REP-004**: Trial balance must balance (debits = credits)
5. **BR-REP-005**: Reports can filter by account type

## Use Cases

- **UC-REP-001**: Generate Balance Sheet
- **UC-REP-002**: Generate Income Statement
- **UC-REP-003**: Generate Trial Balance
- **UC-REP-004**: Export Report

## Domain Events

- **ReportGenerated**
- **ReportExported**

## Integration

Consumes:
- **AccountBalanceChanged** → Update report data
- **TransactionPosted** → Refresh reports

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)

---

**Status**: Documented, awaiting implementation
