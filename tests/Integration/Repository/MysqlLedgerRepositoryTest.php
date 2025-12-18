<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\ChartOfAccounts\ValueObject\AccountType;
use Domain\Company\ValueObject\CompanyId;
use Domain\Ledger\Entity\AccountBalance;
use Domain\Ledger\ValueObject\AccountBalanceId;
use Domain\Ledger\ValueObject\BalanceMetrics;
use Domain\Shared\ValueObject\Currency;
use Infrastructure\Persistence\Mysql\Repository\MysqlLedgerRepository;
use Tests\Integration\BaseIntegrationTestCase;
use Tests\Integration\DatabaseTestHelper;

class MysqlLedgerRepositoryTest extends BaseIntegrationTestCase
{
    use DatabaseTestHelper;

    private MysqlLedgerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MysqlLedgerRepository($this->pdo); // Using PDO from BaseIntegrationTestCase
    }

    public function testSaveAndFindBalance(): void
    {
        $companyIdStr = '00000000-0000-0000-0000-000000000001';
        $accountIdStr = '00000000-0000-0000-0000-000000000002';
        $balanceIdStr = '00000000-0000-0000-0000-000000000003';

        // Seed data
        $this->createCompany($this->pdo, $companyIdStr);
        $this->createAccount($this->pdo, $accountIdStr, $companyIdStr);

        // Create Entity
        $balance = AccountBalance::reconstruct(
            AccountBalanceId::fromString($balanceIdStr),
            AccountId::fromString($accountIdStr),
            CompanyId::fromString($companyIdStr),
            AccountType::ASSET,
            AccountType::ASSET->normalBalance(),
            Currency::USD,
            BalanceMetrics::reconstruct(
                currentBalanceCents: 1000,
                openingBalanceCents: 0,
                totalDebitsCents: 1000,
                totalCreditsCents: 0,
                transactionCount: 1,
                lastTransactionAt: null,
                version: 1
            )
        );

        // Save
        $this->repository->save($balance);

        // Retrieve
        $retrieved = $this->repository->findByAccount(AccountId::fromString($accountIdStr));

        // Assert
        $this->assertNotNull($retrieved);
        $this->assertEquals(1000, $retrieved->currentBalanceCents());
        $this->assertEquals($accountIdStr, $retrieved->accountId()->toString());
    }
}
