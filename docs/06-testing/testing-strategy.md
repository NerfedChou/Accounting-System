# Testing Strategy & Guidelines

> **Master Architect Reference**: Complete testing strategy for the Accounting System.

## Testing Philosophy

### Test-Driven Development (TDD)

This project follows TDD:
1. **Red**: Write a failing test
2. **Green**: Write minimal code to pass
3. **Refactor**: Improve code while keeping tests green

**Why TDD?**
- Forces clear requirements before coding
- Ensures high test coverage
- Creates living documentation
- Prevents regression

---

## Test Pyramid

```
        /\
       /  \        E2E Tests (5%)
      /────\       - Full user journeys
     /      \      - Browser automation
    /────────\     - Slow, brittle
   /          \
  /   API &    \   Integration Tests (20%)
 /   Service    \  - Database interactions
/────────────────\ - External services
                   - HTTP endpoints

     Unit Tests (75%)
     - Domain logic
     - Value objects
     - Fast, isolated
```

---

## Test Types

### 1. Unit Tests

**Location:** `tests/Unit/`

**Characteristics:**
- Fast (< 100ms per test)
- Isolated (no external dependencies)
- Test single units of code
- Use mocks/stubs for dependencies

**What to test:**
- Domain entities
- Value objects
- Domain services
- Validation logic
- Calculation algorithms

**Example Structure:**
```
tests/Unit/
├── Domain/
│   ├── Transaction/
│   │   ├── Entity/
│   │   │   ├── TransactionTest.php
│   │   │   └── TransactionLineTest.php
│   │   ├── ValueObject/
│   │   │   ├── TransactionIdTest.php
│   │   │   ├── MoneyTest.php
│   │   │   └── LineTypeTest.php
│   │   └── Service/
│   │       └── TransactionValidatorTest.php
│   ├── Account/
│   │   └── ...
│   └── Ledger/
│       └── ...
└── Application/
    └── Transaction/
        └── CreateTransactionHandlerTest.php
```

**Example Test:**
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Transaction\ValueObject;

use Domain\Transaction\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_creates_money_with_valid_amount(): void
    {
        $money = Money::fromFloat(100.50);

        $this->assertSame(100.50, $money->toFloat());
        $this->assertSame('100.50', $money->toString());
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        Money::fromFloat(-50.00);
    }

    public function test_adds_money_correctly(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(50.50);

        $result = $money1->add($money2);

        $this->assertSame(150.50, $result->toFloat());
    }

    public function test_subtracts_money_correctly(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(30.00);

        $result = $money1->subtract($money2);

        $this->assertSame(70.00, $result->toFloat());
    }

    public function test_compares_money_equality(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(100.00);
        $money3 = Money::fromFloat(50.00);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }
}
```

### 2. Integration Tests

**Location:** `tests/Integration/`

**Characteristics:**
- Test multiple components together
- Use real database (test database)
- Test repository implementations
- Slower than unit tests

**What to test:**
- Repository implementations
- Database queries
- Event handling
- Cache interactions

**Example Structure:**
```
tests/Integration/
├── Infrastructure/
│   ├── Persistence/
│   │   ├── MySQL/
│   │   │   ├── MySQLTransactionRepositoryTest.php
│   │   │   ├── MySQLAccountRepositoryTest.php
│   │   │   └── MySQLLedgerRepositoryTest.php
│   │   └── InMemory/
│   │       └── ...
│   └── Event/
│       └── EventBusTest.php
└── Application/
    └── UseCase/
        └── CreateTransactionUseCaseTest.php
```

**Example Test:**
```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\MySQL;

