<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use DateTimeImmutable;
use Domain\Audit\Entity\ActivityLog;
use Domain\Audit\ValueObject\ActivityId;
use Domain\Audit\ValueObject\ActivityType;
use Domain\Audit\ValueObject\Actor;
use Domain\Audit\ValueObject\RequestContext;
use Domain\Company\ValueObject\CompanyId;
use Infrastructure\Persistence\Mysql\Repository\MysqlActivityLogRepository;
use Tests\Integration\BaseIntegrationTestCase;
use Tests\Integration\DatabaseTestHelper;

class MysqlActivityLogRepositoryTest extends BaseIntegrationTestCase
{
    use DatabaseTestHelper;

    private MysqlActivityLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MysqlActivityLogRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());

        $log = new ActivityLog(
            ActivityId::generate(),
            $companyId,
            Actor::system(),
            ActivityType::ACCOUNT_CREATED,
            'Account',
            'acc-12345',
            'CREATE',
            [],
            ['name' => 'Cash', 'code' => '1001'],
            [],
            RequestContext::empty(),
            new DateTimeImmutable()
        );

        $this->repository->save($log);

        $retrieved = $this->repository->findById($log->id());

        $this->assertNotNull($retrieved);
        $this->assertEquals(ActivityType::ACCOUNT_CREATED, $retrieved->activityType());
        $this->assertEquals('Account', $retrieved->entityType());
    }

    public function testFindByCompany(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());

        $log1 = new ActivityLog(
            ActivityId::generate(),
            $companyId,
            Actor::system(),
            ActivityType::ACCOUNT_CREATED,
            'Account',
            'acc-001',
            'CREATE',
            [],
            ['name' => 'Accounts Receivable'],
            [],
            RequestContext::empty(),
            new DateTimeImmutable()
        );

        $log2 = new ActivityLog(
            ActivityId::generate(),
            $companyId,
            Actor::system(),
            ActivityType::TRANSACTION_POSTED,
            'Transaction',
            'txn-001',
            'POST',
            [],
            ['amount' => 5000],
            [],
            RequestContext::empty(),
            new DateTimeImmutable()
        );

        $this->repository->save($log1);
        $this->repository->save($log2);

        $logs = $this->repository->getRecent($companyId);

        $this->assertCount(2, $logs);
    }

    public function testFindByIdReturnsNullForNonExistent(): void
    {
        $result = $this->repository->findById(ActivityId::generate());
        $this->assertNull($result);
    }
}
