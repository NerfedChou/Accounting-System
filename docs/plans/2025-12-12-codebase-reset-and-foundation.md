# Codebase Reset & Foundation Setup - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Nuclear option - delete legacy code, preserve domain knowledge, set up professional documentation structure and CI/CD foundation

**Architecture:**
- Documentation-first approach
- DDD/EDA/Hexagonal patterns preparation
- Hybrid event-driven (Event Sourcing for critical domains, traditional for others)
- Comprehensive GitHub Actions CI/CD

**Tech Stack:**
- Backend: PHP (modular, no framework initially)
- Frontend: HTML, CSS, JavaScript (vanilla, pattern-based)
- Database: MySQL 8.0 (student-friendly, widely taught)
- Container: Docker
- CI/CD: GitHub Actions

---

## Task 1: Backup Domain Knowledge

**Files:**
- Preserve: `README.md` (updated with complete domain knowledge)
- Archive: Select legacy documentation files

**Step 1: Create legacy archive directory**

```bash
mkdir -p /home/chef/Github/Accounting-System/legacy-archive
```

**Step 2: Archive useful legacy documentation**

```bash
# Move ERD and flowchart files to archive
mv /home/chef/Github/Accounting-System/FINAL-ERD-COMPREHENSIVE.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/FINAL-SYSTEM-FLOWCHART.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/FINAL_IMPLEMENTATION_PLAN.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/*.drawio /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/COMPLETE_*.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/COMPANY_*.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/QUICK_FIX_REFERENCE.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/SYSTEM-*.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/VISUAL-*.md /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
mv /home/chef/Github/Accounting-System/system-diagrams.html /home/chef/Github/Accounting-System/legacy-archive/ 2>/dev/null || true
```

**Step 3: Verify README.md is preserved**

Run: `ls -lh /home/chef/Github/Accounting-System/README.md`
Expected: File exists with recent timestamp

**Step 4: Create archive README**

Write file: `/home/chef/Github/Accounting-System/legacy-archive/README.md`
```markdown
# Legacy Codebase Archive

This directory contains documentation from the legacy PHP accounting system (2024).

The legacy system was a learning project that taught valuable lessons about:
- Double-entry bookkeeping implementation
- Multi-tenant architecture challenges
- The importance of proper architecture from day one

**Domain knowledge has been extracted to:** `/README.md`

**Do not use this code.** It contains security vulnerabilities and architectural issues.
Reference for domain logic understanding only.

Date archived: 2025-12-12
```

**Step 5: Commit archive**

```bash
git add legacy-archive/ README.md
git commit -m "docs: archive legacy documentation, preserve domain knowledge

- Move legacy docs to archive
- Keep updated README.md with extracted domain knowledge
- Prepare for fresh start with proper architecture"
```

---

## Task 2: Nuclear Option - Delete Legacy Codebase

**Files:**
- Delete: `/src` directory (all PHP, HTML, CSS, JS)
- Delete: `/docker` directory (legacy Docker configs)
- Delete: `docker-compose.yml` (will recreate from scratch)

**Step 1: Remove source code directory**

```bash
rm -rf /home/chef/Github/Accounting-System/src
```

**Step 2: Remove legacy Docker configuration**

```bash
rm -rf /home/chef/Github/Accounting-System/docker
rm -f /home/chef/Github/Accounting-System/docker-compose.yml
```

**Step 3: Verify deletion**

Run: `ls -la /home/chef/Github/Accounting-System/`
Expected: Only `.git/`, `README.md`, `.env.example`, `.gitignore`, `legacy-archive/`, `docs/` remain

**Step 4: Clean up root-level markdown files**

```bash
cd /home/chef/Github/Accounting-System
find . -maxdepth 1 -name "*.md" ! -name "README.md" -delete
```

**Step 5: Commit deletion**

```bash
git add -A
git commit -m "chore: nuclear option - remove legacy codebase

- Delete /src directory (legacy PHP/HTML/CSS/JS)
- Delete /docker and docker-compose.yml
- Clean slate for architecture-first rebuild
- Domain knowledge preserved in README.md"
```

---

## Task 3: Create Documentation Structure

**Files:**
- Create: Complete `/docs` hierarchy

**Step 1: Create documentation directories**

```bash
cd /home/chef/Github/Accounting-System

# Create main documentation structure
mkdir -p docs/01-architecture
mkdir -p docs/02-subsystems/{identity,company-management,chart-of-accounts,transaction-processing,ledger-posting,financial-reporting,audit-trail,approval-workflow}
mkdir -p docs/03-algorithms
mkdir -p docs/04-api
mkdir -p docs/05-deployment
mkdir -p docs/06-testing
mkdir -p docs/plans
```

**Step 2: Create architecture overview**

Write file: `/home/chef/Github/Accounting-System/docs/01-architecture/overview.md`

```markdown
# System Architecture Overview

## Vision

A robust, modular accounting system built with:
- **Domain-Driven Design (DDD)**: Business logic isolated in domain models
- **Event-Driven Architecture (EDA)**: Subsystems communicate via events
- **Hexagonal Architecture**: Plug-and-play adapters for different technologies
- **Test-Driven Development (TDD)**: Tests written before implementation

## Architectural Principles

### 1. Bounded Contexts (DDD)

Each subsystem is a bounded context with:
- Clear domain models (Entities, Value Objects, Aggregates)
- Explicit boundaries
- Independent evolution
- Well-defined integration points

### 2. Ports & Adapters (Hexagonal)

```
┌─────────────────────────────────────────┐
│           Domain Core                    │
│  (Business Logic, Algorithms)            │
│                                          │
│  ┌────────────────────────────────┐     │
│  │   Entities & Value Objects      │     │
│  │   Domain Services               │     │
│  │   Repository Interfaces (Ports) │     │
│  └────────────────────────────────┘     │
└──────────────┬──────────────────────────┘
               │
    ┌──────────┼──────────┐
    │          │          │
┌───▼───┐  ┌──▼───┐  ┌──▼────┐
│  DB   │  │ HTTP │  │ Event │  Adapters
│Adapter│  │ API  │  │ Bus   │  (Implementations)
└───────┘  └──────┘  └───────┘
```

**Benefits:**
- Swap PostgreSQL for MySQL without touching domain logic
- Replace HTTP API with GraphQL easily
- Add event bus (RabbitMQ, Kafka) later
- Mock adapters for testing

### 3. Event-Driven Communication

**Hybrid Approach:**

**Event Sourcing for:**
- Transaction Processing (complete audit trail)
- Ledger Posting (state reconstruction)
- Approval Workflow (full history)

**Traditional Storage for:**
- Identity & Access Management
- Company Management
- Chart of Accounts

**Event Flow Example:**
```
Transaction Created
    ↓
TransactionValidated
    ↓
TransactionPosted
    ↓
LedgerUpdated + AccountBalanceChanged
    ↓
FinancialReportInvalidated
```

### 4. Subsystem Isolation

Each subsystem:
- Has its own database schema/tables
- Exposes well-defined ports (interfaces)
- Publishes domain events
- Subscribes to relevant events
- Can be deployed independently (future microservices)

## Technology Stack

| Layer | Technology | Why |
|-------|------------|-----|
| Backend | PHP 8.2+ | Required, modular structure |
| Frontend | HTML/CSS/JS | Vanilla, component patterns |
| Database | PostgreSQL | ACID compliance, JSON support |
| Events | Simple Event Bus (PHP) | Start simple, upgrade later |
| Container | Docker | Consistent environments |
| CI/CD | GitHub Actions | Automated testing & deployment |

## Quality Gates

Every change must:
1. ✅ Have tests (TDD)
2. ✅ Pass CI checks
3. ✅ Follow architecture patterns
4. ✅ Update documentation
5. ✅ Maintain domain integrity

## Next Steps

See subsystem documentation in `/docs/02-subsystems/` for detailed designs.
```

**Step 3: Create bounded contexts document**

Write file: `/home/chef/Github/Accounting-System/docs/01-architecture/bounded-contexts.md`

