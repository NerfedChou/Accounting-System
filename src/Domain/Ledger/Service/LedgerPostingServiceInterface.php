<?php

declare(strict_types=1);

namespace Domain\Ledger\Service;

use Domain\Identity\ValueObject\UserId;
use Domain\Transaction\ValueObject\TransactionId;

/**
 * Service interface for posting transactions to the ledger.
 * Implementation should be in Application layer.
 */
interface LedgerPostingServiceInterface
{
    /**
     * Post a validated transaction to the ledger.
     * Creates balance changes for each line.
     */
    public function post(TransactionId $transactionId): PostingResult;

    /**
     * Reverse a posted transaction (for voids).
     * Creates opposite balance changes.
     */
    public function reverse(
        TransactionId $transactionId,
        UserId $reversedBy,
        string $reason
    ): ReversalResult;
}
