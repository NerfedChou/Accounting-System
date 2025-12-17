<?php

declare(strict_types=1);

namespace Domain\Company\Service;

use Domain\Company\ValueObject\CompanyId;

/**
 * Port for company activation operations.
 * Implementation should be in Application layer.
 */
interface CompanyActivationServiceInterface
{
    /**
     * Activate a pending company after setup is complete.
     */
    public function activate(CompanyId $companyId): void;

    /**
     * Check if company meets activation requirements.
     *
     * Requirements:
     * - Must have at least one admin user
     * - Must have chart of accounts initialized
     * - Must have valid tax identifier
     * - Must have complete address
     */
    public function canActivate(CompanyId $companyId): ActivationCheckResult;
}