```markdown
# Bounded Contexts (DDD)

## Overview

The accounting system is divided into 8 bounded contexts, each with clear responsibilities and boundaries.

---

## 1. Identity & Access Management

**Responsibility:** User authentication, authorization, role management

**Core Concepts:**
- User (Entity)
- Role (Value Object): Admin, Tenant
- Permission (Value Object)
- Session (Entity)

**Events Published:**
- `UserRegistered`
- `UserAuthenticated`
- `UserDeactivated`
- `RoleAssigned`

**Events Consumed:** None (root context)

**Domain Rules:**
- Passwords must be hashed (bcrypt)
- Sessions expire after inactivity
- Deactivated users cannot authenticate

---

## 2. Company Management

**Responsibility:** Multi-tenant company data, company lifecycle

**Core Concepts:**
- Company (Aggregate Root)
- CompanyId (Value Object)
- Address (Value Object)
- Currency (Value Object)

**Events Published:**
- `CompanyCreated`
- `CompanyActivated`
- `CompanyDeactivated`

**Events Consumed:**
- `UserRegistered` → Create company association

**Domain Rules:**
- Each tenant belongs to one company
- Deactivating company cascades to users
- Currency code must be valid ISO 4217

---

## 3. Chart of Accounts

**Responsibility:** Account structure, account types, account hierarchy

**Core Concepts:**
- Account (Aggregate Root)
- AccountType (Value Object): Asset, Liability, Equity, Revenue, Expense
- NormalBalance (Value Object): Debit, Credit
- AccountCode (Value Object)

**Events Published:**
- `AccountCreated`
- `AccountActivated`
- `AccountDeactivated`

**Events Consumed:**
- `CompanyCreated` → Initialize default chart of accounts

**Domain Rules:**
- Account codes unique per company
- Normal balance determined by account type
- Cannot deactivate accounts with non-zero balance

---

## 4. Transaction Processing

**Responsibility:** Transaction creation, validation, double-entry rules

**Core Concepts:**
- Transaction (Aggregate Root)
- TransactionLine (Entity)
- LineType (Value Object): Debit, Credit
- TransactionStatus (Value Object): Pending, Posted, Voided

**Events Published:**
- `TransactionCreated`
- `TransactionValidated`
- `TransactionApprovalRequired`
- `TransactionPosted`
- `TransactionVoided`

**Events Consumed:**
- `AccountCreated` → Account available for transactions
- `ApprovalGranted` → Post transaction

**Domain Rules:**
- Debits must equal credits
- Minimum 2 lines per transaction
- All amounts must be positive
- Pending transactions can be edited
- Posted transactions cannot be modified (only voided)

---

## 5. Ledger & Posting

**Responsibility:** Account balance management, posting transactions to ledger

**Core Concepts:**
- Ledger (Aggregate Root)
- AccountBalance (Entity)
- BalanceChange (Value Object)

**Events Published:**
- `LedgerUpdated`
- `AccountBalanceChanged`
- `NegativeBalanceDetected`

**Events Consumed:**
- `TransactionPosted` → Update account balances
- `TransactionVoided` → Reverse balance changes

**Domain Rules:**
- Balance changes calculated from normal balance + line type
- Asset/Liability/Revenue/Expense cannot go negative
- Equity can go negative with approval
- Accounting equation must remain balanced

---

## 6. Financial Reporting

**Responsibility:** Generate financial reports, maintain report cache

**Core Concepts:**
- BalanceSheet (Aggregate)
- IncomeStatement (Aggregate)
- ReportPeriod (Value Object)

**Events Published:**
- `ReportGenerated`

**Events Consumed:**
- `AccountBalanceChanged` → Invalidate cached reports
- `TransactionPosted` → Trigger report recalculation

**Domain Rules:**
- Reports reflect real-time balances
- Balance sheet must balance (Assets = Liabilities + Equity)
- Income statement calculates net income (Revenue - Expenses)

---

## 7. Audit Trail

**Responsibility:** Immutable activity log, compliance tracking

**Core Concepts:**
- ActivityLog (Entity)
- ActivityType (Value Object)
- Actor (Value Object): User who performed action

**Events Published:** None (sink context)

**Events Consumed:** ALL events from all contexts

**Domain Rules:**
- Logs are immutable (append-only)
- Every user action logged
- IP address captured for security
- Retention: permanent

---

## 8. Approval Workflow

**Responsibility:** Transaction approval routing, approval tracking

**Core Concepts:**
- Approval (Aggregate Root)
- ApprovalStatus (Value Object): Pending, Approved, Declined
- Approver (Entity)

**Events Published:**
- `ApprovalRequested`
- `ApprovalGranted`
- `ApprovalDenied`

**Events Consumed:**
- `TransactionApprovalRequired` → Create approval request
- `NegativeBalanceDetected` → Route to admin approval

**Domain Rules:**
- Only admins can approve
- Declined approvals require reason
- Approval grants are logged
- Once approved, cannot be unapproved

---

## Context Map

```
┌─────────────┐
│  Identity   │
│  & Access   │
└──────┬──────┘
       │ provides authentication
       ↓
┌─────────────┐      ┌─────────────┐
│   Company   │─────→│  Chart of   │
│ Management  │      │  Accounts   │
└──────┬──────┘      └──────┬──────┘
       │                    │
       │ provides company   │ provides accounts
       │                    │
       ↓                    ↓
┌─────────────┐      ┌─────────────┐
│Transaction  │─────→│   Ledger    │
│ Processing  │posts │  & Posting  │
└──────┬──────┘      └──────┬──────┘
       │                    │
       │                    │ balance updates
       ↓                    ↓
┌─────────────┐      ┌─────────────┐
│  Approval   │      │  Financial  │
│  Workflow   │      │  Reporting  │
└─────────────┘      └─────────────┘
       │                    │
       └────────┬───────────┘
                │ all events
                ↓
         ┌─────────────┐
         │ Audit Trail │
         └─────────────┘
```

## Integration Patterns

**Upstream/Downstream:**
- Identity is upstream (provides auth to all)
- Audit Trail is downstream (consumes all events)

**Shared Kernel:** None (strict boundaries)

**Anti-Corruption Layers:** Event adapters for each context
```

**Step 4: Create hexagonal architecture document**

Write file: `/home/chef/Github/Accounting-System/docs/01-architecture/hexagonal-architecture.md`

```markdown
# Hexagonal Architecture (Ports & Adapters)

## Principle

Business logic (domain) is isolated in the center. All external dependencies are accessed through interfaces (ports). Different implementations (adapters) can be plugged in without changing the domain.

---

## Structure

```
/src
├── Domain/                      # The Core (Business Logic)
│   ├── Transaction/
│   │   ├── Entity/              # Domain entities
│   │   │   ├── Transaction.php
│   │   │   └── TransactionLine.php
│   │   ├── ValueObject/         # Immutable value objects
│   │   │   ├── TransactionId.php
│   │   │   ├── LineType.php
│   │   │   └── Amount.php
│   │   ├── Repository/          # Repository interfaces (PORTS)
│   │   │   └── TransactionRepositoryInterface.php
│   │   ├── Service/             # Domain services
│   │   │   └── TransactionValidator.php
│   │   └── Event/               # Domain events
│   │       ├── TransactionCreated.php
│   │       └── TransactionPosted.php
│   └── ...other domains...
│
├── Application/                 # Use Cases (Application Logic)
│   ├── Transaction/
│   │   ├── CreateTransaction/
│   │   │   ├── CreateTransactionCommand.php
│   │   │   └── CreateTransactionHandler.php
│   │   └── PostTransaction/
│   │       ├── PostTransactionCommand.php
│   │       └── PostTransactionHandler.php
│   └── ...other use cases...
│
└── Infrastructure/              # Adapters (IMPLEMENTATIONS)
    ├── Persistence/             # Database adapters
    │   ├── PostgreSQL/
    │   │   └── PostgreSQLTransactionRepository.php
    │   └── InMemory/            # For testing
    │       └── InMemoryTransactionRepository.php
    ├── Http/                    # HTTP adapters
    │   ├── Controller/
    │   │   └── TransactionController.php
    │   └── Middleware/
    │       └── AuthenticationMiddleware.php
    ├── Event/                   # Event bus adapters
    │   └── SimpleEventBus.php   # Start simple
    └── Cli/                     # CLI adapters
        └── Command/
            └── MigrateCommand.php
