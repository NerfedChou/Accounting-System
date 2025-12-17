<?php

declare(strict_types=1);

namespace Domain\Approval\ValueObject;

use Domain\Shared\ValueObject\Money;

/**
 * Value object representing the reason an approval is required.
 */
final readonly class ApprovalReason
{
    /**
     * @param array<string, mixed> $details
     */
    private function __construct(
        private ApprovalType $type,
        private string $description,
        private array $details
    ) {
    }

    public static function negativeEquity(string $accountName, int $projectedBalanceCents): self
    {
        return new self(
            ApprovalType::NEGATIVE_EQUITY,
            sprintf('Transaction would result in negative %s balance', $accountName),
            [
                'account_name' => $accountName,
                'projected_balance_cents' => $projectedBalanceCents,
            ]
        );
    }

    public static function highValue(int $amountCents, int $thresholdCents): self
    {
        return new self(
            ApprovalType::HIGH_VALUE,
            sprintf(
                'Transaction amount (%d cents) exceeds approval threshold (%d cents)',
                $amountCents,
                $thresholdCents
            ),
            [
                'amount_cents' => $amountCents,
                'threshold_cents' => $thresholdCents,
            ]
        );
    }

    public static function backdated(\DateTimeImmutable $transactionDate, int $daysBack): self
    {
        return new self(
            ApprovalType::BACKDATED_TRANSACTION,
            sprintf('Transaction backdated %d days to %s', $daysBack, $transactionDate->format('Y-m-d')),
            [
                'transaction_date' => $transactionDate->format('Y-m-d'),
                'days_back' => $daysBack,
            ]
        );
    }

    public static function voidTransaction(string $transactionNumber): self
    {
        return new self(
            ApprovalType::VOID_TRANSACTION,
            sprintf('Request to void transaction %s', $transactionNumber),
            [
                'transaction_number' => $transactionNumber,
            ]
        );
    }

    public function type(): ApprovalType
    {
        return $this->type;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'description' => $this->description,
            'details' => $this->details,
        ];
    }
}
