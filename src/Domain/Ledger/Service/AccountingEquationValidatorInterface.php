<?php

declare(strict_types=1);

namespace Domain\Ledger\Service;

use Domain\Ledger\ValueObject\BalanceSummary;
use Domain\Transaction\Service\ValidationResult;

/**
 * Domain service for validating the accounting equation.
 * Implements BR-LP-005.
 */
interface AccountingEquationValidatorInterface
{
    /**
     * Validate that accounting equation is balanced.
     * Assets = Liabilities + Equity + (Revenue - Expenses)
     */
    public function validate(BalanceSummary $summary): ValidationResult;

    /**
     * Validate equation would remain balanced after projected changes.
     *
     * @param BalanceSummary $currentSummary Current state
     * @param array<string, int> $projectedChanges Map of account type to change in cents
     */
    public function validateWithChanges(
        BalanceSummary $currentSummary,
        array $projectedChanges
    ): ValidationResult;
}