```

---

## Example: Transaction Repository Port

**Domain Port (Interface):**

```php
<?php
// src/Domain/Transaction/Repository/TransactionRepositoryInterface.php

namespace Domain\Transaction\Repository;

use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\ValueObject\TransactionId;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): void;

    public function findById(TransactionId $id): ?Transaction;

    public function findByCompany(string $companyId): array;

    public function delete(TransactionId $id): void;
}
```

**PostgreSQL Adapter (Implementation):**

```php
<?php
// src/Infrastructure/Persistence/PostgreSQL/PostgreSQLTransactionRepository.php

namespace Infrastructure\Persistence\PostgreSQL;

use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\ValueObject\TransactionId;

class PostgreSQLTransactionRepository implements TransactionRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Transaction $transaction): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO transactions (id, company_id, description, ...)
            VALUES (:id, :company_id, :description, ...)
        ");

        $stmt->execute([
            'id' => $transaction->getId()->value(),
            'company_id' => $transaction->getCompanyId(),
            'description' => $transaction->getDescription(),
            // ... other fields
        ]);
    }

    public function findById(TransactionId $id): ?Transaction
    {
        // Implementation
    }

    // ... other methods
}
```

**In-Memory Adapter (For Testing):**

```php
<?php
// src/Infrastructure/Persistence/InMemory/InMemoryTransactionRepository.php

namespace Infrastructure\Persistence\InMemory;

use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\ValueObject\TransactionId;

class InMemoryTransactionRepository implements TransactionRepositoryInterface
{
    private array $transactions = [];

    public function save(Transaction $transaction): void
    {
        $this->transactions[$transaction->getId()->value()] = $transaction;
    }

    public function findById(TransactionId $id): ?Transaction
    {
        return $this->transactions[$id->value()] ?? null;
    }

    // ... other methods
}
```

---

## Benefits

1. **Testability**: Use in-memory adapters for fast unit tests
2. **Flexibility**: Swap PostgreSQL for MySQL by changing adapter registration
3. **Technology Independence**: Domain logic doesn't depend on framework
4. **Future-Proof**: Easy to add new adapters (GraphQL, gRPC, message queue)

---

## Dependency Rule

Dependencies flow inward:

```
Infrastructure → Application → Domain

Domain depends on NOTHING
Application depends on Domain interfaces
Infrastructure depends on Application and implements Domain ports
```

**Never:** Domain code imports from Infrastructure
**Always:** Infrastructure implements Domain interfaces
```

**Step 5: Create event catalog**

Write file: `/home/chef/Github/Accounting-System/docs/01-architecture/event-catalog.md`

```markdown
# Event Catalog

Complete list of domain events in the system.

## Naming Convention

- Past tense (event already happened)
- Domain-specific language
- Include aggregate ID in payload

---

## Identity & Access Management

### UserRegistered
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "userId": "uuid",
  "username": "string",
  "email": "string",
  "role": "admin|tenant",
  "companyId": "uuid|null"
}
```

### UserAuthenticated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "userId": "uuid",
  "ipAddress": "string",
  "sessionId": "uuid"
}
```

### UserDeactivated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "userId": "uuid",
  "reason": "string",
  "deactivatedBy": "uuid"
}
```

---

## Company Management

### CompanyCreated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "companyId": "uuid",
  "companyName": "string",
  "currencyCode": "string",
  "createdBy": "uuid"
}
```

### CompanyDeactivated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "companyId": "uuid",
  "reason": "string",
  "deactivatedBy": "uuid"
}
```

---

## Chart of Accounts

### AccountCreated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "accountId": "uuid",
  "companyId": "uuid",
  "accountCode": "string",
  "accountName": "string",
  "accountType": "asset|liability|equity|revenue|expense",
  "normalBalance": "debit|credit",
  "openingBalance": "decimal",
  "createdBy": "uuid"
}
```

---

## Transaction Processing

### TransactionCreated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "transactionId": "uuid",
  "companyId": "uuid",
  "transactionDate": "date",
  "description": "string",
  "lines": [
    {
      "accountId": "uuid",
      "lineType": "debit|credit",
      "amount": "decimal"
    }
  ],
  "totalAmount": "decimal",
  "createdBy": "uuid"
}
```

### TransactionValidated
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "transactionId": "uuid",
  "isValid": true,
  "validationResults": {
    "debitsEqualCredits": true,
    "accountingEquationMaintained": true,
    "noNegativeBalances": true
  }
}
```

### TransactionPosted
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "transactionId": "uuid",
  "companyId": "uuid",
  "postedBy": "uuid",
  "balanceChanges": [
    {
      "accountId": "uuid",
      "previousBalance": "decimal",
      "newBalance": "decimal",
      "change": "decimal"
    }
  ]
}
```

### TransactionVoided
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "transactionId": "uuid",
  "voidReason": "string",
  "voidedBy": "uuid"
}
```

---

## Ledger & Posting

### AccountBalanceChanged
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "accountId": "uuid",
  "companyId": "uuid",
  "previousBalance": "decimal",
  "newBalance": "decimal",
  "change": "decimal",
  "causedBy": "transactionId"
}
```

### NegativeBalanceDetected
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "accountId": "uuid",
  "accountType": "equity",
  "projectedBalance": "decimal",
  "requiresApproval": true
}
```

---

## Approval Workflow

### ApprovalRequested
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "approvalId": "uuid",
  "transactionId": "uuid",
  "reason": "negative_equity",
  "requestedBy": "uuid"
}
```

### ApprovalGranted
```json
{
  "eventId": "uuid",
  "occurredAt": "2025-12-12T10:30:00Z",
  "approvalId": "uuid",
  "transactionId": "uuid",
  "approvedBy": "uuid"
}
```

---

## Event Subscriptions

| Event | Consumed By |
|-------|-------------|
| UserRegistered | Company Management, Audit Trail |
| CompanyCreated | Chart of Accounts, Audit Trail |
| AccountCreated | Transaction Processing, Audit Trail |
| TransactionPosted | Ledger & Posting, Financial Reporting, Audit Trail |
| AccountBalanceChanged | Financial Reporting, Audit Trail |
| ApprovalRequested | Approval Workflow, Audit Trail |
| ALL | Audit Trail |
```

**Step 6: Create technology decisions document**

Write file: `/home/chef/Github/Accounting-System/docs/01-architecture/technology-decisions.md`

```markdown
# Architecture Decision Records (ADRs)

## ADR-001: PHP as Backend Language

**Status:** Accepted

**Context:** Need to choose backend language for rebuild

**Decision:** Use PHP 8.2+ with modern practices

**Rationale:**
- Required by project constraints
- PHP 8.2+ has improved type system
- Can implement DDD patterns
- Supports interface-based design

**Consequences:**
- Must be disciplined with architecture (no framework safety net initially)
- Type hints required everywhere
- Strict separation of concerns crucial

---

## ADR-002: No Framework Initially

**Status:** Accepted

**Context:** Framework choice (Laravel, Symfony, etc.)

**Decision:** Start with vanilla PHP, modular structure, add framework later if needed

**Rationale:**
- Learn architecture patterns deeply
- Avoid framework lock-in
- Full control over structure
- Can adopt framework incrementally (Symfony components, etc.)

**Consequences:**
- More manual setup initially
- Must build own routing, DI container
- Documentation is critical

---

## ADR-003: PostgreSQL as Primary Database

**Status:** Accepted

**Context:** Database selection for financial data

**Decision:** Use PostgreSQL

**Rationale:**
- ACID compliance (critical for accounting)
- JSON support for event storage
- Better for complex queries
- Strong consistency guarantees
- Better for financial precision (NUMERIC type)

**Consequences:**
- Requires PostgreSQL knowledge
- Different from legacy MySQL

---

## ADR-004: Hybrid Event Architecture

**Status:** Accepted

**Context:** Event Sourcing vs Traditional Storage

**Decision:** Hybrid - Event Sourcing for critical domains, traditional for others

**Rationale:**
- Transaction processing needs complete audit trail
- Event Sourcing perfect for accounting compliance
- Simpler domains don't need event complexity
- Flexibility to evolve

**Event Sourced:**
- Transaction Processing
- Ledger & Posting
- Approval Workflow

**Traditional:**
- Identity & Access
- Company Management
- Chart of Accounts
- Financial Reporting (read models)

