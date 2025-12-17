<?php

declare(strict_types=1);

namespace Domain\Approval\Service;

use Domain\Approval\Entity\Approval;
use Domain\Approval\ValueObject\ApprovalReason;
use Domain\Approval\ValueObject\ApprovalType;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;

/**
 * Service interface for requesting approvals.
 */
interface ApprovalRequestServiceInterface
{
    /**
     * Create approval request.
     */
    public function requestApproval(
        CompanyId $companyId,
        ApprovalType $type,
        string $entityType,
        string $entityId,
        ApprovalReason $reason,
        UserId $requestedBy,
        int $amountCents = 0
    ): Approval;

    /**
     * Check if entity requires approval.
     *
     * @param array<string, mixed> $context
     */
    public function requiresApproval(
        string $entityType,
        string $entityId,
        array $context
    ): ApprovalRequirement;
}
