<?php

declare(strict_types=1);

namespace Api\Controller;

use Api\Response\JsonResponse;
use Domain\ChartOfAccounts\Entity\Account;
use Domain\ChartOfAccounts\Repository\AccountRepositoryInterface;
use Domain\ChartOfAccounts\ValueObject\AccountCode;
use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\ChartOfAccounts\ValueObject\AccountType;
use Domain\Company\ValueObject\CompanyId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Account controller for Chart of Accounts management.
 */
final class AccountController
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {
    }

    /**
     * GET /api/v1/companies/{companyId}/accounts
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $companyId = $request->getAttribute('companyId');
        if ($companyId === null) {
            return JsonResponse::error('Company ID required', 400);
        }

        try {
            $accounts = $this->accountRepository->findByCompany(
                CompanyId::fromString($companyId)
            );

            $data = array_map(fn(Account $a) => $this->formatAccount($a), $accounts);

            return JsonResponse::success($data);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/companies/{companyId}/accounts/{id}
     */
    public function get(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            return JsonResponse::error('Account ID required', 400);
        }

        try {
            $account = $this->accountRepository->findById(
                AccountId::fromString($id)
            );

            if ($account === null) {
                return JsonResponse::error('Account not found', 404);
            }

            return JsonResponse::success($this->formatAccount($account));
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/companies/{companyId}/accounts
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $companyId = $request->getAttribute('companyId');
        if ($companyId === null) {
            return JsonResponse::error('Company ID required', 400);
        }

        $body = $request->getParsedBody();

        // Validate required fields
        $required = ['code', 'name'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return JsonResponse::error("Missing required field: $field", 422);
            }
        }

        try {
            $account = Account::create(
                AccountCode::fromInt((int) $body['code']),
                $body['name'],
                CompanyId::fromString($companyId),
                $body['description'] ?? null,
                isset($body['parent_id']) ? AccountId::fromString($body['parent_id']) : null
            );

            $this->accountRepository->save($account);

            return JsonResponse::created($this->formatAccount($account));
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Format account for API response.
     */
    private function formatAccount(Account $account): array
    {
        return [
            'id' => $account->id()->toString(),
            'code' => $account->code()->toInt(),
            'name' => $account->name(),
            'type' => $account->accountType()->value,
            'company_id' => $account->companyId()->toString(),
            'parent_id' => $account->parentAccountId()?->toString(),
            'is_active' => $account->isActive(),
        ];
    }
}
