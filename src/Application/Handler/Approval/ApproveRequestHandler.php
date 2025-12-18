<?php

declare(strict_types=1);

namespace Application\Handler\Approval;

use Application\Command\Approval\ApproveRequestCommand;
use Application\Command\CommandInterface;
use Application\Dto\Approval\ApprovalDto;
use Application\Handler\HandlerInterface;
use Domain\Approval\Entity\Approval;
use Domain\Approval\Repository\ApprovalRepositoryInterface;
use Domain\Approval\ValueObject\ApprovalId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\Exception\EntityNotFoundException;

/**
 * Handler for approving a request.
 *
 * @implements HandlerInterface<ApproveRequestCommand>
 */
final readonly class ApproveRequestHandler implements HandlerInterface
{
    public function __construct(
        private ApprovalRepositoryInterface $approvalRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): ApprovalDto
    {
        assert($command instanceof ApproveRequestCommand);

        $approvalId = ApprovalId::fromString($command->approvalId);

        // Find approval
        $approval = $this->approvalRepository->findById($approvalId);

        if ($approval === null) {
            throw new EntityNotFoundException("Approval not found: {$command->approvalId}");
        }

        // Approve (domain handles business rules)
        $approval->approve(
            UserId::fromString($command->approverId),
            $command->comment
        );

        // Persist
        $this->approvalRepository->save($approval);

        // Dispatch events
        foreach ($approval->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $this->toDto($approval);
    }

    private function toDto(Approval $approval): ApprovalDto
    {
        return new ApprovalDto(
            id: $approval->id()->toString(),
            entityType: $approval->entityType(),
            entityId: $approval->entityId(),
            approvalType: $approval->approvalType()->value,
            status: $approval->status()->value,
            requestedBy: $approval->requestedBy()->toString(),
            processedBy: $approval->reviewedBy()?->toString(),
            reason: $approval->reviewNotes(),
            requestedAt: $approval->requestedAt()->format('Y-m-d H:i:s'),
            processedAt: $approval->reviewedAt()?->format('Y-m-d H:i:s'),
            expiresAt: $approval->expiresAt()?->format('Y-m-d H:i:s'),
        );
    }
}
