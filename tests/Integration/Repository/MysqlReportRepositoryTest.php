<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use DateTimeImmutable;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Reporting\Entity\Report;
use Domain\Reporting\ValueObject\ReportId;
use Domain\Reporting\ValueObject\ReportPeriod;
use Infrastructure\Persistence\Mysql\Repository\MysqlReportRepository;
use Tests\Integration\BaseIntegrationTestCase;
use Tests\Integration\DatabaseTestHelper;

class MysqlReportRepositoryTest extends BaseIntegrationTestCase
{
    use DatabaseTestHelper;

    private MysqlReportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MysqlReportRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());

        // Seed a system user for the generated_by FK constraint
        // MysqlReportRepository uses '00000000-0000-0000-0000-000000000000'
        $this->createUser($this->pdo, '00000000-0000-0000-0000-000000000000', $companyId->toString(), 'system@system.local');

        $report = new Report(
            ReportId::generate(),
            $companyId,
            ReportPeriod::month(2024, 1),
            'balance_sheet',
            ['assets' => 100000, 'liabilities' => 50000, 'equity' => 50000],
            new DateTimeImmutable()
        );

        $this->repository->save($report);

        $retrieved = $this->repository->findById($report->id());

        $this->assertNotNull($retrieved);
        $this->assertEquals('balance_sheet', $retrieved->type());
        $this->assertArrayHasKey('assets', $retrieved->data());
    }

    public function testFindByCompany(): void
    {
        $companyId = CompanyId::generate();
        $this->createCompany($this->pdo, $companyId->toString());
        
        // Seed system user for FK
        $this->createUser($this->pdo, '00000000-0000-0000-0000-000000000000', $companyId->toString(), 'system@system.local');

        $report1 = new Report(
            ReportId::generate(),
            $companyId,
            ReportPeriod::month(2024, 1),
            'balance_sheet',
            ['total' => 100000],
            new DateTimeImmutable()
        );

        $report2 = new Report(
            ReportId::generate(),
            $companyId,
            ReportPeriod::month(2024, 2),
            'income_statement',
            ['revenue' => 25000],
            new DateTimeImmutable()
        );

        $this->repository->save($report1);
        $this->repository->save($report2);

        $reports = $this->repository->findByCompany($companyId);

        $this->assertCount(2, $reports);
    }

    public function testFindByIdReturnsNullForNonExistent(): void
    {
        $result = $this->repository->findById(ReportId::generate());
        $this->assertNull($result);
    }
}
