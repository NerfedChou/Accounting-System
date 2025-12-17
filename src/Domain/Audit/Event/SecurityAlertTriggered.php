<?php

declare(strict_types=1);

namespace Domain\Audit\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class SecurityAlertTriggered implements DomainEvent
{
    public function __construct(
        private string $alertType,
        private ?string $companyId,
        private ?string $userId,
        private ?string $ipAddress,
        private string $details,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'audit.security_alert_triggered';
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
            'alert_type' => $this->alertType,
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
            'details' => $this->details,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function alertType(): string
    {
        return $this->alertType;
    }
}