**Consequences:**
- Need event store implementation
- Two persistence strategies to maintain
- Clear benefits outweigh complexity

---

## ADR-005: Hexagonal Architecture

**Status:** Accepted

**Context:** How to structure codebase for flexibility

**Decision:** Strict hexagonal architecture (ports & adapters)

**Rationale:**
- Business logic isolated from infrastructure
- Easy to test (mock adapters)
- Can swap databases, APIs, event buses
- Future-proof for microservices

**Consequences:**
- More interfaces/abstractions
- Requires discipline
- Steeper learning curve
- Long-term maintainability worth it

---

## ADR-006: Documentation-First Approach

**Status:** Accepted

**Context:** When to write documentation

**Decision:** Write docs BEFORE code, keep synchronized

**Rationale:**
- Previous project suffered from no architecture
- Documentation forces design thinking
- Easier to refine on paper than in code
- Acts as contract between subsystems

**Consequences:**
- Slower initial progress
- Must keep docs updated
- Worth it for long-term clarity

---

## ADR-007: Comprehensive CI/CD from Day One

**Status:** Accepted

**Context:** When to set up automation

**Decision:** GitHub Actions from the start

**Rationale:**
- Prevent regression immediately
- Enforce quality gates
- Automate tedious tasks
- Professional workflow from beginning

**Pipeline:**
- Linting (PHP_CodeSniffer)
- Type checking (PHPStan level 8)
- Unit tests (PHPUnit)
- Integration tests
- Security scanning
- Docker image building

**Consequences:**
- Setup time upfront
- Must maintain CI config
- Catches issues early

---

## ADR-008: Test-Driven Development (TDD)

**Status:** Accepted

**Context:** Testing approach

**Decision:** Write tests BEFORE implementation

**Rationale:**
- Previous project had zero tests
- TDD forces good design
- Ensures testable code
- Acts as living documentation

**Test Types:**
- Unit tests (domain logic)
- Integration tests (adapters)
- Acceptance tests (use cases)

**Consequences:**
- Slower initial development
- Better quality code
- Fewer bugs in production
```

**Step 7: Verify documentation structure**

Run: `tree /home/chef/Github/Accounting-System/docs/01-architecture -L 2`
Expected: All files created successfully

**Step 8: Commit documentation structure**

```bash
git add docs/
git commit -m "docs: create architecture documentation structure

- Add system architecture overview
- Document bounded contexts (8 subsystems)
- Explain hexagonal architecture pattern
- Create complete event catalog
- Document architecture decision records (ADRs)
- Prepare for subsystem documentation"
```

---

## Task 4: Create Subsystem Documentation Templates

**Files:**
- Create: Template documentation for each subsystem

**Step 1: Create Identity subsystem documentation**

Write file: `/home/chef/Github/Accounting-System/docs/02-subsystems/identity/domain-model.md`

```markdown
# Identity & Access Management - Domain Model

## Aggregate: User

**Aggregate Root:** User entity

### Entities

#### User
- `userId`: UserId (Value Object)
- `username`: string (unique)
- `email`: Email (Value Object)
- `passwordHash`: string (bcrypt)
- `role`: Role (Value Object)
- `companyId`: CompanyId | null
- `isActive`: boolean
- `registrationStatus`: RegistrationStatus (Value Object)
- `lastLogin`: DateTime | null

### Value Objects

#### UserId
- UUID format
- Immutable

#### Email
- Validation: RFC 5322
- Lowercase normalization

#### Role
- Enum: ADMIN, TENANT
- Validation: must be valid role

#### RegistrationStatus
- Enum: PENDING, APPROVED, DECLINED
- Transitions:
  - PENDING → APPROVED (admin approval)
  - PENDING → DECLINED (admin rejection)
  - Cannot go backwards

### Domain Services

#### AuthenticationService
- `authenticate(username, password): User | null`
- `hashPassword(password): string`
- `verifyPassword(password, hash): boolean`

#### AuthorizationService
- `canAccess(user, resource): boolean`
- `hasRole(user, role): boolean`

### Repository Interface

```php
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByUsername(string $username): ?User;
    public function findByEmail(Email $email): ?User;
    public function findPendingRegistrations(): array;
}
```

### Domain Events

- `UserRegistered`
- `UserAuthenticated`
- `UserDeactivated`
- `RoleAssigned`
- `RegistrationApproved`
- `RegistrationDeclined`

### Business Rules

1. Username must be unique
2. Email must be unique
3. Passwords must be hashed (bcrypt, cost 12)
4. Deactivated users cannot authenticate
5. Pending users cannot authenticate
6. Admin users have no company association
7. Tenant users must have company association
```

Write file: `/home/chef/Github/Accounting-System/docs/02-subsystems/identity/algorithms.md`

```markdown
# Identity & Access Management - Algorithms

## Algorithm 1: Password Hashing

**Purpose:** Securely hash user passwords

**Input:**
- `plainPassword`: string (user-provided password)

**Output:**
- `hash`: string (bcrypt hash)

**Algorithm:**
```
FUNCTION hashPassword(plainPassword):
    IF length(plainPassword) < 8:
        THROW "Password must be at least 8 characters"

    IF NOT containsUppercase(plainPassword):
        THROW "Password must contain uppercase letter"

    IF NOT containsLowercase(plainPassword):
        THROW "Password must contain lowercase letter"

    IF NOT containsDigit(plainPassword):
        THROW "Password must contain digit"

    hash = bcrypt(plainPassword, cost=12)
    RETURN hash
END FUNCTION
```

**PHP Implementation:**
```php
public function hashPassword(string $plainPassword): string
{
    // Validate complexity
    if (strlen($plainPassword) < 8) {
        throw new InvalidPasswordException("Password must be at least 8 characters");
    }

    if (!preg_match('/[A-Z]/', $plainPassword)) {
        throw new InvalidPasswordException("Password must contain uppercase letter");
    }

    if (!preg_match('/[a-z]/', $plainPassword)) {
        throw new InvalidPasswordException("Password must contain lowercase letter");
    }

    if (!preg_match('/[0-9]/', $plainPassword)) {
        throw new InvalidPasswordException("Password must contain digit");
    }

    // Hash with bcrypt
    return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

---

## Algorithm 2: User Authentication

**Purpose:** Authenticate user credentials

**Input:**
- `username`: string
- `plainPassword`: string
- `ipAddress`: string

**Output:**
- `User` | null

**Algorithm:**
```
FUNCTION authenticate(username, plainPassword, ipAddress):
    user = userRepository.findByUsername(username)

    IF user IS NULL:
        auditLog("Failed login attempt", username, ipAddress)
        RETURN NULL

    IF NOT user.isActive:
        auditLog("Inactive user login attempt", username, ipAddress)
        RETURN NULL

    IF user.registrationStatus != APPROVED:
        auditLog("Unapproved user login attempt", username, ipAddress)
        RETURN NULL

    IF NOT verifyPassword(plainPassword, user.passwordHash):
        auditLog("Invalid password", username, ipAddress)
        RETURN NULL

    user.updateLastLogin(NOW())
    userRepository.save(user)

    publishEvent(UserAuthenticated(user.id, ipAddress))
    auditLog("Successful login", username, ipAddress)

    RETURN user
END FUNCTION
```

---

## Algorithm 3: Authorization Check

**Purpose:** Check if user can access resource

**Input:**
- `user`: User
- `resource`: string (e.g., "company:123:transactions")
- `action`: string (e.g., "read", "write")

**Output:**
- `boolean`

**Algorithm:**
```
FUNCTION canAccess(user, resource, action):
    IF user.role == ADMIN:
        RETURN TRUE  # Admins have full access

    IF user.role == TENANT:
        resourceCompanyId = extractCompanyId(resource)

        IF resourceCompanyId != user.companyId:
            RETURN FALSE  # Tenant can only access own company

        RETURN TRUE

    RETURN FALSE
END FUNCTION
```

**PHP Implementation:**
```php
public function canAccess(User $user, string $resource, string $action): bool
{
    // Admins have full access
    if ($user->getRole()->equals(Role::admin())) {
        return true;
    }

    // Tenants can only access their own company resources
    if ($user->getRole()->equals(Role::tenant())) {
        $resourceCompanyId = $this->extractCompanyId($resource);

        if ($resourceCompanyId === null) {
            return false; // Resource doesn't belong to any company
        }

        return $user->getCompanyId()->equals($resourceCompanyId);
    }

    return false;
}

