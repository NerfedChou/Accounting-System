<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ledger\Entity;

use Tests\Unit\Domain\Ledger\LedgerTestCase;

use Domain\Ledger\Entity\BalanceChange;
use Domain\Transaction\ValueObject\LineType;
use Tests\Unit\Domain\Ledger\BalanceChangeBuilder;

use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\ChartOfAccounts\ValueObject\AccountType;
use Domain\ChartOfAccounts\ValueObject\NormalBalance;
use Domain\Company\ValueObject\CompanyId;
use Domain\Ledger\Dto\AccountInitializationParams;
use Domain\Ledger\Entity\AccountBalance;
use Domain\Shared\ValueObject\Currency;
use Domain\Transaction\ValueObject\TransactionId;
final class AccountBalanceTest extends LedgerTestCase
{
    private AccountId $accountId;
    private CompanyId $companyId;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->companyId = CompanyId::generate();
    }

    public function test_initializes_with_zero_balance(): void
    {
        $balance = $this->createAssetBalance();

        $this->assertEquals(0, $balance->currentBalanceCents());
        $this->assertEquals(0, $balance->transactionCount());
    }

    public function test_initializes_with_opening_balance(): void
    {
        $balance = $this->createAssetBalance(100000);

        $this->assertEquals(100000, $balance->currentBalanceCents());
        $this->assertEquals(100000, $balance->openingBalanceCents());
    }

    /**
     * @dataProvider balanceChangeProvider
     */
    public function test_applying_balance_change(
        int $openingBalance,
        LineType $lineType,
        int $amount,
        int $expectedBalance
    ): void {
        $balance = $this->createAssetBalance($openingBalance);
        
        $builder = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->withPreviousBalance($openingBalance)
            ->withNormalBalance(NormalBalance::DEBIT);

        if ($lineType === LineType::DEBIT) {
            $builder->debit($amount);
        } else {
            $builder->credit($amount);
        }

        $balance->applyChange($builder->build());

        $this->assertEquals($expectedBalance, $balance->currentBalanceCents());
        if ($expectedBalance !== $openingBalance) {
             $this->assertEquals(1, $balance->transactionCount());
        }
    }

    public static function balanceChangeProvider(): array
    {
        return [
            'debit to asset increases balance' => [
                'openingBalance' => 100000,
                'lineType' => LineType::DEBIT,
                'amount' => 50000,
                'expectedBalance' => 150000,
            ],
            'credit to asset decreases balance' => [
                'openingBalance' => 100000,
                'lineType' => LineType::CREDIT,
                'amount' => 30000,
                'expectedBalance' => 70000,
            ],
        ];
    }

    public function test_would_be_negative_after_change(): void
    {
        $balance = $this->createAssetBalance(10000);

        $this->assertTrue($balance->wouldBeNegativeAfterChange(-20000));
        $this->assertFalse($balance->wouldBeNegativeAfterChange(-5000));
    }

    public function test_equity_can_have_negative_balance(): void
    {
        $params = new AccountInitializationParams(
            $this->accountId,
            $this->companyId,
            AccountType::EQUITY,
            Currency::PHP
        );

        $balance = AccountBalance::initialize($params);

        $this->assertTrue($balance->canHaveNegativeBalance());
    }

    public function test_records_domain_events(): void
    {
        $balance = $this->createAssetBalance(100000);
        $change = BalanceChangeBuilder::create()
            ->forAccount($this->accountId)
            ->debit(50000)
            ->withPreviousBalance(100000)
            ->withNormalBalance(NormalBalance::DEBIT)
            ->build();

        $balance->applyChange($change);

        $events = $balance->releaseEvents();
        $this->assertCount(1, $events);
    }

    private function createAssetBalance(int $openingCents = 0): AccountBalance
    {
        $params = new AccountInitializationParams(
            $this->accountId,
            $this->companyId,
            AccountType::ASSET,
            Currency::PHP,
            $openingCents
        );

        return AccountBalance::initialize($params);
    }
}
