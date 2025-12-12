# Approval Workflow Subsystem

## Overview

The Approval Workflow bounded context manages administrative approval processes for transactions and other entities that require authorization.

## Purpose

This subsystem is responsible for:

- **Approval Requests**: Creating approval requests
- **Approval Queue**: Managing pending approvals
- **Approval Process**: Approve/reject workflow
- **Approval Tracking**: History and audit trail
- **Automatic Actions**: Triggering actions on approval
- **Expiration**: Time-based approval expiration

## Core Entities

### Approval
Represents an approval request:
- Entity type and ID being approved
- Approval type and reason
- Status (pending, approved, rejected, expired, cancelled)
- Priority level
- Requested by user
- Reviewed by admin
- Timestamps and expiration

## Key Value Objects

- **ApprovalId**: UUID-based approval identifier
- **ApprovalStatus**: Enum (pending, approved, rejected, expired, cancelled)
- **ApprovalType**: Enum (transaction, negative_equity, backdated, etc.)

## Business Rules

1. **BR-APR-001**: Only admins can review approvals
2. **BR-APR-002**: Cannot self-approve
3. **BR-APR-003**: Approval reason required
4. **BR-APR-004**: Rejected requires review notes
5. **BR-APR-005**: Approved triggers automatic action
6. **BR-APR-006**: Expired approvals require resubmission

## Approval Triggers

### Automatic Approval Required When:
- Transaction would cause negative equity
- Transaction amount exceeds threshold
- Transaction backdated beyond limit
- Account deactivation with balance
- User registration

## Use Cases

- **UC-APR-001**: List Pending Approvals
- **UC-APR-002**: Approve Request
- **UC-APR-003**: Reject Request
- **UC-APR-004**: Cancel Request

## Domain Events

- **ApprovalRequested**
- **ApprovalGranted**
- **ApprovalDenied**
- **ApprovalExpired**
- **ApprovalCancelled**

## Integration

Publishes:
- **ApprovalGranted** → Transaction Processing (auto-post)

Consumes:
- **TransactionApprovalRequired** → Create approval request

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)

---

**Status**: Documented, awaiting implementation
