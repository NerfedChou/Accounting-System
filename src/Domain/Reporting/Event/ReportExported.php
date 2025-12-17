<?php

declare(strict_types=1);

namespace Domain\Reporting\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class ReportExported implements DomainEvent
{
    public function __construct(
        private string $reportId,
        private string $format,
        private string $exportedBy,
        private int $fileSize,
        private string $filePath,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'reporting.report_exported';
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
            'format' => $this->format,
            'exported_by' => $this->exportedBy,
            'file_size' => $this->fileSize,
            'file_path' => $this->filePath,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function reportId(): string
    {
        return $this->reportId;
    }

    public function format(): string
    {
        return $this->format;
    }
}
