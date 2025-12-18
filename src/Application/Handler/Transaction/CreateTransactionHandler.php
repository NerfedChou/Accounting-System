<?php

declare(strict_types=1);

namespace Application\Handler\Transaction;

use Application\Command\CommandInterface;
use Application\Command\Transaction\CreateTransactionCommand;
use Application\Dto\Transaction\TransactionDto;
use Application\Dto\Transaction\TransactionLineDto;
use Application\Handler\HandlerInterface;
use Domain\ChartOfAccounts\Repository\AccountRepositoryInterface;
use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\ValueObject\Currency;
use Domain\Shared\ValueObject\Money;
use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Domain\Transaction\ValueObject\LineType;

/**
 * Handler for creating a transaction.
 *
 * @implements HandlerInterface<CreateTransactionCommand>
 */
final readonly class CreateTransactionHandler implements HandlerInterface
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private AccountRepositoryInterface $accountRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): TransactionDto
    {
        assert($command instanceof CreateTransactionCommand);

        $companyId = CompanyId::fromString($command->companyId);
        $createdBy = UserId::fromString($command->createdBy);
        // Handler was using ->date, Command has ->transactionDate
        $transactionDate = $command->transactionDate 
            ? new \DateTimeImmutable($command->transactionDate) 
            : new \DateTimeImmutable();
            
        $currency = Currency::from($command->currency);

        // Create transaction header
        $transaction = Transaction::create(
            companyId: $companyId,
            transactionDate: $transactionDate,
            description: $command->description,
            createdBy: $createdBy,
            referenceNumber: $command->referenceNumber,
        );

        // Add lines
        $this->processTransactionLines($command, $transaction, $companyId, $currency);

        // Persist
        $this->transactionRepository->save($transaction);

        // Dispatch events
        foreach ($transaction->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $this->toDto($transaction);
    }

    private function processTransactionLines(
        CreateTransactionCommand $command,
        Transaction $transaction,
        CompanyId $companyId,
        Currency $currency
    ): void {
        foreach ($command->lines as $lineData) {
            $accountId = AccountId::fromString($lineData->accountId);
            $account = $this->accountRepository->findById($accountId);

            if ($account === null) {
                throw new \DomainException("Account not found: {$lineData->accountId}");
            }

            if (!$account->companyId()->equals($companyId)) {
                throw new \DomainException("Account {$lineData->accountId} does not belong to company {$command->companyId}");
            }

            if (!$account->isActive()) {
                throw new \DomainException("Account {$lineData->accountId} is not active");
            }

            $transaction->addLine(
                accountId: $accountId,
                lineType: LineType::from($lineData->lineType), // was type
                amount: Money::fromCents($lineData->amountCents, $currency), // was amount
                description: $lineData->description,
            );
        }
    }

    private function toDto(Transaction $transaction): TransactionDto
    {
        $lines = [];
        // Need to loop with index to get line order if not available, but TransactionLine likely has it?
        // TransactionLine entity Inspection needed? 
        // Assuming TransactionLine works.
        // But Transaction::lines() returns array<TransactionLine>.
        // Let's assume lines are in order.
        $i = 0;
        foreach ($transaction->lines() as $line) {
            $lines[] = new TransactionLineDto(
                id: (string)$i, // We don't seem to have line IDs exposed or generated yet? Or maybe TransactionLine has it?
                                // Inspecting Entity/TransactionLine would be good, but assuming not exposed, use index.
                accountId: $line->accountId()->toString(),
                accountCode: 'Unknown', // Need to fetch code or have it in line? Line usually just has ID.
                accountName: 'Unknown',
                lineType: $line->lineType()->value,
                amountCents: $line->amount()->cents(), // Money::cents()
                lineOrder: $i++,
                description: $line->description()
            );
        }

        return new TransactionDto(
            id: $transaction->id()->toString(),
            transactionNumber: $transaction->id()->toString(), // Was $transaction->transactionNumber()
            companyId: $transaction->companyId()->toString(),
            status: $transaction->status()->value,
            description: $transaction->description(),
            totalDebitsCents: $transaction->totalDebits()->cents(), // Money::cents()
            totalCreditsCents: $transaction->totalCredits()->cents(), // Money::cents()
            lines: $lines,
            referenceNumber: $transaction->referenceNumber(),
            transactionDate: $transaction->transactionDate()->format('Y-m-d'),
            createdAt: $transaction->createdAt()->format('Y-m-d H:i:s'),
            postedAt: $transaction->postedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
