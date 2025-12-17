<?php

declare(strict_types=1);

namespace Domain\Transaction\Repository;

use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\Transaction\Entity\TransactionLine;
use Domain\Transaction\ValueObject\TransactionId;

interface TransactionLineRepositoryInterface
{
    public function save(TransactionLine $line): void;

    /**
     * @return array<TransactionLine>
     */
    public function findByTransaction(TransactionId $transactionId): array;

    /**
     * @return array<TransactionLine>
     */
    public function findByAccount(AccountId $accountId): array;

    public function deleteByTransaction(TransactionId $transactionId): void;
}
