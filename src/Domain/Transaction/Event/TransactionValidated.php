<?php

declare(strict_types=1);

namespace Domain\Transaction\Event;

use DateTimeImmutable;
use Domain\Shared\Event\DomainEvent;

final readonly class TransactionValidated implements DomainEvent
{
    /**
     * @param array<string, bool> $validationResults
     */
    public function __construct(
        private string $transactionId,
        private bool $isValid,
        private array $validationResults,
        private bool $requiresApproval,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function eventName(): string
    {
        return 'transaction.validated';
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
            'is_valid' => $this->isValid,
            'validation_results' => $this->validationResults,
            'requires_approval' => $this->requiresApproval,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function transactionId(): string
    {
        return $this->transactionId;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }
}
