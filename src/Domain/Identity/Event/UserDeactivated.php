<?php

declare(strict_types=1);

namespace Domain\Identity\Event;

use DateTimeImmutable;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\DomainEvent;

final class UserDeactivated implements DomainEvent
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private readonly UserId $userId,
        private readonly UserId $deactivatedBy,
        private readonly string $reason
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function eventName(): string
    {
        return 'user.deactivated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'deactivated_by' => $this->deactivatedBy->toString(),
            'reason' => $this->reason,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function deactivatedBy(): UserId
    {
        return $this->deactivatedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
