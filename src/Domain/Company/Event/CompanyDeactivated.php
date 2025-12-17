<?php

declare(strict_types=1);

namespace Domain\Company\Event;

use DateTimeImmutable;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\DomainEvent;

final class CompanyDeactivated implements DomainEvent
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private readonly CompanyId $companyId,
        private readonly UserId $deactivatedBy,
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
        return 'company.deactivated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'deactivated_by' => $this->deactivatedBy->toString(),
            'reason' => $this->reason,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function deactivatedBy(): UserId
    {
        return $this->deactivatedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
