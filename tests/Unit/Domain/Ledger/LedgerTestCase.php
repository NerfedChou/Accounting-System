<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ledger;

use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\ChartOfAccounts\ValueObject\NormalBalance;
use Domain\Ledger\Entity\BalanceChange;
use Domain\Transaction\ValueObject\LineType;
use Domain\Transaction\ValueObject\TransactionId;
use PHPUnit\Framework\TestCase;

abstract class LedgerTestCase extends TestCase
{
    // Helper methods removed in favor of BalanceChangeBuilder

}
