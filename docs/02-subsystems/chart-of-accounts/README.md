# Chart of Accounts Subsystem

## Overview

The Chart of Accounts bounded context manages the account structure, including account creation, hierarchy, and lifecycle management for each company.

## Purpose

This subsystem is responsible for:

- **Account Management**: Creating and managing accounts
- **Account Hierarchy**: Parent-child account relationships
- **Account Types**: Assets, Liabilities, Equity, Revenue, Expense
- **Normal Balance**: Debit or credit based on account type
- **Default Accounts**: System-required accounts
- **Account Validation**: Code format and uniqueness

## Core Entities

### Account
Main entity representing a general ledger account:
- Unique account code (e.g., 1000-1999 for assets)
- Account name and type
- Normal balance (debit/credit)
- Parent account for hierarchy
- Active/inactive status
- Opening balance

## Key Value Objects

- **AccountId**: UUID-based account identifier
- **AccountCode**: 4-digit code with type validation
- **AccountType**: Enum (asset, liability, equity, revenue, expense)
- **NormalBalance**: Enum (debit, credit)

## Business Rules

1. **BR-COA-001**: Account code must be unique per company
2. **BR-COA-002**: Account code determines type (1xxx=Asset, 2xxx=Liability, etc.)
3. **BR-COA-003**: Normal balance derives from account type
4. **BR-COA-004**: Cannot deactivate account with balance
5. **BR-COA-005**: Cannot delete account with transactions
6. **BR-COA-006**: System accounts cannot be deleted

## Account Code Ranges

- **1000-1999**: Assets
- **2000-2999**: Liabilities
- **3000-3999**: Equity
- **4000-4999**: Revenue
- **5000-5999**: Expenses

## Use Cases

- **UC-COA-001**: Create Account
- **UC-COA-002**: List Accounts
- **UC-COA-003**: Update Account
- **UC-COA-004**: Deactivate Account
- **UC-COA-005**: Initialize Default Chart

## Domain Events

- **AccountCreated**
- **AccountUpdated**
- **AccountDeactivated**

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)

---

**Status**: Documented, awaiting implementation