private function extractCompanyId(string $resource): ?CompanyId
{
    // Parse resource string (e.g., "company:123:transactions")
    $parts = explode(':', $resource);

    if (count($parts) < 2 || $parts[0] !== 'company') {
        return null;
    }

    return new CompanyId($parts[1]);
}
```
```

**Step 2: Create Transaction Processing subsystem documentation**

Write file: `/home/chef/Github/Accounting-System/docs/02-subsystems/transaction-processing/domain-model.md`

```markdown
# Transaction Processing - Domain Model

## Aggregate: Transaction

**Aggregate Root:** Transaction entity

### Entities

#### Transaction
- `transactionId`: TransactionId (Value Object)
- `companyId`: CompanyId (Value Object)
- `transactionNumber`: string (auto-generated, TXN-XXXXXX)
- `transactionDate`: Date (Value Object)
- `description`: string
- `lines`: Collection<TransactionLine>
- `status`: TransactionStatus (Value Object)
- `totalAmount`: Money (Value Object)
- `requiresApproval`: boolean
- `createdBy`: UserId
- `postedBy`: UserId | null
- `postedAt`: DateTime | null

#### TransactionLine
- `lineId`: LineId (Value Object)
- `accountId`: AccountId (Value Object)
- `lineType`: LineType (Value Object)
- `amount`: Money (Value Object)

### Value Objects

#### TransactionId
- UUID format

#### LineType
- Enum: DEBIT, CREDIT

#### TransactionStatus
- Enum: PENDING, POSTED, VOIDED
- State transitions defined

#### Money
- `amount`: Decimal (precision 15, scale 2)
- `currency`: Currency (Value Object)
- Validation: amount > 0

### Domain Services

#### TransactionValidator
- `validateDoubleEntry(lines): ValidationResult`
- `validateAccountingEquation(transaction, currentBalances): ValidationResult`
- `validateNoNegativeBalances(transaction, accounts): ValidationResult`

#### BalanceCalculator
- `calculateBalanceChange(normalBalance, lineType, amount): Decimal`

### Repository Interface

```php
interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): void;
    public function findById(TransactionId $id): ?Transaction;
    public function findByCompany(CompanyId $companyId): array;
    public function findPendingApprovals(): array;
    public function getNextTransactionNumber(): string;
}
```

### Domain Events

- `TransactionCreated`
- `TransactionValidated`
- `TransactionApprovalRequired`
- `TransactionPosted`
- `TransactionVoided`

### Business Rules

1. **Double-Entry Rule**: Sum(debits) MUST equal Sum(credits)
2. **Minimum Lines**: At least 2 lines (1 debit, 1 credit minimum)
3. **Positive Amounts**: All line amounts must be > 0
4. **Status Transitions**:
   - PENDING → POSTED (post action)
   - PENDING → VOIDED (delete action)
   - POSTED → VOIDED (void action, admin only)
   - Cannot transition from POSTED to PENDING
5. **Immutability**: Posted transactions cannot be edited (only voided)
6. **Approval Required**: Set when transaction would cause negative equity
```

Write file: `/home/chef/Github/Accounting-System/docs/02-subsystems/transaction-processing/algorithms.md`

```markdown
# Transaction Processing - Algorithms

## Algorithm 1: Double-Entry Validation

**Purpose:** Ensure debits equal credits

**Input:**
- `lines`: Collection<TransactionLine>

**Output:**
- `ValidationResult` (valid: boolean, message: string)

**Algorithm:**
```
FUNCTION validateDoubleEntry(lines):
    totalDebits = 0
    totalCredits = 0

    FOR EACH line IN lines:
        IF line.lineType == DEBIT:
            totalDebits += line.amount
        ELSE IF line.lineType == CREDIT:
            totalCredits += line.amount

    difference = abs(totalDebits - totalCredits)

    IF difference < 0.01:  # Allow 1 cent tolerance for rounding
        RETURN ValidationResult(valid=TRUE, message="Transaction balanced")
    ELSE:
        RETURN ValidationResult(
            valid=FALSE,
            message="Debits ($totalDebits) must equal Credits ($totalCredits)"
        )
END FUNCTION
```

**PHP Implementation:**
```php
public function validateDoubleEntry(array $lines): ValidationResult
{
    $totalDebits = 0.0;
    $totalCredits = 0.0;

    foreach ($lines as $line) {
        if ($line->getLineType()->equals(LineType::debit())) {
            $totalDebits += $line->getAmount()->toFloat();
        } elseif ($line->getLineType()->equals(LineType::credit())) {
            $totalCredits += $line->getAmount()->toFloat();
        }
    }

    $difference = abs($totalDebits - $totalCredits);

    if ($difference < 0.01) {
        return ValidationResult::valid("Transaction balanced");
    }

    return ValidationResult::invalid(
        sprintf(
            "Debits (%.2f) must equal Credits (%.2f). Difference: %.2f",
            $totalDebits,
            $totalCredits,
            $difference
        )
    );
}
```

---

## Algorithm 2: Balance Change Calculation

**Purpose:** Calculate how a transaction line affects account balance

**Input:**
- `normalBalance`: NormalBalance (DEBIT or CREDIT)
- `lineType`: LineType (DEBIT or CREDIT)
- `amount`: Decimal

**Output:**
- `balanceChange`: Decimal (positive = increase, negative = decrease)

**Algorithm:**
```
FUNCTION calculateBalanceChange(normalBalance, lineType, amount):
    # Core accounting principle:
    # If the transaction side MATCHES the account's normal balance → INCREASE
    # If the transaction side OPPOSES the account's normal balance → DECREASE

    IF normalBalance == lineType:
        RETURN +amount  # Same side = Increase
    ELSE:
        RETURN -amount  # Opposite side = Decrease
END FUNCTION
```

**Examples:**
```
# Asset account (normal balance: DEBIT)
calculateBalanceChange(DEBIT, DEBIT, 100)   → +100 (increase)
calculateBalanceChange(DEBIT, CREDIT, 100)  → -100 (decrease)

# Liability account (normal balance: CREDIT)
calculateBalanceChange(CREDIT, CREDIT, 500) → +500 (increase)
calculateBalanceChange(CREDIT, DEBIT, 500)  → -500 (decrease)
```

**PHP Implementation:**
```php
public function calculateBalanceChange(
    NormalBalance $normalBalance,
    LineType $lineType,
    Money $amount
): float {
    if ($normalBalance->equals($lineType)) {
        return $amount->toFloat(); // Same side = Increase
    }

    return -$amount->toFloat(); // Opposite side = Decrease
}
```

---

## Algorithm 3: Negative Balance Detection

**Purpose:** Detect if transaction would cause prohibited negative balances

**Input:**
- `transaction`: Transaction
- `accounts`: Map<AccountId, Account>

**Output:**
- `ValidationResult`

**Algorithm:**
```
FUNCTION validateNoNegativeBalances(transaction, accounts):
    FOR EACH line IN transaction.lines:
        account = accounts[line.accountId]

        # Calculate projected balance
        balanceChange = calculateBalanceChange(
            account.normalBalance,
            line.lineType,
            line.amount
        )
        projectedBalance = account.currentBalance + balanceChange

        # Check if negative is prohibited for this account type
        cannotBeNegative = [ASSET, LIABILITY, REVENUE, EXPENSE]

        IF account.type IN cannotBeNegative AND projectedBalance < 0:
            RETURN ValidationResult(
                valid=FALSE,
                message="{account.type} accounts cannot have negative balance"
            )

        # Equity can go negative but requires approval
        IF account.type == EQUITY AND projectedBalance < 0:
            transaction.setRequiresApproval(TRUE)
            # Continue validation, don't fail

    RETURN ValidationResult(valid=TRUE)
