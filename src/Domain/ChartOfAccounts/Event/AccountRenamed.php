<?php

declare(strict_types=1);

namespace Domain\ChartOfAccounts\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final class AccountRenamed implements DomainEvent
{
    public function __construct(
        private readonly string $accountId,
        private readonly string $previousName,
        private readonly string $newName,
        private readonly string $renamedBy,
        private readonly DateTimeImmutable $occurredAt
    ) {
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'account.renamed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'previous_name' => $this->previousName,
            'new_name' => $this->newName,
            'renamed_by' => $this->renamedBy,
            'occurred_on' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function previousName(): string
    {
        return $this->previousName;
    }

    public function newName(): string
    {
        return $this->newName;
    }
}
