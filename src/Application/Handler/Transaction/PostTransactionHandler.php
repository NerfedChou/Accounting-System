<?php

declare(strict_types=1);

namespace Application\Handler\Transaction;

use Application\Command\CommandInterface;
use Application\Command\Transaction\PostTransactionCommand;
use Application\Dto\Transaction\TransactionDto;
use Application\Dto\Transaction\TransactionLineDto;
use Application\Handler\HandlerInterface;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\Exception\EntityNotFoundException;
use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Domain\Transaction\ValueObject\TransactionId;

/**
 * Handler for posting a transaction.
 *
 * @implements HandlerInterface<PostTransactionCommand>
 */
final readonly class PostTransactionHandler implements HandlerInterface
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): TransactionDto
    {
        assert($command instanceof PostTransactionCommand);

        $transactionId = TransactionId::fromString($command->transactionId);

        // Find transaction
        $transaction = $this->transactionRepository->findById($transactionId);

        if ($transaction === null) {
            throw new EntityNotFoundException("Transaction not found: {$command->transactionId}");
        }

        // Post transaction
        $transaction->post(UserId::fromString($command->postedBy));

        // Persist
        $this->transactionRepository->save($transaction);

        // Dispatch events
        foreach ($transaction->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $this->toDto($transaction);
    }

    private function toDto(Transaction $transaction): TransactionDto
    {
        $lines = [];
        $i = 0;
        foreach ($transaction->lines() as $line) {
            $lines[] = new TransactionLineDto(
                id: (string)$i,
                accountId: $line->accountId()->toString(),
                accountCode: 'Unknown',
                accountName: 'Unknown',
                lineType: $line->lineType()->value,
                amountCents: $line->amount()->cents(),
                lineOrder: $i++,
                description: $line->description() ?? '' // Description is nullable in entity but string in DTO? Check entity.
            );
        }

        return new TransactionDto(
            id: $transaction->id()->toString(),
            transactionNumber: $transaction->id()->toString(), // Mapping ID to transactionNumber
            companyId: $transaction->companyId()->toString(),
            status: $transaction->status()->value,
            description: $transaction->description(),
            totalDebitsCents: $transaction->totalDebits()->cents(),
            totalCreditsCents: $transaction->totalCredits()->cents(),
            lines: $lines,
            referenceNumber: $transaction->referenceNumber(),
            transactionDate: $transaction->transactionDate()->format('Y-m-d'),
            createdAt: $transaction->createdAt()->format('Y-m-d H:i:s'),
            postedAt: $transaction->postedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
