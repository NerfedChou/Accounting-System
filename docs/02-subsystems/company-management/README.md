# Company Management Subsystem

## Overview

The Company Management bounded context handles multi-tenant organization management, company registration, settings, and lifecycle management.

## Purpose

This subsystem is responsible for:

- **Company Registration**: Creating new tenant companies
- **Company Settings**: Managing company-specific configurations
- **Multi-Tenancy**: Isolating data between companies
- **Company Lifecycle**: Activation, suspension, deactivation
- **Fiscal Year Management**: Company-specific fiscal periods
- **Company Information**: Legal details, address, tax information

## Core Entities

### Company
Main entity representing a tenant organization:
- Legal name and trading name
- Tax identification
- Physical address
- Currency and timezone
- Fiscal year configuration
- Status (pending, active, suspended, deactivated)

### CompanySettings
Configuration specific to each company:
- Approval thresholds
- Backdate limits
- Auto-posting preferences
- Date/number formats

## Key Value Objects

- **CompanyId**: UUID-based company identifier
- **TaxIdentifier**: Tax ID with validation
- **Address**: Complete address value object
- **FiscalYear**: Start month/day configuration
- **CompanyStatus**: Enum (pending, active, suspended, deactivated)

## Business Rules

1. **BR-COM-001**: Company name must be unique
2. **BR-COM-002**: Tax ID must be unique
3. **BR-COM-003**: New companies start as pending
4. **BR-COM-004**: Only admins can activate companies
5. **BR-COM-005**: Deactivation cascades to all resources
6. **BR-COM-006**: Cannot delete company with transactions
7. **BR-COM-007**: Settings must have valid values

## Use Cases

- **UC-COM-001**: Create Company
- **UC-COM-002**: Activate Company
- **UC-COM-003**: Update Company
- **UC-COM-004**: Deactivate Company
- **UC-COM-005**: Update Settings

## Domain Events

- **CompanyCreated**
- **CompanyActivated**
- **CompanyDeactivated**
- **CompanySettingsUpdated**

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)

---

**Status**: Documented, awaiting implementation
