<?php

declare(strict_types=1);

namespace Domain\Reporting\Repository;

use Domain\Company\ValueObject\CompanyId;
use Domain\Reporting\ValueObject\ReportId;
use Domain\Reporting\ValueObject\ReportPeriod;

/**
 * Repository interface for persisting generated reports.
 */
interface ReportRepositoryInterface
{
    /**
     * Save generated report for history.
     *
     * @param array<string, mixed> $reportData
     */
    public function save(
        ReportId $id,
        CompanyId $companyId,
        string $reportType,
        ReportPeriod $period,
        array $reportData
    ): void;

    /**
     * Find report by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(ReportId $id): ?array;

    /**
     * Find reports by company and type.
     *
     * @return array<array<string, mixed>>
     */
    public function findByCompanyAndType(
        CompanyId $companyId,
        string $reportType,
        int $limit = 10
    ): array;
}
