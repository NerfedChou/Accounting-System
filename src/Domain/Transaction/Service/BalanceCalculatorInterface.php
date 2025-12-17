<?php

declare(strict_types=1);

namespace Domain\Transaction\Service;

use Domain\ChartOfAccounts\ValueObject\NormalBalance;
use Domain\Shared\ValueObject\Money;
use Domain\Transaction\ValueObject\LineType;

/**
 * Service interface for calculating balance changes.
 * Implementation should be in Application layer.
 */
interface BalanceCalculatorInterface
{
    /**
     * Calculate balance change for a transaction line.
     *
     * Core principle:
     * - Same side as normal balance = INCREASE (positive)
     * - Opposite side = DECREASE (negative)
     *
     * Examples:
     * - Asset (normal: DEBIT), Debit $100 → +100 (balance increases)
     * - Asset (normal: DEBIT), Credit $100 → -100 (balance decreases)
     * - Liability (normal: CREDIT), Credit $100 → +100 (balance increases)
     * - Liability (normal: CREDIT), Debit $100 → -100 (balance decreases)
     */
    public function calculateBalanceChange(
        NormalBalance $normalBalance,
        LineType $lineType,
        Money $amount
    ): int; // Returns cents (positive = increase, negative = decrease)
}
