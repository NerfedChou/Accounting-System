<?php

declare(strict_types=1);

namespace Domain\Ledger\Repository;

use DateTimeImmutable;
use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\Ledger\Entity\BalanceChange;
use Domain\Transaction\ValueObject\TransactionId;

interface BalanceChangeRepositoryInterface
{
    public function save(BalanceChange $change): void;

    /**
     * @return array<BalanceChange>
     */
    public function findByTransaction(TransactionId $transactionId): array;

    /**
     * @return array<BalanceChange>
     */
    public function findByAccount(
        AccountId $accountId,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array;

    /**
     * Check if a transaction has already been reversed.
     */
    public function isTransactionReversed(TransactionId $transactionId): bool;
}
