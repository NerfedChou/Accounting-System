<?php

declare(strict_types=1);

namespace Application\Command\Transaction;

use Application\Command\CommandInterface;

/**
 * Represents a line item in a transaction.
 */
final readonly class TransactionLineData
{
    public function __construct(
        public string $accountId,
        public string $lineType,
        public int $amountCents,
        public string $description,
    ) {
    }
}

/**
 * Command to create a new transaction.
 */
final readonly class CreateTransactionCommand implements CommandInterface
{
    /**
     * @param TransactionLineData[] $lines
     */
    public function __construct(
        public string $companyId,
        public string $createdBy,
        public string $description,
        public string $currency,
        public array $lines,
        public ?string $transactionDate = null,
        public ?string $referenceNumber = null,
    ) {
    }
}
