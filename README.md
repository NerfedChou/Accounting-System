# Accounting System

> **Master Architect Documentation** | **DevOps Ready** | **Production Grade**

## What This Is

A **modern accrual-basis double-entry accounting system** built with enterprise-grade software architecture:

| Architecture | Description |
|--------------|-------------|
| **Domain-Driven Design (DDD)** | 8 bounded contexts with rich domain models |
| **Event-Driven Architecture (EDA)** | Hybrid event sourcing with domain events |
| **Hexagonal Architecture** | Ports & adapters for technology independence |
| **Test-Driven Development (TDD)** | Tests before code, always |

**Perfect for:**
- Enterprise accounting implementations
- Learning professional software architecture
- Students studying software engineering
- Developers mastering DDD/EDA/CQRS patterns

---

## Current Status

**Phase:** Documentation & Infrastructure Complete ✅

| Component | Status |
|-----------|--------|
| Architecture Documentation | ✅ Complete |
| Subsystem Domain Models | ✅ Complete (8/8) |
| API Specification | ✅ Complete |
| Database Schema | ✅ Complete |
| CI/CD Pipelines | ✅ Complete |
| Docker Configuration | ✅ Complete |
| Testing Strategy | ✅ Complete |
| Implementation Plan | ✅ Complete |
| Domain Implementation | 🔄 Ready for TDD |

---

## Documentation Map

```
docs/
├── 01-architecture/          # System Design
│   ├── overview.md           # Architecture vision & principles
│   ├── bounded-contexts.md   # 8 domain boundaries & events
│   ├── hexagonal-architecture.md
│   ├── event-catalog.md      # Complete event reference
│   └── technology-decisions.md  # ADRs
│
├── 02-subsystems/            # Domain Models (Complete)
│   ├── identity/             # Authentication & authorization
│   ├── company-management/   # Multi-tenant companies
│   ├── chart-of-accounts/    # Account structure
│   ├── transaction-processing/  # Double-entry transactions
│   ├── ledger-posting/       # Balance management
│   ├── financial-reporting/  # Reports generation
│   ├── audit-trail/          # Immutable activity logs
│   └── approval-workflow/    # Admin approvals
│
├── 03-algorithms/            # Core Logic
│   ├── double-entry-bookkeeping.md
│   └── database-schema.md    # Complete MySQL schema
│
├── 04-api/                   # REST API
│   ├── api-specification.md  # All endpoints & contracts
│   └── error-codes.md        # Error handling reference
│
├── 05-deployment/            # DevOps
│   ├── docker-setup.md       # Container configuration
│   └── github-actions.md     # CI/CD pipelines
│
├── 06-testing/               # Quality Assurance
│   └── testing-strategy.md   # TDD guidelines
│
└── CONTRIBUTING.md           # Development workflow
```

### Quick Links

| Topic | Document |
|-------|----------|
| **Getting Started** | [CONTRIBUTING.md](docs/CONTRIBUTING.md) |
| **Architecture** | [overview.md](docs/01-architecture/overview.md) |
| **8 Subsystems** | [bounded-contexts.md](docs/01-architecture/bounded-contexts.md) |
| **API Reference** | [api-specification.md](docs/04-api/api-specification.md) |
| **Database** | [database-schema.md](docs/03-algorithms/database-schema.md) |
| **Docker** | [docker-setup.md](docs/05-deployment/docker-setup.md) |
| **Testing** | [testing-strategy.md](docs/06-testing/testing-strategy.md) |
| **Changelog** | [CHANGELOG.md](CHANGELOG.md) |

## Getting Started

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0
- Docker & Docker Compose (recommended)
- Git

### Installation

```bash
# Clone repository
git clone <repo-url>
cd Accounting-System

# Install PHP dependencies
composer install

# Run tests (requires MySQL)
composer test

# Run static analysis
composer analyse

# Run linting
composer lint
```

### Development Commands

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests (requires MySQL)
composer test:integration

# Coverage report
composer test:coverage

# PHPStan (level 8)
composer analyse

# Code style check
composer lint

# Auto-fix code style
composer lint:fix

# Security analysis
composer psalm
```

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.2+ |
| Database | MySQL 8.0 |
| Frontend | HTML, CSS, JavaScript |
| Architecture | DDD + EDA + Hexagonal + TDD |
| Container | Docker |
| CI/CD | GitHub Actions |

## Author

Jan Rhian Angulo

---

**License:** Free to use and modify for educational purposes.
