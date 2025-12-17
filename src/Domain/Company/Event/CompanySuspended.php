<?php

declare(strict_types=1);

namespace Domain\Company\Event;

use DateTimeImmutable;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\DomainEvent;

final class CompanySuspended implements DomainEvent
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private readonly CompanyId $companyId,
        private readonly UserId $suspendedBy,
        private readonly string $reason
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function eventName(): string
    {
        return 'company.suspended';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'suspended_by' => $this->suspendedBy->toString(),
            'reason' => $this->reason,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function suspendedBy(): UserId
    {
        return $this->suspendedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
