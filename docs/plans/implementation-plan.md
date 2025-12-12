# Implementation Plan: Core Domain Development

> **Date:** 2025-12-13
> **Phase:** Core Domain Implementation (TDD)
> **Status:** Ready to Begin

## Overview

With documentation complete, this plan outlines the TDD-based implementation of core domain logic for the Accounting System.

---

## Implementation Phases

### Phase 1: Domain Foundation (Week 1-2)

**Goal:** Implement core value objects and entity foundations

#### Task 1.1: Shared Kernel Value Objects
**Priority:** Critical
**Estimated Time:** 2 days

**Files to Create:**
```
src/Domain/Shared/ValueObject/
├── Uuid.php
├── Money.php
├── Currency.php
├── Email.php
└── DateTime/
    ├── DateTimeValue.php
    └── DateValue.php
```

**TDD Approach:**
1. Write tests first in `tests/Unit/Domain/Shared/ValueObject/`
2. Implement to pass tests
3. Refactor for clean code

**Test Cases for Money:**
- Create from float
- Create zero value
- Add two Money objects
- Subtract Money objects
- Reject negative values (throw exception)
- Format as string
- Equality comparison

---

#### Task 1.2: Identity Domain
**Priority:** Critical
**Estimated Time:** 3 days

**Files to Create:**
```
src/Domain/Identity/
├── Entity/
│   ├── User.php
│   └── Session.php
├── ValueObject/
│   ├── UserId.php
│   ├── Role.php
│   ├── RegistrationStatus.php
│   └── SessionId.php
├── Repository/
│   ├── UserRepositoryInterface.php
│   └── SessionRepositoryInterface.php
├── Service/
│   ├── AuthenticationService.php
│   ├── AuthorizationService.php
│   └── PasswordService.php
└── Event/
    ├── UserRegistered.php
    ├── UserAuthenticated.php
    └── UserDeactivated.php
```

**Test First:**
```php
// tests/Unit/Domain/Identity/Entity/UserTest.php
public function test_creates_user_with_valid_data(): void
public function test_rejects_invalid_email(): void
public function test_hashes_password_on_creation(): void
public function test_authenticates_with_correct_password(): void
public function test_rejects_wrong_password(): void
public function test_pending_user_cannot_authenticate(): void
public function test_deactivated_user_cannot_authenticate(): void
public function test_approves_pending_user(): void
public function test_cannot_self_approve(): void
```

---

#### Task 1.3: Company Domain
**Priority:** Critical
**Estimated Time:** 2 days

**Files to Create:**
```
src/Domain/Company/
├── Entity/
│   ├── Company.php
│   └── CompanySettings.php
├── ValueObject/
│   ├── CompanyId.php
│   ├── TaxIdentifier.php
│   ├── Address.php
│   ├── FiscalYear.php
│   └── CompanyStatus.php
├── Repository/
│   └── CompanyRepositoryInterface.php
└── Event/
    ├── CompanyCreated.php
    ├── CompanyActivated.php
    └── CompanyDeactivated.php
```

---

### Phase 2: Chart of Accounts (Week 2-3)

**Goal:** Implement account structure and validation

#### Task 2.1: Account Entity
**Priority:** High
**Estimated Time:** 3 days

**Files to Create:**
```
src/Domain/ChartOfAccounts/
├── Entity/
│   └── Account.php
├── ValueObject/
│   ├── AccountId.php
│   ├── AccountCode.php
│   ├── AccountType.php
│   └── NormalBalance.php
├── Repository/
│   └── AccountRepositoryInterface.php
├── Service/
│   ├── AccountCodeGenerator.php
│   └── DefaultChartInitializer.php
└── Event/
    ├── AccountCreated.php
    └── AccountDeactivated.php
```

**Key Tests:**
```php
public function test_derives_account_type_from_code(): void
public function test_derives_normal_balance_from_type(): void
public function test_asset_code_starts_with_1(): void
public function test_rejects_invalid_account_code(): void
public function test_cannot_deactivate_with_balance(): void
```

---

### Phase 3: Transaction Processing (Week 3-4)

**Goal:** Implement core transaction logic with double-entry validation

#### Task 3.1: Transaction Entity
**Priority:** Critical
**Estimated Time:** 4 days

**Files to Create:**
```
src/Domain/Transaction/
├── Entity/
│   ├── Transaction.php
│   └── TransactionLine.php
├── ValueObject/
│   ├── TransactionId.php
│   ├── LineId.php
│   ├── LineType.php
│   ├── TransactionStatus.php
│   └── TransactionDate.php
├── Repository/
│   ├── TransactionRepositoryInterface.php
│   └── TransactionLineRepositoryInterface.php
├── Service/
│   ├── TransactionValidator.php
│   ├── BalanceCalculator.php
│   └── TransactionNumberGenerator.php
└── Event/
    ├── TransactionCreated.php
    ├── TransactionPosted.php
    └── TransactionVoided.php
```

