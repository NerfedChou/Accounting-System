<?php

declare(strict_types=1);

namespace Api\Controller;

use Api\Response\JsonResponse;
use Domain\Approval\Repository\ApprovalRepositoryInterface;
use Domain\ChartOfAccounts\Repository\AccountRepositoryInterface;
use Domain\Transaction\Repository\TransactionRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dashboard controller for system-wide statistics.
 * No company scoping - returns aggregate data across all companies.
 */
final class DashboardController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly ApprovalRepositoryInterface $approvalRepository,
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * GET /api/v1/dashboard/stats
     * Returns system-wide statistics for the dashboard.
     */
    public function stats(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Get counts from repositories
            $pendingCount = $this->approvalRepository->countPending();
            $transactionCount = $this->transactionRepository->countToday();
            $accountCount = $this->accountRepository->countActive();
            
            return JsonResponse::success([
                'pending_approvals' => $pendingCount,
                'todays_transactions' => $transactionCount,
                'gl_accounts' => $accountCount,
                'active_sessions' => 1, // Can be expanded later
            ]);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }
}
