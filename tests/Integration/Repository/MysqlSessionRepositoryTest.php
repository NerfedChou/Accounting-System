<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\Entity\Session;
use Domain\Identity\ValueObject\SessionId;
use Domain\Identity\ValueObject\UserId;
use Infrastructure\Persistence\Mysql\Repository\MysqlSessionRepository;
use Tests\Integration\BaseIntegrationTestCase;
use Tests\Integration\DatabaseTestHelper;

class MysqlSessionRepositoryTest extends BaseIntegrationTestCase
{
    use DatabaseTestHelper;

    private MysqlSessionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MysqlSessionRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());
        
        $userId = UserId::generate();
        $this->createUser($this->pdo, $userId->toString(), $companyId->toString());

        $session = Session::create(
            $userId,
            '127.0.0.1',
            'PHPUnit Test Agent',
            bin2hex(random_bytes(32))
        );

        $this->repository->save($session);

        $retrieved = $this->repository->findById($session->id());

        $this->assertNotNull($retrieved);
        $this->assertEquals($userId->toString(), $retrieved->userId()->toString());
        $this->assertFalse($retrieved->isExpired());
    }

    public function testFindByToken(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());
        
        $userId = UserId::generate();
        $this->createUser($this->pdo, $userId->toString(), $companyId->toString());
        
        $token = bin2hex(random_bytes(32));

        $session = Session::create(
            $userId,
            '192.168.1.1',
            'Browser Agent',
            $token
        );

        $this->repository->save($session);

        $retrieved = $this->repository->findByToken($token);

        $this->assertNotNull($retrieved);
        $this->assertEquals($userId->toString(), $retrieved->userId()->toString());
    }

    public function testFindActiveByUserId(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());
        
        $userId = UserId::generate();
        $this->createUser($this->pdo, $userId->toString(), $companyId->toString());

        $session1 = Session::create(
            $userId,
            '10.0.0.1',
            'Device 1',
            bin2hex(random_bytes(32))
        );

        $session2 = Session::create(
            $userId,
            '10.0.0.2',
            'Device 2',
            bin2hex(random_bytes(32))
        );

        $this->repository->save($session1);
        $this->repository->save($session2);

        $sessions = $this->repository->findActiveByUserId($userId);

        $this->assertCount(2, $sessions);
    }

    public function testFindByIdReturnsNullForNonExistent(): void
    {
        $result = $this->repository->findById(SessionId::generate());
        $this->assertNull($result);
    }
}