**Critical Tests:**
```php
// Double-entry validation
public function test_balanced_transaction_is_valid(): void
public function test_unbalanced_transaction_is_invalid(): void
public function test_requires_minimum_two_lines(): void
public function test_requires_at_least_one_debit(): void
public function test_requires_at_least_one_credit(): void
public function test_rejects_negative_amounts(): void

// Balance calculation
public function test_debit_to_asset_increases_balance(): void
public function test_credit_to_asset_decreases_balance(): void
public function test_credit_to_liability_increases_balance(): void
public function test_debit_to_liability_decreases_balance(): void

// Status transitions
public function test_pending_can_be_posted(): void
public function test_posted_cannot_be_edited(): void
public function test_posted_can_be_voided(): void
public function test_voided_is_terminal(): void
```

---

### Phase 4: Ledger & Posting (Week 4-5)

**Goal:** Implement balance tracking and posting

#### Task 4.1: Ledger Entity
**Priority:** Critical
**Estimated Time:** 3 days

**Files to Create:**
```
src/Domain/Ledger/
├── Entity/
│   ├── Ledger.php
│   ├── AccountBalance.php
│   └── BalanceChange.php
├── ValueObject/
│   ├── LedgerId.php
│   ├── BalanceChangeId.php
│   └── BalanceSummary.php
├── Repository/
│   ├── LedgerRepositoryInterface.php
│   └── BalanceChangeRepositoryInterface.php
├── Service/
│   ├── LedgerPostingService.php
│   ├── AccountingEquationValidator.php
│   └── BalanceCalculationService.php
└── Event/
    ├── LedgerUpdated.php
    ├── AccountBalanceChanged.php
    └── NegativeBalanceDetected.php
```

**Critical Tests:**
```php
// Posting
public function test_posts_transaction_to_ledger(): void
public function test_updates_all_account_balances(): void
public function test_rejects_negative_asset_balance(): void
public function test_flags_negative_equity_for_approval(): void

// Accounting equation
public function test_equation_balanced_after_posting(): void
public function test_detects_equation_violation(): void

// Reversal
public function test_reverses_voided_transaction(): void
public function test_restores_original_balances_on_void(): void
```

---

### Phase 5: Application Layer (Week 5-6)

**Goal:** Implement use cases with command handlers

#### Task 5.1: Command Handlers
**Priority:** High
**Estimated Time:** 4 days

**Files to Create:**
```
src/Application/
├── Command/
│   ├── Identity/
│   │   ├── RegisterUserCommand.php
│   │   └── AuthenticateUserCommand.php
│   ├── Transaction/
│   │   ├── CreateTransactionCommand.php
│   │   ├── PostTransactionCommand.php
│   │   └── VoidTransactionCommand.php
│   └── Account/
│       └── CreateAccountCommand.php
├── Handler/
│   ├── Identity/
│   │   ├── RegisterUserHandler.php
│   │   └── AuthenticateUserHandler.php
│   ├── Transaction/
│   │   ├── CreateTransactionHandler.php
│   │   ├── PostTransactionHandler.php
│   │   └── VoidTransactionHandler.php
│   └── Account/
│       └── CreateAccountHandler.php
└── DTO/
    ├── UserDTO.php
    ├── TransactionDTO.php
    └── AccountDTO.php
```

---

### Phase 6: Infrastructure Layer (Week 6-7)

**Goal:** Implement database adapters

#### Task 6.1: MySQL Repositories
**Priority:** High
**Estimated Time:** 4 days

**Files to Create:**
```
src/Infrastructure/
├── Persistence/
│   ├── MySQL/
│   │   ├── MySQLUserRepository.php
│   │   ├── MySQLCompanyRepository.php
│   │   ├── MySQLAccountRepository.php
│   │   ├── MySQLTransactionRepository.php
│   │   └── MySQLLedgerRepository.php
│   └── InMemory/
│       ├── InMemoryUserRepository.php
│       ├── InMemoryAccountRepository.php
│       └── InMemoryTransactionRepository.php
├── Event/
│   └── SimpleEventBus.php
└── Migration/
    └── Migrator.php
```

**Integration Tests:**
```php
// tests/Integration/Persistence/
public function test_saves_and_retrieves_user(): void
public function test_finds_user_by_username(): void
public function test_saves_transaction_with_lines(): void
public function test_updates_account_balance(): void
```

---

### Phase 7: HTTP API Layer (Week 7-8)

**Goal:** Implement REST API endpoints