use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\ValueObject\TransactionId;
use Infrastructure\Persistence\MySQL\MySQLTransactionRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class MySQLTransactionRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MySQLTransactionRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD']
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->repository = new MySQLTransactionRepository($this->pdo);

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $this->pdo->exec('DELETE FROM transaction_lines');
        $this->pdo->exec('DELETE FROM transactions');
    }

    public function test_saves_and_retrieves_transaction(): void
    {
        $transaction = $this->createTestTransaction();

        $this->repository->save($transaction);

        $retrieved = $this->repository->findById($transaction->getId());

        $this->assertNotNull($retrieved);
        $this->assertTrue($transaction->getId()->equals($retrieved->getId()));
        $this->assertSame($transaction->getDescription(), $retrieved->getDescription());
        $this->assertCount(2, $retrieved->getLines());
    }

    public function test_returns_null_for_nonexistent_transaction(): void
    {
        $id = TransactionId::generate();

        $result = $this->repository->findById($id);

        $this->assertNull($result);
    }

    public function test_finds_transactions_by_company(): void
    {
        $companyId = CompanyId::generate();

        $transaction1 = $this->createTestTransaction($companyId);
        $transaction2 = $this->createTestTransaction($companyId);
        $transaction3 = $this->createTestTransaction(CompanyId::generate()); // Different company

        $this->repository->save($transaction1);
        $this->repository->save($transaction2);
        $this->repository->save($transaction3);

        $results = $this->repository->findByCompany($companyId);

        $this->assertCount(2, $results);
    }

    private function createTestTransaction(?CompanyId $companyId = null): Transaction
    {
        // Helper to create test transaction
        return Transaction::create(
            TransactionId::generate(),
            $companyId ?? CompanyId::generate(),
            new \DateTime(),
            'Test transaction',
            [
                TransactionLine::debit(AccountId::generate(), Money::fromFloat(100.00)),
                TransactionLine::credit(AccountId::generate(), Money::fromFloat(100.00)),
            ]
        );
    }
}
```

### 3. API Tests

**Location:** `tests/Api/`

**Characteristics:**
- Test HTTP endpoints
- Verify request/response contracts
- Test authentication/authorization
- Use test client

**Example:**
```php
<?php

declare(strict_types=1);

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

class TransactionApiTest extends TestCase
{
    private HttpClient $client;
    private string $authToken;

    protected function setUp(): void
    {
        $this->client = new HttpClient('http://localhost:8080');
        $this->authToken = $this->login('testuser', 'password');
    }

    public function test_creates_transaction_successfully(): void
    {
        $response = $this->client->post('/v1/transactions', [
            'headers' => ['Authorization' => "Bearer {$this->authToken}"],
            'json' => [
                'transactionDate' => '2025-12-13',
                'description' => 'Office supplies',
                'lines' => [
                    ['accountId' => 'uuid1', 'lineType' => 'debit', 'amount' => 100.00],
                    ['accountId' => 'uuid2', 'lineType' => 'credit', 'amount' => 100.00],
                ]
            ]
        ]);

        $this->assertSame(201, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertSame('pending', $body['data']['status']);
    }

    public function test_rejects_unbalanced_transaction(): void
    {
        $response = $this->client->post('/v1/transactions', [
            'headers' => ['Authorization' => "Bearer {$this->authToken}"],
            'json' => [
                'transactionDate' => '2025-12-13',
                'description' => 'Unbalanced',
                'lines' => [
                    ['accountId' => 'uuid1', 'lineType' => 'debit', 'amount' => 100.00],
                    ['accountId' => 'uuid2', 'lineType' => 'credit', 'amount' => 50.00],
                ]
            ]
        ]);

        $this->assertSame(422, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('TRANSACTION_UNBALANCED', $body['error']['code']);
    }
}
```

---

## Test Configuration (phpunit.xml)

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
        <testsuite name="api">
            <directory>tests/Api</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/Infrastructure/Http/Controller</directory>
        </exclude>
    </source>

    <coverage>
        <report>
            <html outputDirectory="coverage"/>
            <clover outputFile="coverage.xml"/>
            <text outputFile="php://stdout" showOnlySummary="true"/>
        </report>
    </coverage>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_HOST" value="127.0.0.1"/>
        <env name="DB_PORT" value="3306"/>
        <env name="DB_DATABASE" value="accounting_test"/>
        <env name="DB_USERNAME" value="test_user"/>
        <env name="DB_PASSWORD" value="test_password"/>
        <env name="REDIS_HOST" value="127.0.0.1"/>
        <env name="REDIS_PORT" value="6379"/>
    </php>
</phpunit>
```

---

## Test Data Builders

Use builders for creating test data:

```php
<?php

declare(strict_types=1);

namespace Tests\Support\Builder;

use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\Entity\TransactionLine;
use Domain\Transaction\ValueObject\TransactionId;
use Domain\Transaction\ValueObject\TransactionStatus;

class TransactionBuilder
{
    private TransactionId $id;
    private CompanyId $companyId;
    private \DateTime $date;
    private string $description = 'Test transaction';
    private array $lines = [];
    private TransactionStatus $status;

    public function __construct()
    {
        $this->id = TransactionId::generate();
        $this->companyId = CompanyId::generate();
        $this->date = new \DateTime();
        $this->status = TransactionStatus::PENDING;
    }

    public static function aTransaction(): self
    {
        return new self();
    }

    public function withId(TransactionId $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function forCompany(CompanyId $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function withLines(array $lines): self
    {
        $this->lines = $lines;
        return $this;
    }

    public function withStatus(TransactionStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function posted(): self
    {
        $this->status = TransactionStatus::POSTED;
        return $this;
    }

    public function balanced(float $amount = 100.00): self
    {
        $this->lines = [
            TransactionLine::debit(AccountId::generate(), Money::fromFloat($amount)),
            TransactionLine::credit(AccountId::generate(), Money::fromFloat($amount)),
        ];
        return $this;
    }

    public function build(): Transaction
    {
        return Transaction::create(
            $this->id,
            $this->companyId,
            $this->date,
            $this->description,
            $this->lines
        );
    }
}

// Usage in tests:
$transaction = TransactionBuilder::aTransaction()
    ->forCompany($companyId)
    ->withDescription('Rent payment')
    ->balanced(1000.00)
    ->build();
```

---

## Mocking Guidelines

### When to Mock
- External services (HTTP, email)
- Time-dependent code
- Random number generation
- File system operations

### When NOT to Mock
- Value objects
- Simple data structures
- The class under test

### Example with Mockery:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Transaction;

use Application\Transaction\CreateTransaction\CreateTransactionCommand;
use Application\Transaction\CreateTransaction\CreateTransactionHandler;
use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Domain\Transaction\Service\TransactionValidator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CreateTransactionHandlerTest extends MockeryTestCase
{
    private TransactionRepositoryInterface $repository;
    private TransactionValidator $validator;
    private CreateTransactionHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(TransactionRepositoryInterface::class);
        $this->validator = Mockery::mock(TransactionValidator::class);

        $this->handler = new CreateTransactionHandler(
            $this->repository,
            $this->validator
        );
    }

    public function test_creates_valid_transaction(): void
    {
        $command = new CreateTransactionCommand(
            companyId: 'uuid',
            date: '2025-12-13',
            description: 'Test',
            lines: [
                ['accountId' => 'uuid1', 'lineType' => 'debit', 'amount' => 100.00],
                ['accountId' => 'uuid2', 'lineType' => 'credit', 'amount' => 100.00],
            ]
        );

        $this->validator
            ->shouldReceive('validate')
            ->once()
            ->andReturn(ValidationResult::valid());

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Transaction::class));