END FUNCTION
```

---

## Algorithm 4: Accounting Equation Validation

**Purpose:** Ensure accounting equation remains balanced after transaction

**Input:**
- `transaction`: Transaction
- `currentBalances`: Map<AccountType, Decimal>

**Output:**
- `ValidationResult`

**Algorithm:**
```
FUNCTION validateAccountingEquation(transaction, currentBalances):
    # Project balance changes by account type
    projectedBalances = clone(currentBalances)

    FOR EACH line IN transaction.lines:
        account = getAccount(line.accountId)
        change = calculateBalanceChange(account.normalBalance, line.lineType, line.amount)
        projectedBalances[account.type] += change

    # Calculate equation: Assets = Liabilities + Equity + (Revenue - Expenses)
    assets = projectedBalances[ASSET]
    liabilities = projectedBalances[LIABILITY]
    equity = projectedBalances[EQUITY]
    revenue = projectedBalances[REVENUE]
    expenses = projectedBalances[EXPENSE]

    leftSide = assets
    rightSide = liabilities + equity + revenue - expenses

    difference = abs(leftSide - rightSide)

    IF difference < 0.01:  # 1 cent tolerance
        RETURN ValidationResult(valid=TRUE, message="Equation balanced")
    ELSE:
        RETURN ValidationResult(
            valid=FALSE,
            message="Accounting equation violated. Difference: $difference"
        )
END FUNCTION
```

**PHP Implementation:**
```php
public function validateAccountingEquation(
    Transaction $transaction,
    array $currentBalances
): ValidationResult {
    // Clone current balances
    $projectedBalances = $currentBalances;

    // Apply transaction changes
    foreach ($transaction->getLines() as $line) {
        $account = $this->accountRepository->findById($line->getAccountId());

        $change = $this->balanceCalculator->calculateBalanceChange(
            $account->getNormalBalance(),
            $line->getLineType(),
            $line->getAmount()
        );

        $accountType = $account->getType();
        $projectedBalances[$accountType->value()] += $change;
    }

    // Calculate equation
    $assets = $projectedBalances[AccountType::ASSET] ?? 0;
    $liabilities = $projectedBalances[AccountType::LIABILITY] ?? 0;
    $equity = $projectedBalances[AccountType::EQUITY] ?? 0;
    $revenue = $projectedBalances[AccountType::REVENUE] ?? 0;
    $expenses = $projectedBalances[AccountType::EXPENSE] ?? 0;

    $leftSide = $assets;
    $rightSide = $liabilities + $equity + $revenue - $expenses;

    $difference = abs($leftSide - $rightSide);

    if ($difference < 0.01) {
        return ValidationResult::valid("Accounting equation balanced");
    }

    return ValidationResult::invalid(
        sprintf(
            "Accounting equation violated. Assets: %.2f, L+E+R-Ex: %.2f, Difference: %.2f",
            $leftSide,
            $rightSide,
            $difference
        )
    );
}
```
```

**Step 3: Create algorithm documentation**

Write file: `/home/chef/Github/Accounting-System/docs/03-algorithms/double-entry-bookkeeping.md`

```markdown
# Double-Entry Bookkeeping Algorithm

## Core Principle

Every financial transaction affects at least two accounts. The total debits must equal total credits. This fundamental rule maintains the accounting equation:

```
Assets = Liabilities + Equity + (Revenue - Expenses)
```

---

## The Five Account Types

| Account Type | Normal Balance | Increases When | Decreases When |
|--------------|----------------|----------------|----------------|
| Asset        | Debit          | Debited        | Credited       |
| Liability    | Credit         | Credited       | Debited        |
| Equity       | Credit         | Credited       | Debited        |
| Revenue      | Credit         | Credited       | Debited        |
| Expense      | Debit          | Debited        | Credited       |

---

## Balance Change Formula

```
IF (transaction_side == normal_balance):
    balance_change = +amount  // INCREASE
ELSE:
    balance_change = -amount  // DECREASE
```

---

## Examples

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
- Cash: normalBalance=DEBIT, lineType=DEBIT, amount=1000 → +$1,000 ✓
- Service Revenue: normalBalance=CREDIT, lineType=CREDIT, amount=1000 → +$1,000 ✓

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
- Rent Expense: normalBalance=DEBIT, lineType=DEBIT, amount=800 → +$800 ✓
- Cash: normalBalance=DEBIT, lineType=CREDIT, amount=800 → -$800 ✓

**Accounting Equation:**
- Assets -$800
- Expenses +$800
- Equation: Assets = L + E + (R - Ex) → Assets decreased, Ex increased (subtracts from right side) → Balanced ✓

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
- Equipment: normalBalance=DEBIT, lineType=DEBIT, amount=5000 → +$5,000 ✓
- Accounts Payable: normalBalance=CREDIT, lineType=CREDIT, amount=5000 → +$5,000 ✓

**Accounting Equation:**
- Assets +$5,000
- Liabilities +$5,000
- Equation: Assets = L + E + (R - Ex) → Both sides increase equally → Balanced ✓

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
- Cash: normalBalance=DEBIT, lineType=DEBIT, amount=10000 → +$10,000 ✓
- Owner's Capital: normalBalance=CREDIT, lineType=CREDIT, amount=10000 → +$10,000 ✓

**Accounting Equation:**
- Assets +$10,000
- Equity +$10,000
- Equation: Balanced ✓

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
- Bank Loan: normalBalance=CREDIT, lineType=DEBIT, amount=2000 → -$2,000 ✓
- Cash: normalBalance=DEBIT, lineType=CREDIT, amount=2000 → -$2,000 ✓

**Accounting Equation:**
- Assets -$2,000
- Liabilities -$2,000
- Equation: Both sides decrease equally → Balanced ✓

---

## Implementation Pseudocode

```
FUNCTION processTransaction(transaction):
    # Step 1: Validate double-entry
    totalDebits = SUM(line.amount WHERE line.type = DEBIT)
    totalCredits = SUM(line.amount WHERE line.type = CREDIT)

    IF abs(totalDebits - totalCredits) >= 0.01:
        THROW "Transaction unbalanced: debits must equal credits"

    # Step 2: Calculate balance changes
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
            change: change,
            newBalance: account.currentBalance + change
        })

    # Step 3: Validate no prohibited negative balances
    FOR EACH balanceChange IN balanceChanges:
        account = getAccount(balanceChange.accountId)

        IF balanceChange.newBalance < 0:
            cannotBeNegative = [ASSET, LIABILITY, REVENUE, EXPENSE]

            IF account.type IN cannotBeNegative:
                THROW "{account.type} cannot have negative balance"

            IF account.type == EQUITY:
                transaction.requiresApproval = TRUE

    # Step 4: Validate accounting equation
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

    # Step 5: Apply changes
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

---

## Common Mistakes to Avoid

1. **Forgetting to balance**: Always verify debits = credits before posting
2. **Wrong sides**: Remember normal balances (Assets/Expenses = Debit, Liabilities/Equity/Revenue = Credit)
3. **Negative amounts**: Use line type (debit/credit) to indicate direction, not negative numbers
4. **Single entry**: Every transaction needs at least 2 lines (1 debit, 1 credit)
5. **Breaking equation**: Always verify accounting equation after changes

---

## Testing Checklist

For every transaction:
- [ ] Debits equal credits (within 1 cent)
- [ ] Minimum 2 lines (1 debit, 1 credit)
- [ ] All amounts are positive
- [ ] Account balances updated correctly
- [ ] No prohibited negative balances
- [ ] Accounting equation still balanced
- [ ] Proper events published
- [ ] Audit trail recorded
```

**Step 4: Verify subsystem documentation created**

Run: `find /home/chef/Github/Accounting-System/docs/02-subsystems -type f -name "*.md" | head -10`
Expected: Documentation files exist

**Step 5: Commit subsystem documentation**

```bash
git add docs/
git commit -m "docs: add subsystem documentation templates

