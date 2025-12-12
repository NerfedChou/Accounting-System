# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Foundation (2025-12-12)

#### Added
- Complete architecture documentation
  - System overview (DDD/EDA/Hexagonal)
  - Bounded contexts (8 subsystems)
  - Event catalog
  - Architecture Decision Records (ADRs)
  - Technology decisions (MySQL 8.0 for students)
- GitHub Actions CI/CD pipelines
  - PHP linting (PSR-12)
  - Static analysis (PHPStan level 8)
  - Unit and integration tests with MySQL 8.0
  - Docker image building
  - Documentation validation
  - Security scanning (Composer audit, Psalm)
  - Dependabot for automated updates
- Modern PHP project structure
  - composer.json with PHP 8.2+ and testing dependencies
  - PHPUnit configuration
  - PSR-4 autoloading (Domain, Application, Infrastructure)
  - Hexagonal architecture directories

#### Changed
- Nuked entire legacy codebase
- Removed all legacy documentation
- Switched from PostgreSQL to MySQL 8.0 (student-friendly)
- Rewritten README for fresh start

#### Removed
- Legacy PHP/HTML/CSS/JS code (150 files, 47,656 lines)
- Legacy Docker configuration
- All spaghetti code and security vulnerabilities

#### Migration Notes
- Fresh start with proper architecture
- Domain knowledge preserved in documentation
- Ready for TDD development

---

## [1.0.0-legacy] - 2024

Legacy student project (deleted). Known issues:
- Plain-text passwords (CRITICAL)
- No automated tests
- Spaghetti architecture
- Security vulnerabilities

**This version should NOT be used.**

---

## Next Steps

**Phase 2:** Core Domain Implementation
1. Implement Transaction Processing domain (TDD)
2. Implement Ledger & Posting domain (TDD)
3. Set up Docker environment (MySQL 8.0)
4. Build API layer
5. Create frontend

See `/docs/plans/` for detailed implementation plans.
