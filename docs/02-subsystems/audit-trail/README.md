# Audit Trail Subsystem

## Overview

The Audit Trail bounded context maintains a complete, immutable log of all system activities for compliance, security, and troubleshooting.

## Purpose

This subsystem is responsible for:

- **Activity Logging**: Recording all user actions
- **Change Tracking**: Before/after state for all changes
- **Security Events**: Login attempts, access violations
- **Compliance**: Regulatory audit requirements
- **Forensics**: Investigation of issues
- **Audit Reports**: Generating audit reports

## Core Entities

### ActivityLog
Immutable record of a system activity:
- Actor (user or system)
- Activity type and action
- Entity type and ID
- Previous and new state
- Changes (diff)
- IP address, user agent
- Request ID and endpoint
- Severity level
- Timestamp

### AuditSnapshot
Periodic snapshot of system state:
- Snapshot type (daily, monthly, quarterly, yearly)
- Snapshot date
- Balance summary
- Checksum for integrity

## Key Value Objects

- **ActivityLogId**: UUID-based log identifier
- **ActivityType**: Enum (authentication, transaction, account, etc.)
- **Severity**: Enum (info, warning, critical, security)

## Business Rules

1. **BR-AUD-001**: All logs are immutable
2. **BR-AUD-002**: All user actions must be logged
3. **BR-AUD-003**: Security events logged immediately
4. **BR-AUD-004**: Logs retained for regulatory period
5. **BR-AUD-005**: Snapshots taken automatically
6. **BR-AUD-006**: Log integrity verified with checksums

## Logged Activities

### Authentication Events
- Login attempts (success/failure)
- Logout
- Password changes
- Session creation/termination

### Transaction Events
- Transaction creation
- Transaction posting
- Transaction voiding
- Approval requests

### Account Events
- Account creation
- Account updates
- Account deactivation

### Administrative Events
- User approval/decline
- Company activation
- Settings changes

## Use Cases

- **UC-AUD-001**: View Activity Log
- **UC-AUD-002**: Search Audit Trail
- **UC-AUD-003**: Generate Audit Report
- **UC-AUD-004**: View User Activity

## Domain Events

- **ActivityLogged**
- **SnapshotCreated**

## Integration

Consumes ALL domain events from:
- Identity & Access Management
- Company Management
- Transaction Processing
- Approval Workflow
- All other subsystems

## Documentation

- **Domain Model**: [domain-model.md](./domain-model.md)

---

**Status**: Documented, awaiting implementation
