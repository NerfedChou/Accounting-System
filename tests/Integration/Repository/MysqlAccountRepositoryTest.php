<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use Domain\ChartOfAccounts\Entity\Account;
use Domain\ChartOfAccounts\ValueObject\AccountCode;
use Domain\Company\ValueObject\CompanyId;
use Infrastructure\Persistence\Mysql\Repository\MysqlAccountRepository;
use Tests\Integration\BaseIntegrationTestCase;
use Tests\Integration\DatabaseTestHelper;

class MysqlAccountRepositoryTest extends BaseIntegrationTestCase
{
    use DatabaseTestHelper;

    private MysqlAccountRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MysqlAccountRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());

        $account = Account::create(
            AccountCode::fromInt(1001),
            'Cash',
            $companyId
        );

        $this->repository->save($account);

        $retrieved = $this->repository->findById($account->id());

        $this->assertNotNull($retrieved);
        $this->assertEquals('Cash', $retrieved->name());
        $this->assertEquals(1001, $retrieved->code()->toInt());
    }

    public function testFindByCompany(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());

        $account1 = Account::create(
            AccountCode::fromInt(1001),
            'Cash',
            $companyId
        );

        $account2 = Account::create(
            AccountCode::fromInt(2001),
            'Accounts Payable',
            $companyId
        );

        $this->repository->save($account1);
        $this->repository->save($account2);

        $accounts = $this->repository->findByCompany($companyId);

        $this->assertCount(2, $accounts);
    }

    public function testFindByCode(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());

        $account = Account::create(
            AccountCode::fromInt(1500),
            'Inventory',
            $companyId
        );

        $this->repository->save($account);

        $retrieved = $this->repository->findByCode(AccountCode::fromInt(1500), $companyId);

        $this->assertNotNull($retrieved);
        $this->assertEquals('Inventory', $retrieved->name());
    }
}
