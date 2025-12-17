<?php

declare(strict_types=1);

namespace Domain\Transaction\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class TransactionApprovalRequired implements DomainEvent
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private string $transactionId,
        private string $companyId,
        private string $reason,
        private array $details,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'transaction.approval_required';
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
            'transaction_id' => $this->transactionId,
            'company_id' => $this->companyId,
            'reason' => $this->reason,
            'details' => $this->details,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function transactionId(): string
    {
        return $this->transactionId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
