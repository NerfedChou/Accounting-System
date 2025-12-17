<?php

declare(strict_types=1);

namespace Domain\Identity\Repository;

use Domain\Identity\Entity\Session;
use Domain\Identity\ValueObject\SessionId;
use Domain\Identity\ValueObject\UserId;

interface SessionRepositoryInterface
{
    public function save(Session $session): void;

    public function findById(SessionId $sessionId): ?Session;

    /**
     * @return array<Session>
     */
    public function findActiveByUserId(UserId $userId): array;

    public function deleteExpired(): int;
}
