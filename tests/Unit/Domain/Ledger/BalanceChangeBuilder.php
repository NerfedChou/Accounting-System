<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ledger;

use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\ChartOfAccounts\ValueObject\NormalBalance;
use Domain\Ledger\Entity\BalanceChange;
use Domain\Transaction\ValueObject\LineType;
use Domain\Transaction\ValueObject\TransactionId;

final class BalanceChangeBuilder
{
    private AccountId $accountId;
    private TransactionId $transactionId;
    private LineType $lineType;
    private int $amountCents = 1000;
    private int $previousBalanceCents = 0;
    private NormalBalance $normalBalance = NormalBalance::DEBIT;

    public function __construct()
    {
        $this->accountId = AccountId::generate();
        $this->transactionId = TransactionId::generate();
        $this->lineType = LineType::DEBIT;
    }

    public static function create(): self
    {
        return new self();
    }

    public function forAccount(AccountId $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function withTransaction(TransactionId $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function debit(int $amountCents): self
    {
        $this->lineType = LineType::DEBIT;
        $this->amountCents = $amountCents;
        return $this;
    }

    public function credit(int $amountCents): self
    {
        $this->lineType = LineType::CREDIT;
        $this->amountCents = $amountCents;
        return $this;
    }

    public function withPreviousBalance(int $cents): self
    {
        $this->previousBalanceCents = $cents;
        return $this;
    }

    public function withNormalBalance(NormalBalance $normalBalance): self
    {
        $this->normalBalance = $normalBalance;
        return $this;
    }

    public function build(): BalanceChange
    {
        return BalanceChange::create(
            $this->accountId,
            $this->transactionId,
            $this->lineType,
            $this->amountCents,
            $this->previousBalanceCents,
            $this->normalBalance
        );
    }
}
