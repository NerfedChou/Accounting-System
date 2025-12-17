<?php

declare(strict_types=1);

namespace Domain\Audit\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class AuditLogCreated implements DomainEvent
{
    public function __construct(
        private string $activityId,
        private string $companyId,
        private string $activityType,
        private string $entityType,
        private string $entityId,
        private string $actorId,
        private string $severity,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'audit.log_created';
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
            'activity_id' => $this->activityId,
            'company_id' => $this->companyId,
            'activity_type' => $this->activityType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'actor_id' => $this->actorId,
            'severity' => $this->severity,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