- Identity & Access Management (domain model + algorithms)
- Transaction Processing (domain model + algorithms)
- Complete double-entry bookkeeping algorithm with examples
- Ready for remaining subsystems"
```

---

## Task 5: Set Up GitHub Actions Workflows

**Files:**
- Create: `.github/workflows/` directory
- Create: CI/CD pipeline configuration

**Step 1: Create workflows directory**

```bash
mkdir -p /home/chef/Github/Accounting-System/.github/workflows
```

**Step 2: Create PHP linting and testing workflow**

Write file: `/home/chef/Github/Accounting-System/.github/workflows/ci.yml`

```yaml
name: Continuous Integration

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  lint:
    name: PHP Code Standards
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_pgsql
          tools: composer, phpcs, phpstan

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run PHP_CodeSniffer
        run: vendor/bin/phpcs --standard=PSR12 src/

      - name: Run PHPStan (Level 8)
        run: vendor/bin/phpstan analyse src/ --level=8

  test:
    name: Unit & Integration Tests
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: accounting_test
          POSTGRES_USER: test_user
          POSTGRES_PASSWORD: test_password
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_pgsql
          tools: composer, phpunit

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Unit Tests
        run: vendor/bin/phpunit --testsuite=unit --coverage-text

      - name: Run Integration Tests
        run: vendor/bin/phpunit --testsuite=integration
        env:
          DB_HOST: localhost
          DB_PORT: 5432
          DB_NAME: accounting_test
          DB_USER: test_user
          DB_PASSWORD: test_password

  security:
    name: Security Scanning
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Security Check (Composer)
        run: composer audit

      - name: Run Psalm Security Analysis
        run: vendor/bin/psalm --show-info=true --taint-analysis
```

**Step 3: Create Docker image build workflow**

Write file: `/home/chef/Github/Accounting-System/.github/workflows/docker-build.yml`

```yaml
name: Docker Image Build

on:
  push:
    branches: [ main ]
    tags:
      - 'v*'
  pull_request:
    branches: [ main ]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push:
    name: Build and Push Docker Image
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to Container Registry
        if: github.event_name != 'pull_request'
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha

      - name: Build and push Docker image
        uses: docker/build-push-action@v5
        with:
          context: .
          push: ${{ github.event_name != 'pull_request' }}
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

**Step 4: Create documentation validation workflow**

Write file: `/home/chef/Github/Accounting-System/.github/workflows/docs-validation.yml`

```yaml
name: Documentation Validation

on:
  push:
    paths:
      - 'docs/**'
      - 'README.md'
  pull_request:
    paths:
      - 'docs/**'
      - 'README.md'

jobs:
  validate-markdown:
    name: Validate Markdown Files
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Lint Markdown files
        uses: DavidAnson/markdownlint-cli2-action@v14
        with:
          globs: |
            README.md
            docs/**/*.md

      - name: Check for broken links
        uses: gaurav-nelson/github-action-markdown-link-check@v1
        with:
          use-quiet-mode: 'yes'
          config-file: '.github/markdown-link-check-config.json'

  validate-structure:
    name: Validate Documentation Structure
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Check required documentation files
        run: |
          required_files=(
            "docs/01-architecture/overview.md"
            "docs/01-architecture/bounded-contexts.md"
            "docs/01-architecture/hexagonal-architecture.md"
            "docs/01-architecture/event-catalog.md"
            "docs/01-architecture/technology-decisions.md"
          )

          for file in "${required_files[@]}"; do
            if [ ! -f "$file" ]; then
              echo "Error: Required file $file is missing"
              exit 1
            fi
          done

          echo "All required documentation files exist ✓"
```

**Step 5: Create markdown link check config**

Write file: `/home/chef/Github/Accounting-System/.github/markdown-link-check-config.json`

```json
{
  "ignorePatterns": [
    {
      "pattern": "^http://localhost"
    }
  ],
  "replacementPatterns": [],
  "httpHeaders": [],
  "timeout": "20s",
  "retryOn429": true,
  "retryCount": 3,
  "fallbackRetryDelay": "30s",
  "aliveStatusCodes": [200, 206]
}
```

**Step 6: Create dependabot configuration**

Write file: `/home/chef/Github/Accounting-System/.github/dependabot.yml`

```yaml
version: 2
updates:
  # Enable version updates for Composer
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 10
    reviewers:
      - "chef"
    labels:
      - "dependencies"
      - "php"

  # Enable version updates for GitHub Actions
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 10
    labels:
      - "dependencies"
      - "github-actions"

  # Enable version updates for Docker
  - package-ecosystem: "docker"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
    labels:
      - "dependencies"
      - "docker"
```

**Step 7: Verify GitHub Actions setup**

Run: `tree /home/chef/Github/Accounting-System/.github -L 2`
Expected: All workflow files created

**Step 8: Commit GitHub Actions**

```bash
git add .github/
git commit -m "ci: set up comprehensive GitHub Actions workflows

- CI pipeline: linting (PSR-12), static analysis (PHPStan), tests
- Docker image building with caching
- Documentation validation (markdown lint, link checking)
- Dependabot for automatic dependency updates
- Security scanning with Psalm taint analysis
- PostgreSQL service for integration tests"
```

---

## Task 6: Create Initial Project Files

**Files:**
- Create: `composer.json` (PHP dependencies)
- Create: `phpunit.xml` (test configuration)
- Create: Basic `.gitignore`

**Step 1: Create composer.json**

Write file: `/home/chef/Github/Accounting-System/composer.json`

```json
{
    "name": "accounting-system/core",
    "description": "Modern accrual-basis double-entry accounting system with DDD/EDA/Hexagonal architecture",
    "type": "project",
    "license": "MIT",
    "authors": [
        {
            "name": "Jan Rhian Angulo",
            "email": "janrhian@example.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "ext-pdo": "*",
        "ext-pdo_pgsql": "*",
        "ext-mbstring": "*",
        "ext-json": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "phpstan/phpstan": "^1.10",
        "squizlabs/php_codesniffer": "^3.8",
        "vimeo/psalm": "^5.18",
        "mockery/mockery": "^1.6"
    },
    "autoload": {
        "psr-4": {
            "Domain\\": "src/Domain/",
            "Application\\": "src/Application/",
            "Infrastructure\\": "src/Infrastructure/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite=unit",
        "test:integration": "phpunit --testsuite=integration",
        "test:coverage": "phpunit --coverage-html coverage",
        "lint": "phpcs --standard=PSR12 src/",
        "lint:fix": "phpcbf --standard=PSR12 src/",
        "analyse": "phpstan analyse src/ --level=8",
        "psalm": "psalm --show-info=true"
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

**Step 2: Create PHPUnit configuration**

Write file: `/home/chef/Github/Accounting-System/phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnWarning="true"
         failOnRisky="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         cacheDirectory=".phpunit.cache">

    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>

    <coverage>
        <report>
            <html outputDirectory="coverage"/>
            <text outputFile="php://stdout" showOnlySummary="true"/>
        </report>
    </coverage>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_HOST" value="localhost"/>
        <env name="DB_PORT" value="5432"/>
        <env name="DB_NAME" value="accounting_test"/>
        <env name="DB_USER" value="test_user"/>
        <env name="DB_PASSWORD" value="test_password"/>
    </php>
</phpunit>
```

**Step 3: Update .gitignore**

Write file: `/home/chef/Github/Accounting-System/.gitignore`

```
# Dependencies
/vendor/
composer.phar
composer.lock

# Testing
.phpunit.cache/
coverage/
.phpunit.result.cache

# IDE
.idea/
.vscode/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Environment
.env
.env.local

# Logs
*.log
logs/

# Build artifacts
build/
dist/

# Docker volumes
mysql_data/
postgres_data/

# Temporary files
tmp/
temp/
```

**Step 4: Create initial directory structure**

```bash
cd /home/chef/Github/Accounting-System
mkdir -p src/{Domain,Application,Infrastructure}
mkdir -p tests/{Unit,Integration}
mkdir -p public
```

**Step 5: Create index.php placeholder**

Write file: `/home/chef/Github/Accounting-System/public/index.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Placeholder - proper routing will be implemented later

header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Accounting System API',
    'version' => '2.0.0-alpha',
    'architecture' => [
        'Domain-Driven Design',
        'Event-Driven Architecture',
        'Hexagonal Architecture',
        'Test-Driven Development'
    ],
    'documentation' => '/docs'
], JSON_PRETTY_PRINT);
```

**Step 6: Verify project structure**

Run: `tree /home/chef/Github/Accounting-System -L 2 -I 'vendor|node_modules|legacy-archive'`
Expected: Clean structure with docs/, src/, tests/, public/

**Step 7: Commit initial project files**

```bash
git add composer.json phpunit.xml .gitignore src/ tests/ public/
git commit -m "chore: initialize project structure

