<?php

declare(strict_types=1);

namespace Domain\Reporting\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class ReportGenerated implements DomainEvent
{
    public function __construct(
        private string $reportId,
        private string $reportType,
        private string $companyId,
        private string $periodStart,
        private string $periodEnd,
        private string $generatedBy,
        private string $format,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'reporting.report_generated';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'report_type' => $this->reportType,
            'company_id' => $this->companyId,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'generated_by' => $this->generatedBy,
            'format' => $this->format,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function reportId(): string
    {
        return $this->reportId;
    }

    public function reportType(): string
    {
        return $this->reportType;
    }
}
