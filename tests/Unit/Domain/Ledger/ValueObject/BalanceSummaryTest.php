<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ledger\ValueObject;

use Domain\Ledger\ValueObject\BalanceSummary;
use Domain\Shared\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

final class BalanceSummaryTest extends TestCase
{
    public function test_empty_summary_is_balanced(): void
    {
        $summary = BalanceSummary::empty(Currency::PHP);

        $this->assertTrue($summary->isBalanced());
        $this->assertEquals(0, $summary->imbalanceCents());
    }

    public function test_balanced_summary(): void
    {
        // Assets = Liabilities + Equity + Net Income
        // 100000 = 30000 + 50000 + (40000 - 20000)
        // 100000 = 30000 + 50000 + 20000
        $summary = new BalanceSummary(
            totalAssetsCents: 100000,
            totalLiabilitiesCents: 30000,
            totalEquityCents: 50000,
            totalRevenueCents: 40000,
            totalExpensesCents: 20000,
            currency: Currency::PHP
        );

        $this->assertTrue($summary->isBalanced());
        $this->assertEquals(20000, $summary->netIncomeCents());
    }

    public function test_unbalanced_summary(): void
    {
        // Assets = 100000
        // Right side = 30000 + 50000 + (40000 - 20000) = 100000
        // But we'll make it unbalanced by changing assets
        $summary = new BalanceSummary(
            totalAssetsCents: 110000, // 10000 more than balanced
            totalLiabilitiesCents: 30000,
            totalEquityCents: 50000,
            totalRevenueCents: 40000,
            totalExpensesCents: 20000,
            currency: Currency::PHP
        );

        $this->assertFalse($summary->isBalanced());
        $this->assertEquals(10000, $summary->imbalanceCents());
    }

    public function test_net_income_with_loss(): void
    {
        // Expenses > Revenue = Net Loss
        $summary = new BalanceSummary(
            totalAssetsCents: 50000,
            totalLiabilitiesCents: 30000,
            totalEquityCents: 40000,
            totalRevenueCents: 10000,
            totalExpensesCents: 30000, // Loss of 20000
            currency: Currency::PHP
        );

        $this->assertEquals(-20000, $summary->netIncomeCents());
        // Net Income method returns 0 for losses
        $this->assertEquals(0, $summary->netIncome()->cents());
    }

    public function test_to_array(): void
    {
        $summary = new BalanceSummary(
            totalAssetsCents: 100000,
            totalLiabilitiesCents: 30000,
            totalEquityCents: 50000,
            totalRevenueCents: 40000,
            totalExpensesCents: 20000,
            currency: Currency::PHP
        );

        $array = $summary->toArray();

        $this->assertEquals(100000, $array['total_assets_cents']);
        $this->assertEquals(30000, $array['total_liabilities_cents']);
        $this->assertEquals(50000, $array['total_equity_cents']);
        $this->assertEquals(40000, $array['total_revenue_cents']);
        $this->assertEquals(20000, $array['total_expenses_cents']);
        $this->assertEquals(20000, $array['net_income_cents']);
        $this->assertTrue($array['is_balanced']);
    }

    public function test_allows_1_cent_tolerance(): void
    {
        // Off by 1 cent due to rounding
        $summary = new BalanceSummary(
            totalAssetsCents: 100001,
            totalLiabilitiesCents: 30000,
            totalEquityCents: 50000,
            totalRevenueCents: 40000,
            totalExpensesCents: 20000,
            currency: Currency::PHP
        );

        $this->assertTrue($summary->isBalanced());
    }
}
