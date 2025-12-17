<?php

declare(strict_types=1);

namespace Domain\Approval\Service;

/**
 * Service interface for processing expired approvals.
 */
interface ApprovalExpirationServiceInterface
{
    /**
     * Process expired approvals (scheduler).
     *
     * @return int Number of approvals expired
     */
    public function processExpiredApprovals(): int;
}
