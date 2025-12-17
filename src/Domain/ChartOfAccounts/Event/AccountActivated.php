<?php

declare(strict_types=1);

namespace Domain\ChartOfAccounts\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final class AccountActivated implements DomainEvent
{
    public function __construct(
        private readonly string $accountId,
        private readonly string $accountCode,
        private readonly string $activatedBy,
        private readonly DateTimeImmutable $occurredAt
    ) {
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'account.activated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'account_code' => $this->accountCode,
            'activated_by' => $this->activatedBy,
            'occurred_on' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function accountCode(): string
    {
        return $this->accountCode;
    }
}
