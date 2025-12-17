<?php

declare(strict_types=1);

namespace Domain\Ledger\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class NegativeBalanceDetected implements DomainEvent
{
    public function __construct(
        private string $accountId,
        private string $accountName,
        private string $accountType,
        private int $projectedBalanceCents,
        private string $transactionId,
        private bool $requiresApproval,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'ledger.negative_balance_detected';
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
            'account_id' => $this->accountId,
            'account_name' => $this->accountName,
            'account_type' => $this->accountType,
            'projected_balance_cents' => $this->projectedBalanceCents,
            'transaction_id' => $this->transactionId,
            'requires_approval' => $this->requiresApproval,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function accountType(): string
    {
        return $this->accountType;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }
}
