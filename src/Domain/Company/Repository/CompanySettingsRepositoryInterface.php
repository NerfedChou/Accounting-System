<?php

declare(strict_types=1);

namespace Domain\Company\Repository;

use Domain\Company\Entity\CompanySettings;
use Domain\Company\ValueObject\CompanyId;

interface CompanySettingsRepositoryInterface
{
    public function save(CompanySettings $settings): void;

    public function findByCompanyId(CompanyId $companyId): ?CompanySettings;
}
