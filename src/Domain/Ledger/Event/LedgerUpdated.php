<?php

declare(strict_types=1);

namespace Domain\Ledger\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class LedgerUpdated implements DomainEvent
{
    /**
     * @param array<array<string, mixed>> $balanceChanges
     */
    public function __construct(
        private string $ledgerId,
        private string $companyId,
        private string $transactionId,
        private array $balanceChanges,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'ledger.updated';
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
            'ledger_id' => $this->ledgerId,
            'company_id' => $this->companyId,
            'transaction_id' => $this->transactionId,
            'balance_changes' => $this->balanceChanges,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function ledgerId(): string
    {
        return $this->ledgerId;
    }

    public function transactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function balanceChanges(): array
    {
        return $this->balanceChanges;
    }
}
