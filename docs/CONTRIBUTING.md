# Contributing Guide

> **Master Architect Reference**: Development workflow and contribution guidelines.

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0
- Docker & Docker Compose (recommended)
- Git

### Setup

```bash
# Clone repository
git clone https://github.com/your-org/Accounting-System.git
cd Accounting-System

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Start Docker services
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Run tests to verify setup
composer test
```

---

## Development Workflow

### 1. Branch Strategy

```
main ────────────────────────────────────────────────────▶
  │
  └─── develop ──────────────────────────────────────────▶
        │
        ├─── feature/COA-123-add-account-api ────────▶ PR
        │
        ├─── bugfix/COA-456-fix-balance-calc ────────▶ PR
        │
        └─── hotfix/COA-789-security-patch ──────────▶ PR (to main)
```

**Branch Naming:**
- `feature/TICKET-description` - New features
- `bugfix/TICKET-description` - Bug fixes
- `hotfix/TICKET-description` - Critical production fixes
- `refactor/description` - Code refactoring
- `docs/description` - Documentation only

### 2. Starting Work

```bash
# Sync with develop
git checkout develop
git pull origin develop

# Create feature branch
git checkout -b feature/COA-123-add-account-api

# Work on your changes...
```

### 3. Making Changes

#### Follow TDD (Test-Driven Development)

```bash
# 1. Write failing test
vendor/bin/phpunit --filter test_creates_account

# 2. Write minimal code to pass
# Edit src/...

# 3. Run test again (should pass)
vendor/bin/phpunit --filter test_creates_account

# 4. Refactor if needed

# 5. Run all tests
composer test
```

#### Run Quality Checks

```bash
# Code style
composer lint

# Fix style issues
composer lint:fix

# Static analysis
composer analyse

# Security check
composer psalm
```

### 4. Committing Changes

#### Commit Message Format

```
type(scope): short description

Longer description if needed.

Fixes #123
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting, no code change
- `refactor`: Code change that doesn't fix or add feature
- `test`: Adding tests
- `chore`: Maintenance tasks

**Examples:**
```bash
git commit -m "feat(accounts): add account creation API endpoint"
git commit -m "fix(ledger): correct balance calculation for equity accounts"
git commit -m "docs(api): add missing error codes documentation"
git commit -m "test(transaction): add integration tests for posting"
```

### 5. Creating Pull Request

```bash
# Push your branch
git push origin feature/COA-123-add-account-api

# Create PR on GitHub
```

#### PR Template

```markdown
## Description
Brief description of changes.

## Type of Change
- [ ] New feature
- [ ] Bug fix
- [ ] Documentation update
- [ ] Refactoring
- [ ] Other (describe):

## Related Issues
Fixes #123

## Changes Made
- Added X
- Modified Y
- Fixed Z

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] All tests pass locally
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No new warnings/errors
```

### 6. Code Review

**Reviewers should check:**
- [ ] Code follows architecture patterns (DDD/Hexagonal)
- [ ] Tests are meaningful (not just for coverage)
- [ ] Business logic is correct
- [ ] No security issues
- [ ] Documentation is updated
- [ ] No over-engineering

**Author responsibilities:**
- Respond to feedback promptly
- Make requested changes
- Don't force push after review started

### 7. Merging

After approval:
1. Squash commits if needed
2. Rebase on develop
3. Merge via GitHub

---

## Code Standards

### PHP Style Guide (PSR-12)

```php
<?php

declare(strict_types=1);

namespace Domain\Transaction\Entity;

use Domain\Transaction\ValueObject\TransactionId;
use Domain\Transaction\ValueObject\Money;

final class Transaction
{
    private TransactionId $id;
    private Money $amount;

    public function __construct(TransactionId $id, Money $amount)
    {
        $this->id = $id;
        $this->amount = $amount;
    }

    public function getId(): TransactionId
    {
        return $this->id;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }
}
```

### Key Rules

1. **Strict Types**: Always declare `strict_types=1`
2. **Final Classes**: Prefer `final` unless designed for extension
3. **Type Hints**: All parameters and return types
4. **Immutability**: Value objects should be immutable
5. **No Mixed**: Avoid `mixed` type when possible

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Class | PascalCase | `TransactionValidator` |
| Interface | PascalCase + Interface | `TransactionRepositoryInterface` |
| Method | camelCase | `calculateBalance()` |
| Variable | camelCase | `$accountBalance` |
| Constant | UPPER_SNAKE | `MAX_LINES` |
| File | PascalCase.php | `Transaction.php` |

---

## Architecture Guidelines

### Directory Structure

```
src/
├── Domain/           # Pure business logic
│   ├── Entity/
│   ├── ValueObject/
│   ├── Service/
│   ├── Repository/   # Interfaces only
│   └── Event/
│
├── Application/      # Use cases
│   ├── Command/
│   ├── Handler/
│   └── DTO/
│
└── Infrastructure/   # Implementations
    ├── Persistence/
    ├── Http/
    └── Event/