- Add composer.json with PHP 8.2+ and testing dependencies
- Configure PHPUnit for unit and integration tests
- Set up PSR-4 autoloading (Domain, Application, Infrastructure)
- Create initial directory structure
- Add placeholder API endpoint
- Update .gitignore for modern PHP project"
```

---

## Task 7: Final Verification & Documentation

**Step 1: Verify git status is clean**

Run: `git status`
Expected: Working tree clean, all changes committed

**Step 2: Create CHANGELOG**

Write file: `/home/chef/Github/Accounting-System/CHANGELOG.md`

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Foundation (2025-12-12)

#### Added
- Complete domain knowledge extraction in README.md
- Comprehensive architecture documentation
  - System overview
  - Bounded contexts (8 subsystems)
  - Hexagonal architecture explanation
  - Event catalog
  - Architecture Decision Records (ADRs)
- Subsystem documentation templates
  - Identity & Access Management
  - Transaction Processing
- Algorithm documentation
  - Double-entry bookkeeping with examples
  - Balance calculation algorithms
- GitHub Actions CI/CD pipelines
  - PHP linting (PSR-12)
  - Static analysis (PHPStan level 8)
  - Unit and integration tests
  - Docker image building
  - Documentation validation
  - Security scanning
- Project structure
  - Composer configuration
  - PHPUnit configuration
  - PSR-4 autoloading

#### Changed
- Nuked legacy codebase (PHP/HTML/CSS/JS)
- Removed legacy Docker configuration
- Archived old documentation files

#### Migration Notes
- Legacy code moved to `/legacy-archive` for reference
- Domain knowledge preserved in `/README.md`
- Fresh start with proper architecture

---

## [1.0.0-legacy] - 2024

Legacy student project (archived). See `/legacy-archive/README.md` for details.

### Known Issues (Legacy)
- Plain-text passwords (CRITICAL)
- No automated tests
- Spaghetti code architecture
- No framework
- Security vulnerabilities

**This version should NOT be used in production.**
```

**Step 3: Update README with setup instructions**

Edit file: `/home/chef/Github/Accounting-System/README.md` (append at end)

```markdown
---

## Development Setup (New Architecture)

### Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL 15
- Docker & Docker Compose (recommended)
- Git

### Installation

```bash
# Clone repository
git clone <repo-url>
cd Accounting-System

# Install PHP dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Run linting
composer lint
```

### Running Tests

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only (requires PostgreSQL)
composer test:integration

# With coverage report
composer test:coverage
```

### Code Quality

```bash
# Run PHPStan (level 8)
composer analyse

# Run PHP_CodeSniffer
composer lint

# Auto-fix code style
composer lint:fix

# Run Psalm security analysis
composer psalm
```

---

## Current Status

**Phase:** Foundation & Documentation

**Completed:**
- ✅ Domain knowledge extraction
- ✅ Architecture documentation
- ✅ Subsystem design (2/8 complete)
- ✅ Algorithm documentation
- ✅ CI/CD setup
- ✅ Project structure

**Next Steps:**
1. Complete remaining subsystem documentation
2. Implement domain models (TDD)
3. Build core business logic
4. Set up Docker environment
5. Create API layer
6. Build frontend

**Progress:** See `/docs/plans/` for detailed implementation plans.
```

Run update:
```bash
# This will be done via Edit tool, not command
```

**Step 4: Create quick start guide**

Write file: `/home/chef/Github/Accounting-System/docs/QUICK-START.md`

```markdown
# Quick Start Guide

## For Developers

### 1. Clone and Setup

```bash
git clone <repo-url>
cd Accounting-System
composer install
```

### 2. Read the Documentation

**Start here:**
1. `/README.md` - Domain knowledge and system overview
2. `/docs/01-architecture/overview.md` - Architecture principles
3. `/docs/01-architecture/bounded-contexts.md` - Subsystem boundaries

**Then explore:**
- `/docs/02-subsystems/` - Detailed subsystem designs
- `/docs/03-algorithms/` - Core accounting algorithms

### 3. Run Tests

```bash
# Make sure tests pass
composer test
```

### 4. Start Contributing

**For new features:**
1. Read relevant subsystem documentation
2. Write tests first (TDD)
3. Implement domain logic
4. Ensure CI passes
5. Update documentation

**For bug fixes:**
1. Write failing test
2. Fix the bug
3. Ensure test passes
4. Verify CI passes

---

## For Architects

### Understanding the System

**Bounded Contexts:**
- 8 subsystems with clear boundaries
- Event-driven communication
- Hexagonal architecture (ports & adapters)

**Key Patterns:**
- Domain-Driven Design (DDD)
- Event-Driven Architecture (EDA)
- Test-Driven Development (TDD)

**Review these files:**
- `/docs/01-architecture/technology-decisions.md` - ADRs
- `/docs/01-architecture/event-catalog.md` - All domain events
- `/docs/01-architecture/hexagonal-architecture.md` - Ports & Adapters

---

## For Business Stakeholders

### What This System Does

This is an **accrual-basis double-entry accounting system** that:
- Tracks financial transactions with complete audit trail
- Supports multiple companies (multi-tenant)
- Generates financial reports (Balance Sheet, Income Statement)
- Enforces accounting rules automatically
- Provides approval workflows for sensitive operations

### Key Features

**Accounting:**
- Double-entry bookkeeping (debits = credits)
- Chart of accounts (5 account types)
- Transaction workflow (create → validate → post → void if needed)
- Real-time balance tracking

**Multi-Tenancy:**
- Multiple companies in one system
- Data isolation between companies
- Company-specific configurations

**Compliance:**
- Complete audit trail (who did what, when)
- Immutable transaction history
- Event sourcing for critical data

**Reporting:**
- Balance Sheet (financial position)
- Income Statement (profitability)
- Real-time account balances

### Architecture Benefits

**Modularity:**
- Each subsystem can evolve independently
- Easy to add new features (plug-and-play)
- Can be split into microservices later

**Quality:**
- Test-driven development (TDD)
- Automated quality gates (CI/CD)
- Type safety (PHP 8.2+)

**Maintainability:**
- Clear documentation
- Domain-driven design
- Separation of concerns

---

## Project Status

**Current Phase:** Foundation & Documentation

See `/CHANGELOG.md` for detailed progress.
```

**Step 5: Commit final documentation**

```bash
git add CHANGELOG.md README.md docs/QUICK-START.md
git commit -m "docs: add changelog and quick start guide

- Create CHANGELOG.md following Keep a Changelog format
- Add development setup instructions to README
- Create QUICK-START.md for different stakeholder types
- Document current status and next steps"
```

**Step 6: Create final summary**

Run: `git log --oneline | head -10`
Expected: 6-7 commits showing the entire reset process

**Step 7: Push to remote (optional)**

```bash
# Push all commits
git push origin main

# Or create new branch for review
git checkout -b feature/architecture-foundation
git push origin feature/architecture-foundation
```

Expected: All changes pushed successfully

---

## Completion Checklist

After completing all tasks, verify:

- [ ] Legacy code deleted from `/src` and `/docker`
- [ ] Domain knowledge preserved in `/README.md`
- [ ] Legacy docs archived in `/legacy-archive`
- [ ] Complete documentation structure in `/docs`
- [ ] All 6 architecture documents created
- [ ] Subsystem documentation templates created (2 subsystems)
- [ ] Algorithm documentation complete (double-entry)
- [ ] GitHub Actions workflows configured (CI, Docker, Docs)
- [ ] `composer.json` created with dependencies
- [ ] `phpunit.xml` configured
- [ ] `.gitignore` updated
- [ ] Project structure created (`src/`, `tests/`, `public/`)
- [ ] All changes committed to git (6-7 commits)
- [ ] CHANGELOG.md created
- [ ] QUICK-START.md created
- [ ] CI passes (if pushed to GitHub)

---

## Next Implementation Plan

After this foundation is complete, create next plan for:

**Phase 2: Core Domain Implementation**
1. Implement Transaction Processing domain (TDD)
2. Implement Ledger & Posting domain (TDD)
3. Implement Chart of Accounts domain (TDD)
4. Set up event bus
5. Create Docker environment
6. Build initial API endpoints

**This plan file:** `/docs/plans/2025-12-12-codebase-reset-and-foundation.md`
