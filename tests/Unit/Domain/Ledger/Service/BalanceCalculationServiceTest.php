<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ledger\Service;

use Domain\ChartOfAccounts\ValueObject\NormalBalance;
use Domain\Ledger\Service\BalanceCalculationService;
use Domain\Transaction\ValueObject\LineType;
use PHPUnit\Framework\TestCase;

final class BalanceCalculationServiceTest extends TestCase
{
    private BalanceCalculationService $service;

    protected function setUp(): void
    {
        $this->service = new BalanceCalculationService();
    }

    public function test_debit_to_debit_normal_increases(): void
    {
        $change = $this->service->calculateChange(
            NormalBalance::DEBIT,
            LineType::DEBIT,
            10000
        );

        $this->assertEquals(10000, $change);
    }

    public function test_credit_to_debit_normal_decreases(): void
    {
        $change = $this->service->calculateChange(
            NormalBalance::DEBIT,
            LineType::CREDIT,
            10000
        );

        $this->assertEquals(-10000, $change);
    }

    public function test_credit_to_credit_normal_increases(): void
    {
        $change = $this->service->calculateChange(
            NormalBalance::CREDIT,
            LineType::CREDIT,
            10000
        );

        $this->assertEquals(10000, $change);
    }

    public function test_debit_to_credit_normal_decreases(): void
    {
        $change = $this->service->calculateChange(
            NormalBalance::CREDIT,
            LineType::DEBIT,
            10000
        );

        $this->assertEquals(-10000, $change);
    }

    public function test_project_balance_adds_change(): void
    {
        $newBalance = $this->service->projectBalance(50000, 10000);

        $this->assertEquals(60000, $newBalance);
    }

    public function test_project_balance_with_negative_change(): void
    {
        $newBalance = $this->service->projectBalance(50000, -10000);

        $this->assertEquals(40000, $newBalance);
    }
}
