<?php

declare(strict_types=1);

namespace Domain\Approval\Service;

use Domain\Approval\ValueObject\ApprovalId;
use Domain\Identity\ValueObject\UserId;

/**
 * Service interface for reviewing approvals.
 */
interface ApprovalReviewServiceInterface
{
    /**
     * Approve request.
     */
    public function approve(ApprovalId $approvalId, UserId $approver, ?string $notes = null): void;

    /**
     * Reject request.
     */
    public function reject(ApprovalId $approvalId, UserId $reviewer, string $reason): void;

    /**
     * Cancel request (by requester).
     */
    public function cancel(ApprovalId $approvalId, UserId $requester, string $reason): void;
}