```

### Domain Layer Rules

1. **No framework dependencies**
2. **No infrastructure imports**
3. **Business logic only**
4. **Rich domain models**

```php
// GOOD - Domain logic in entity
class Transaction
{
    public function post(): void
    {
        if (!$this->isValid()) {
            throw new InvalidTransactionException();
        }
        $this->status = TransactionStatus::POSTED;
        $this->recordEvent(new TransactionPosted($this->id));
    }
}

// BAD - Anemic model with logic in service
class Transaction
{
    private string $status;
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}

class TransactionService
{
    public function post(Transaction $t): void
    {
        // Logic should be in entity
        $t->setStatus('posted');
    }
}
```

### Value Objects

```php
// GOOD - Proper value object
final class Money
{
    private float $amount;

    private function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }
        $this->amount = $amount;
    }

    public static function fromFloat(float $amount): self
    {
        return new self($amount);
    }

    public function add(Money $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function equals(Money $other): bool
    {
        return abs($this->amount - $other->amount) < 0.01;
    }
}
```

---

## Testing Guidelines

### Test Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Transaction\Entity;

use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    // Use descriptive method names
    public function test_creates_transaction_with_valid_lines(): void
    {
        // Arrange
        $lines = [
            TransactionLine::debit($accountId1, Money::fromFloat(100)),
            TransactionLine::credit($accountId2, Money::fromFloat(100)),
        ];

        // Act
        $transaction = Transaction::create($id, $companyId, $date, 'Test', $lines);

        // Assert
        $this->assertSame('pending', $transaction->getStatus()->value());
        $this->assertCount(2, $transaction->getLines());
    }

    public function test_rejects_unbalanced_transaction(): void
    {
        $this->expectException(UnbalancedTransactionException::class);

        $lines = [
            TransactionLine::debit($accountId, Money::fromFloat(100)),
            // Missing credit line
        ];

        Transaction::create($id, $companyId, $date, 'Test', $lines);
    }
}
```

### What to Test

**Always test:**
- Business rules and validation
- Edge cases
- Error scenarios
- Value object equality

**Don't test:**
- Getters/setters
- Framework code
- External libraries

---

## Documentation

### Code Comments

```php
/**
 * Calculate balance change based on account's normal balance.
 *
 * If transaction line type matches normal balance, balance increases.
 * If they differ, balance decreases.
 *
 * @example
 *   Asset (debit normal) + Debit line = +amount
 *   Asset (debit normal) + Credit line = -amount
 */
public function calculateChange(
    NormalBalance $normal,
    LineType $lineType,
    Money $amount
): float {
    // ...
}
```

### When to Comment

- Complex algorithms
- Non-obvious business rules
- Workarounds (with ticket reference)
- Public API methods

### When NOT to Comment

- Obvious code
- Self-explanatory method names
- Code that should be refactored instead

---

## Security Guidelines

### Never Do

- Store passwords in plain text
- Include secrets in code
- Log sensitive data
- Trust user input
- Use `eval()` or similar

### Always Do

- Use prepared statements
- Validate and sanitize input
- Hash passwords (bcrypt)
- Use HTTPS
- Escape output

---

## Performance Guidelines

### Database

- Use indexes for frequent queries
- Avoid N+1 queries
- Use pagination for large datasets
- Cache expensive queries

### PHP

- Use opcode caching
- Avoid loading unnecessary data
- Use lazy loading where appropriate

---

## Getting Help

- **Documentation**: `/docs` directory
- **Issues**: GitHub Issues
- **Questions**: Create a discussion

---

## Release Process

1. Merge feature branches to `develop`
2. Create release branch `release/vX.Y.Z`
3. Final testing and bug fixes
4. Update CHANGELOG.md
5. Merge to `main`
6. Tag release
7. Merge back to `develop`

```bash
# Create release
git checkout develop
git checkout -b release/v1.2.0

# After testing, merge to main
git checkout main
git merge release/v1.2.0
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin main --tags

# Merge back to develop
git checkout develop
git merge main
git push origin develop
```
