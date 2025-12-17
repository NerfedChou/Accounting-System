<?php

declare(strict_types=1);

namespace Domain\ChartOfAccounts\Service;

use Domain\ChartOfAccounts\Entity\Account;
use Domain\Company\ValueObject\CompanyId;

/**
 * Service interface for initializing a default chart of accounts for new companies.
 * Implementation should be in Application layer.
 */
interface ChartOfAccountsInitializerInterface
{
    /**
     * Create standard chart of accounts for a new company.
     * Called in response to CompanyCreated event.
     *
     * @return array<Account> The created accounts
     */
    public function initializeForCompany(CompanyId $companyId): array;
}
