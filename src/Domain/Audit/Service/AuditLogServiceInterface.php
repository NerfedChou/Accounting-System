<?php

declare(strict_types=1);

namespace Domain\Audit\Service;

use Domain\Audit\Entity\ActivityLog;
use Domain\Audit\ValueObject\ActivityType;
use Domain\Audit\ValueObject\Actor;
use Domain\Audit\ValueObject\ChangeRecord;
use Domain\Audit\ValueObject\RequestContext;
use Domain\Shared\Event\DomainEvent;

/**
 * Service interface for creating audit log entries.
 */
interface AuditLogServiceInterface
{
    /**
     * Log an activity.
     *
     * @param array<string, mixed> $previousState
     * @param array<string, mixed> $newState
     */
    public function log(
        ActivityType $type,
        string $entityType,
        string $entityId,
        string $action,
        array $previousState,
        array $newState,
        Actor $actor,
        RequestContext $context
    ): ActivityLog;

    /**
     * Log from a domain event.
     */
    public function logFromEvent(DomainEvent $event, Actor $actor, RequestContext $context): ActivityLog;

    /**
     * Calculate field-level changes between states.
     *
     * @param array<string, mixed> $previousState
     * @param array<string, mixed> $newState
     * @return array<ChangeRecord>
     */
    public function calculateChanges(array $previousState, array $newState): array;
}
