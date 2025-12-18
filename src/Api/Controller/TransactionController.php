<?php

declare(strict_types=1);

namespace Api\Controller;

use Api\Response\JsonResponse;
use Application\Command\Transaction\CreateTransactionCommand;
use Application\Command\Transaction\PostTransactionCommand;
use Application\Command\Transaction\TransactionLineData;
use Application\Command\Transaction\VoidTransactionCommand;
use Application\Dto\Transaction\TransactionDto;
use Application\Handler\Transaction\CreateTransactionHandler;
use Application\Handler\Transaction\PostTransactionHandler;
use Application\Handler\Transaction\VoidTransactionHandler;
use Domain\Company\ValueObject\CompanyId;
use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Domain\Transaction\ValueObject\TransactionId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Transaction controller for journal entry management.
 * Uses Application layer handlers for proper domain type translation.
 */
final class TransactionController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CreateTransactionHandler $createHandler,
        private readonly PostTransactionHandler $postHandler,
        private readonly VoidTransactionHandler $voidHandler,
    ) {
    }

    /**
     * GET /api/v1/companies/{companyId}/transactions
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $companyId = $request->getAttribute('companyId');
        if ($companyId === null) {
            return JsonResponse::error('Company ID required', 400);
        }

        try {
            $transactions = $this->transactionRepository->findByCompany(
                CompanyId::fromString($companyId)
            );

            $data = array_map(fn($t) => $this->formatTransactionSummary($t), $transactions);

            return JsonResponse::success($data);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/companies/{companyId}/transactions/{id}
     */
    public function get(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            return JsonResponse::error('Transaction ID required', 400);
        }

        try {
            $transaction = $this->transactionRepository->findById(
                TransactionId::fromString($id)
            );

            if ($transaction === null) {
                return JsonResponse::error('Transaction not found', 404);
            }

            return JsonResponse::success($this->formatTransactionSummary($transaction));
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/companies/{companyId}/transactions
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $companyId = $request->getAttribute('companyId');
        $userId = $request->getAttribute('user_id');
        $body = $request->getParsedBody();

        if ($companyId === null || $userId === null) {
            return JsonResponse::error('Company ID and authentication required', 400);
        }

        // Validate required fields
        if (empty($body['description'])) {
            return JsonResponse::error('Missing required field: description', 422);
        }

        if (empty($body['lines']) || !is_array($body['lines']) || count($body['lines']) < 2) {
            return JsonResponse::error('At least 2 transaction lines required', 422);
        }

        try {
            // Build line data for command
            $lines = [];
            foreach ($body['lines'] as $line) {
                if (empty($line['account_id']) || empty($line['line_type']) || !isset($line['amount_cents'])) {
                    return JsonResponse::error(
                        'Invalid line: account_id, line_type, amount_cents required', 
                        422
                    );
                }

                $lines[] = new TransactionLineData(
                    accountId: $line['account_id'],
                    lineType: $line['line_type'], // 'debit' or 'credit'
                    amountCents: (int) $line['amount_cents'],
                    description: $line['description'] ?? '',
                );
            }

            $command = new CreateTransactionCommand(
                companyId: $companyId,
                createdBy: $userId,
                description: $body['description'],
                currency: $body['currency'] ?? 'USD',
                lines: $lines,
                transactionDate: $body['date'] ?? null,
                referenceNumber: $body['reference_number'] ?? null,
            );

            $dto = $this->createHandler->handle($command);

            return JsonResponse::created($dto->toArray());
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/companies/{companyId}/transactions/{id}/post
     */
    public function post(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $userId = $request->getAttribute('user_id');

        if ($id === null || $userId === null) {
            return JsonResponse::error('Transaction ID and authentication required', 400);
        }

        try {
            $command = new PostTransactionCommand(
                transactionId: $id,
                postedBy: $userId,
            );

            $dto = $this->postHandler->handle($command);

            return JsonResponse::success($dto->toArray());
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/companies/{companyId}/transactions/{id}/void
     */
    public function void(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $userId = $request->getAttribute('user_id');
        $body = $request->getParsedBody();

        if ($id === null || $userId === null) {
            return JsonResponse::error('Transaction ID and authentication required', 400);
        }

        try {
            $command = new VoidTransactionCommand(
                transactionId: $id,
                voidedBy: $userId,
                reason: $body['reason'] ?? 'Voided via API',
            );

            $dto = $this->voidHandler->handle($command);

            return JsonResponse::success($dto->toArray());
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Format transaction entity for list/get responses.
     * Uses domain entity accessors directly for read operations.
     */
    private function formatTransactionSummary(mixed $transaction): array
    {
        return [
            'id' => $transaction->id()->toString(),
            'company_id' => $transaction->companyId()->toString(),
            'description' => $transaction->description(),
            'status' => $transaction->status()->value,
            'date' => $transaction->transactionDate()->format('Y-m-d'),
            'total_debits_cents' => $transaction->totalDebits()->cents(),
            'total_credits_cents' => $transaction->totalCredits()->cents(),
            'reference_number' => $transaction->referenceNumber(),
            'created_at' => $transaction->createdAt()->format('Y-m-d\TH:i:s\Z'),
            'posted_at' => $transaction->postedAt()?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