#### Task 7.1: Controllers
**Priority:** High
**Estimated Time:** 4 days

**Files to Create:**
```
src/Infrastructure/Http/
├── Controller/
│   ├── AuthController.php
│   ├── UserController.php
│   ├── CompanyController.php
│   ├── AccountController.php
│   ├── TransactionController.php
│   └── ReportController.php
├── Middleware/
│   ├── AuthenticationMiddleware.php
│   ├── AuthorizationMiddleware.php
│   ├── CorsMiddleware.php
│   └── RateLimitMiddleware.php
├── Request/
│   ├── CreateTransactionRequest.php
│   └── LoginRequest.php
└── Response/
    ├── JsonResponse.php
    └── ErrorResponse.php
```

---

## Development Workflow

### For Each Task:

1. **Write Failing Tests**
   ```bash
   composer test:unit -- --filter=test_name
   ```

2. **Implement Code**
   - Write minimal code to pass tests
   - No extra features

3. **Refactor**
   - Clean up code
   - Extract methods/classes
   - Improve naming

4. **Run Full Suite**
   ```bash
   composer test && composer analyse && composer lint
   ```

5. **Commit**
   ```bash
   git add .
   git commit -m "feat(domain): implement TransactionValidator"
   ```

---

## Quality Gates

Every PR must pass:

- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] PHPStan level 8 passes
- [ ] PHP_CodeSniffer (PSR-12) passes
- [ ] Code coverage >= 80%
- [ ] Documentation updated

---

## Dependencies Between Tasks

```
Phase 1: Foundation
    │
    ├── Task 1.1: Shared Value Objects ─────┐
    │                                        │
    ├── Task 1.2: Identity Domain ◄──────────┤
    │                                        │
    └── Task 1.3: Company Domain ◄───────────┘
           │
           ▼
Phase 2: Chart of Accounts
    │
    └── Task 2.1: Account Entity ◄─── Company Domain
           │
           ▼
Phase 3: Transaction Processing
    │
    └── Task 3.1: Transaction Entity ◄─── Account Entity
           │
           ▼
Phase 4: Ledger & Posting
    │
    └── Task 4.1: Ledger Entity ◄─── Transaction Entity
           │
           ▼
Phase 5: Application Layer ◄─── All Domain Tasks
           │
           ▼
Phase 6: Infrastructure Layer ◄─── Application Layer
           │
           ▼
Phase 7: HTTP API Layer ◄─── Infrastructure Layer
```

---

## Risk Mitigation

### Risk 1: Double-Entry Logic Complexity
**Mitigation:** 
- Extensive unit tests for balance calculations
- Multiple transaction examples in tests
- Integration tests with real database

### Risk 2: Event Sourcing Complexity
**Mitigation:**
- Start with simple event bus
- Add event sourcing incrementally for critical domains
- Keep event store simple initially

### Risk 3: Performance with Large Datasets
**Mitigation:**
- Use database indexes from start
- Implement pagination in repositories
- Add caching layer later

---

## Success Criteria

### Phase 1 Complete When:
- [ ] All value objects have 100% test coverage
- [ ] User registration and authentication works
- [ ] Company creation works

### Phase 3 Complete When:
- [ ] Can create balanced transactions
- [ ] Double-entry validation rejects unbalanced
- [ ] Balance calculations correct for all account types

### Phase 4 Complete When:
- [ ] Posting updates all account balances
- [ ] Accounting equation maintained
- [ ] Void reverses all balance changes

### Project MVP When:
- [ ] Full transaction lifecycle (create → post → void)
- [ ] Financial reports generate correctly
- [ ] API endpoints functional
- [ ] All tests passing

---

## Next Steps

1. **Immediately:** Start Task 1.1 (Shared Value Objects)
2. **After Foundation:** Parallel work on Identity and Company domains
3. **Weekly:** Review progress, adjust timeline if needed

---

## Commands Reference

```bash
# Start development
composer install

# Run tests during development
composer test:unit -- --filter=TestClassName

# Run all quality checks
composer test && composer analyse && composer lint

# Generate coverage report
composer test:coverage

# Fix code style
composer lint:fix
```

---

## File Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Entity | `{Name}.php` | `Transaction.php` |
| Value Object | `{Name}.php` | `TransactionId.php` |
| Interface | `{Name}Interface.php` | `TransactionRepositoryInterface.php` |
| Event | `{PastTenseAction}.php` | `TransactionPosted.php` |
| Command | `{Action}{Entity}Command.php` | `CreateTransactionCommand.php` |
| Handler | `{Action}{Entity}Handler.php` | `CreateTransactionHandler.php` |
| Test | `{ClassUnderTest}Test.php` | `TransactionTest.php` |

---

**Ready to begin implementation!**
