# Accounting System

> **Status:** Complete rebuild in progress. Legacy code removed. Starting fresh with proper architecture.

## What This Is

An **accrual-basis double-entry accounting system** built with modern software architecture patterns:
- Domain-Driven Design (DDD)
- Event-Driven Architecture (EDA)
- Hexagonal Architecture (Ports & Adapters)
- Test-Driven Development (TDD)

**Perfect for:**
- Learning professional software architecture
- Understanding accounting systems
- Students studying software engineering
- Developers learning DDD/EDA patterns

## Current Status

**Phase:** Foundation Complete ✅

- ✅ Legacy codebase deleted (47,656 lines removed)
- ✅ Architecture documentation created
- ✅ GitHub Actions CI/CD configured
- ✅ Modern PHP project structure
- 🔄 Ready for domain implementation (TDD)

## Documentation

See `/docs` directory for complete architecture and design documentation.

**Start here:**
- `/docs/01-architecture/overview.md` - System architecture
- `/docs/01-architecture/bounded-contexts.md` - 8 subsystem domains
- `/docs/01-architecture/hexagonal-architecture.md` - Ports & Adapters
- `/CHANGELOG.md` - Project history

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
