<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ledger\Entity;

use Tests\Unit\Domain\Ledger\LedgerTestCase;

use Domain\Ledger\Entity\BalanceChange;

use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\ChartOfAccounts\ValueObject\NormalBalance;
use Domain\Transaction\ValueObject\LineType;
use Domain\Transaction\ValueObject\TransactionId;
use Tests\Unit\Domain\Ledger\BalanceChangeBuilder;
final class BalanceChangeTest extends LedgerTestCase
{
    private AccountId $accountId;
    private TransactionId $transactionId;


    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->transactionId = TransactionId::generate();
    }

    public function test_debit_to_debit_normal_balance_increases(): void
    {
        $change = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->withTransaction($this->transactionId)
            ->debit(10000)
            ->withPreviousBalance(50000)
            ->withNormalBalance(NormalBalance::DEBIT)
            ->build();

        $this->assertEquals(10000, $change->changeCents());
        $this->assertEquals(60000, $change->newBalanceCents());
        $this->assertFalse($change->isReversal());
    }

    public function test_credit_to_debit_normal_balance_decreases(): void
    {
        $change = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->withTransaction($this->transactionId)
            ->credit(10000)
            ->withPreviousBalance(50000)
            ->withNormalBalance(NormalBalance::DEBIT)
            ->build();

        $this->assertEquals(-10000, $change->changeCents());
        $this->assertEquals(40000, $change->newBalanceCents());
    }

    public function test_credit_to_credit_normal_balance_increases(): void
    {
        $change = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->withTransaction($this->transactionId)
            ->credit(10000)
            ->withPreviousBalance(50000)
            ->withNormalBalance(NormalBalance::CREDIT)
            ->build();

        $this->assertEquals(10000, $change->changeCents());
        $this->assertEquals(60000, $change->newBalanceCents());
    }

    public function test_debit_to_credit_normal_balance_decreases(): void
    {
        $change = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->withTransaction($this->transactionId)
            ->debit(10000)
            ->withPreviousBalance(50000)
            ->withNormalBalance(NormalBalance::CREDIT)
            ->build();

        $this->assertEquals(-10000, $change->changeCents());
        $this->assertEquals(40000, $change->newBalanceCents());
    }

    public function test_to_array_returns_all_fields(): void
    {
        $change = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->withTransaction($this->transactionId)
            ->debit(10000)
            ->withPreviousBalance(50000)
            ->withNormalBalance(NormalBalance::DEBIT)
            ->build();

        $array = $change->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('account_id', $array);
        $this->assertArrayHasKey('change_cents', $array);
    }
}