        $result = $this->handler->handle($command);

        $this->assertInstanceOf(Transaction::class, $result);
    }
}
```

---

## Coverage Requirements

### Minimum Coverage

| Category | Minimum |
|----------|---------|
| Domain Layer | 90% |
| Application Layer | 85% |
| Infrastructure Layer | 70% |
| Overall | 80% |

### Generating Coverage Reports

```bash
# Text summary
composer test:coverage

# HTML report
vendor/bin/phpunit --coverage-html coverage/

# Open in browser
open coverage/index.html
```

---

## Test Naming Conventions

```php
// Method: test_[what]_[when]_[expected]

// Good examples:
test_creates_transaction_with_valid_data()
test_rejects_negative_amount()
test_calculates_balance_change_for_debit_on_asset_account()
test_throws_exception_when_debits_not_equal_credits()

// Avoid:
testTransaction()  // Too vague
test1()            // No description
testItWorks()      // What works?
```

---

## Running Tests

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# With coverage
composer test:coverage

# Specific test file
vendor/bin/phpunit tests/Unit/Domain/Transaction/Entity/TransactionTest.php

# Specific test method
vendor/bin/phpunit --filter test_creates_valid_transaction

# With verbose output
vendor/bin/phpunit -v

# Stop on first failure
vendor/bin/phpunit --stop-on-failure
```

---

## Testing Checklist

### Before Commit
- [ ] All tests pass
- [ ] New code has tests
- [ ] Coverage meets minimum
- [ ] No skipped tests without reason

### Code Review
- [ ] Tests are readable
- [ ] Tests are meaningful (not just coverage)
- [ ] Edge cases covered
- [ ] Error scenarios tested

### CI Pipeline
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Coverage threshold met
- [ ] No warnings/deprecations
