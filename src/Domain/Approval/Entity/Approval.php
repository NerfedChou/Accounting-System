<?php

declare(strict_types=1);

namespace Domain\Approval\Entity;

use DateTimeImmutable;
use Domain\Approval\Event\ApprovalCancelled;
use Domain\Approval\Event\ApprovalExpired;
use Domain\Approval\Event\ApprovalGranted;
use Domain\Approval\Event\ApprovalRejected;
use Domain\Approval\Event\ApprovalRequested;
use Domain\Approval\ValueObject\ApprovalId;
use Domain\Approval\ValueObject\ApprovalReason;
use Domain\Approval\ValueObject\ApprovalStatus;
use Domain\Approval\ValueObject\ApprovalType;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\DomainEvent;
use Domain\Shared\Exception\BusinessRuleException;

final class Approval
{
    private const MIN_REJECTION_REASON_LENGTH = 10;

    /** @var array<DomainEvent> */
    private array $domainEvents = [];

    private ?UserId $reviewedBy = null;
    private ?DateTimeImmutable $reviewedAt = null;
    private ?string $reviewNotes = null;

    private function __construct(
        private readonly ApprovalId $id,
        private readonly CompanyId $companyId,
        private readonly ApprovalType $approvalType,
        private readonly string $entityType,
        private readonly string $entityId,
        private readonly ApprovalReason $reason,
        private readonly UserId $requestedBy,
        private readonly DateTimeImmutable $requestedAt,
        private readonly int $amountCents,
        private readonly int $priority,
        private readonly ?DateTimeImmutable $expiresAt,
        private ApprovalStatus $status,
    ) {
    }

    public static function request(
        CompanyId $companyId,
        ApprovalType $approvalType,
        string $entityType,
        string $entityId,
        ApprovalReason $reason,
        UserId $requestedBy,
        int $amountCents = 0,
        ?int $priority = null,
        ?int $expirationHours = null,
    ): self {
        $id = ApprovalId::generate();
        $now = new DateTimeImmutable();

        $actualPriority = $priority ?? $approvalType->getDefaultPriority();
        $actualExpirationHours = $expirationHours ?? $approvalType->getDefaultExpirationHours();
        $expiresAt = $now->modify(sprintf('+%d hours', $actualExpirationHours));

        $approval = new self(
            id: $id,
            companyId: $companyId,
            approvalType: $approvalType,
            entityType: $entityType,
            entityId: $entityId,
            reason: $reason,
            requestedBy: $requestedBy,
            requestedAt: $now,
            amountCents: $amountCents,
            priority: $actualPriority,
            expiresAt: $expiresAt,
            status: ApprovalStatus::PENDING,
        );

        $approval->recordEvent(new ApprovalRequested(
            approvalId: $id->toString(),
            companyId: $companyId->toString(),
            approvalType: $approvalType->value,
            entityType: $entityType,
            entityId: $entityId,
            reason: $reason->toArray(),
            requestedBy: $requestedBy->toString(),
            priority: $actualPriority,
            expiresAt: $expiresAt,
            occurredAt: $now,
        ));

        return $approval;
    }

    /**
     * BR-AW-001, BR-AW-002: Only admin can approve, cannot self-approve.
     */
    public function approve(UserId $approver, ?string $notes = null): void
    {
        $this->ensureCanTransition(ApprovalStatus::APPROVED);
        $this->ensureNotSelfApproval($approver);

        $this->status = ApprovalStatus::APPROVED;
        $this->reviewedBy = $approver;
        $this->reviewedAt = new DateTimeImmutable();
        $this->reviewNotes = $notes;

        $this->recordEvent(new ApprovalGranted(
            approvalId: $this->id->toString(),
            entityType: $this->entityType,
            entityId: $this->entityId,
            approvedBy: $approver->toString(),
            notes: $notes,
            occurredAt: $this->reviewedAt,
        ));
    }

    /**
     * BR-AW-004: Rejection requires reason with minimum length.
     */
    public function reject(UserId $reviewer, string $reason): void
    {
        $this->ensureCanTransition(ApprovalStatus::REJECTED);
        $this->ensureNotSelfApproval($reviewer);
        $this->validateRejectionReason($reason);

        $this->status = ApprovalStatus::REJECTED;
        $this->reviewedBy = $reviewer;
        $this->reviewedAt = new DateTimeImmutable();
        $this->reviewNotes = $reason;

        $this->recordEvent(new ApprovalRejected(
            approvalId: $this->id->toString(),
            entityType: $this->entityType,
            entityId: $this->entityId,
            rejectedBy: $reviewer->toString(),
            reason: $reason,
            occurredAt: $this->reviewedAt,
        ));
    }

    public function cancel(UserId $canceller, string $reason): void
    {
        $this->ensureCanTransition(ApprovalStatus::CANCELLED);

        // Only the requester can cancel
        if (!$this->requestedBy->equals($canceller)) {
            throw new BusinessRuleException('Only the requester can cancel their approval request');
        }

        $this->status = ApprovalStatus::CANCELLED;
        $this->reviewedBy = $canceller;
        $this->reviewedAt = new DateTimeImmutable();
        $this->reviewNotes = $reason;

        $this->recordEvent(new ApprovalCancelled(
            approvalId: $this->id->toString(),
            entityType: $this->entityType,
            entityId: $this->entityId,
            cancelledBy: $canceller->toString(),
            reason: $reason,
            occurredAt: $this->reviewedAt,
        ));
    }

    public function expire(): void
    {
        $this->ensureCanTransition(ApprovalStatus::EXPIRED);

        $this->status = ApprovalStatus::EXPIRED;
        $this->reviewedAt = new DateTimeImmutable();

        $this->recordEvent(new ApprovalExpired(
            approvalId: $this->id->toString(),
            entityType: $this->entityType,
            entityId: $this->entityId,
            expiresAt: $this->expiresAt,
            occurredAt: $this->reviewedAt,
        ));
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->status->isPending() && new DateTimeImmutable() > $this->expiresAt;
    }

    // Getters

    public function id(): ApprovalId
    {
        return $this->id;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function approvalType(): ApprovalType
    {
        return $this->approvalType;
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function reason(): ApprovalReason
    {
        return $this->reason;
    }

    public function requestedBy(): UserId
    {
        return $this->requestedBy;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function amountCents(): int
    {
        return $this->amountCents;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function reviewedBy(): ?UserId
    {
        return $this->reviewedBy;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function reviewNotes(): ?string
    {
        return $this->reviewNotes;
    }

    // Private helpers

    private function ensureCanTransition(ApprovalStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new BusinessRuleException(
                sprintf(
                    'Cannot transition from %s to %s',
                    $this->status->value,
                    $newStatus->value
                )
            );
        }
    }

    private function ensureNotSelfApproval(UserId $reviewer): void
    {
        if ($this->requestedBy->equals($reviewer)) {
            throw new BusinessRuleException('Cannot approve or reject your own request');
        }
    }

    private function validateRejectionReason(string $reason): void
    {
        if (strlen($reason) < self::MIN_REJECTION_REASON_LENGTH) {
            throw new BusinessRuleException(
                sprintf('Rejection reason must be at least %d characters', self::MIN_REJECTION_REASON_LENGTH)
            );
        }
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
