<?php

declare(strict_types=1);

namespace Domain\ChartOfAccounts\Service;

use Domain\ChartOfAccounts\ValueObject\AccountCode;
use Domain\ChartOfAccounts\ValueObject\AccountType;
use Domain\Company\ValueObject\CompanyId;

/**
 * Service interface for generating account codes.
 * Implementation should be in Application layer.
 */
interface AccountCodeGeneratorInterface
{
    /**
     * Generate next available account code for given type.
     */
    public function generateNextCode(CompanyId $companyId, AccountType $type): AccountCode;

    /**
     * Generate sub-account code under parent.
     */
    public function generateSubAccountCode(CompanyId $companyId, AccountCode $parentCode): AccountCode;
}
