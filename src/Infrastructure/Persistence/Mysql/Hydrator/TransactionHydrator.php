<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Mysql\Hydrator;

use DateTimeImmutable;
use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\ValueObject\Currency;
use Domain\Shared\ValueObject\Money;
use Domain\Transaction\Entity\Transaction;
use Domain\Transaction\Entity\TransactionLine;
use Domain\Transaction\ValueObject\LineType;
use Domain\Transaction\ValueObject\TransactionId;
use Domain\Transaction\ValueObject\TransactionLineId;
use Domain\Transaction\ValueObject\TransactionStatus;
use ReflectionClass;

/**
 * Hydrates Transaction entities from database rows and extracts data for persistence.
 */
final class TransactionHydrator
{
    /**
     * Hydrate a Transaction entity from a database row.
     *
     * @param array<string, mixed> $row
     * @param array<int, array<string, mixed>> $lineRows
     */
    public function hydrate(array $row, array $lineRows = []): Transaction
    {
        $reflection = new ReflectionClass(Transaction::class);
        $transaction = $reflection->newInstanceWithoutConstructor();

        $this->setProperty($reflection, $transaction, 'id', TransactionId::fromString($row['id']));
        $this->setProperty($reflection, $transaction, 'companyId', CompanyId::fromString($row['company_id']));
        $this->setProperty(
            $reflection,
            $transaction,
            'transactionDate',
            new DateTimeImmutable($row['transaction_date'])
        );
        $this->setProperty($reflection, $transaction, 'description', $row['description']);
        $this->setProperty($reflection, $transaction, 'createdBy', UserId::fromString($row['created_by']));
        $this->setProperty($reflection, $transaction, 'createdAt', new DateTimeImmutable($row['created_at']));
        $this->setProperty($reflection, $transaction, 'status', TransactionStatus::from($row['status']));
        $this->setProperty($reflection, $transaction, 'referenceNumber', $row['reference_number']);

        // Optional fields
        $this->setProperty(
            $reflection,
            $transaction,
            'postedAt',
            $row['posted_at'] !== null ? new DateTimeImmutable($row['posted_at']) : null
        );
        $this->setProperty(
            $reflection,
            $transaction,
            'postedBy',
            $row['posted_by'] !== null ? UserId::fromString($row['posted_by']) : null
        );
        $this->setProperty(
            $reflection,
            $transaction,
            'voidedAt',
            $row['voided_at'] !== null ? new DateTimeImmutable($row['voided_at']) : null
        );
        $this->setProperty(
            $reflection,
            $transaction,
            'voidedBy',
            $row['voided_by'] !== null ? UserId::fromString($row['voided_by']) : null
        );
        $this->setProperty($reflection, $transaction, 'voidReason', $row['void_reason']);
        $this->setProperty($reflection, $transaction, 'domainEvents', []);

        // Hydrate lines
        $lines = [];
        foreach ($lineRows as $lineRow) {
            $lines[] = $this->hydrateLine($lineRow);
        }
        $this->setProperty($reflection, $transaction, 'lines', $lines);

        return $transaction;
    }

    /**
     * Hydrate a TransactionLine from a database row.
     *
     * @param array<string, mixed> $row
     */
    public function hydrateLine(array $row): TransactionLine
    {
        return TransactionLine::reconstitute(
            id: TransactionLineId::fromString($row['id']),
            accountId: AccountId::fromString($row['account_id']),
            lineType: LineType::from($row['line_type']),
            amount: Money::fromCents((int) $row['amount_cents'], Currency::from($row['currency'])),
            description: $row['description'],
        );
    }

    /**
     * Extract data from Transaction entity for persistence.
     *
     * @return array<string, mixed>
     */
    public function extract(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id()->toString(),
            'company_id' => $transaction->companyId()->toString(),
            'transaction_date' => $transaction->transactionDate()->format('Y-m-d'),
            'description' => $transaction->description(),
            'reference_number' => $transaction->referenceNumber(),
            'status' => $transaction->status()->value,
            'created_by' => $transaction->createdBy()->toString(),
            'created_at' => $transaction->createdAt()->format('Y-m-d H:i:s'),
            'posted_by' => $transaction->postedBy()?->toString(),
            'posted_at' => $transaction->postedAt()?->format('Y-m-d H:i:s'),
            'voided_by' => $transaction->voidedBy()?->toString(),
            'voided_at' => $transaction->voidedAt()?->format('Y-m-d H:i:s'),
            'void_reason' => $transaction->voidReason(),
        ];
    }

    /**
     * Extract data from TransactionLine for persistence.
     *
     * @return array<string, mixed>
     */
    public function extractLine(TransactionLine $line, string $transactionId, int $lineOrder): array
    {
        return [
            'id' => $line->id()->toString(),
            'transaction_id' => $transactionId,
            'account_id' => $line->accountId()->toString(),
            'line_type' => $line->lineType()->value,
            'amount_cents' => $line->amount()->cents(),
            'currency' => $line->amount()->currency()->value,
            'description' => $line->description(),
            'line_order' => $lineOrder,
        ];
    }

    /**
     * Set a property value using reflection.
     */
    private function setProperty(ReflectionClass $reflection, object $object, string $property, mixed $value): void
    {
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
