<?php

declare(strict_types=1);

namespace Domain\Company\Event;

use DateTimeImmutable;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\DomainEvent;

final class CompanySettingsUpdated implements DomainEvent
{
    private DateTimeImmutable $occurredOn;

    /**
     * @param array<string, mixed> $previousSettings
     * @param array<string, mixed> $newSettings
     */
    public function __construct(
        private readonly CompanyId $companyId,
        private readonly UserId $updatedBy,
        private readonly array $previousSettings,
        private readonly array $newSettings
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function eventName(): string
    {
        return 'company.settings_updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'updated_by' => $this->updatedBy->toString(),
            'previous_settings' => $this->previousSettings,
            'new_settings' => $this->newSettings,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function updatedBy(): UserId
    {
        return $this->updatedBy;
    }

    /**
     * @return array<string, mixed>
     */
    public function previousSettings(): array
    {
        return $this->previousSettings;
    }

    /**
     * @return array<string, mixed>
     */
    public function newSettings(): array
    {
        return $this->newSettings;
    }
}
