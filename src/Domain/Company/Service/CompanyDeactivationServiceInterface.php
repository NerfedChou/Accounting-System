<?php

declare(strict_types=1);

namespace Domain\Company\Service;

use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;

/**
 * Port for company deactivation operations.
 * Implementation should be in Application layer.
 */
interface CompanyDeactivationServiceInterface
{
    /**
     * Deactivate company and cascade to users.
     */
    public function deactivate(
        CompanyId $companyId,
        string $reason,
        UserId $deactivatedBy
    ): void;

    /**
     * Check if company can be safely deactivated.
     *
     * Checks:
     * - No pending transactions
     * - No active sessions
     */
    public function canDeactivate(CompanyId $companyId): DeactivationCheckResult;
}
