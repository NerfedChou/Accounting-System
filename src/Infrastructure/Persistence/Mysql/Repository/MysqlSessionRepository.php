<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Mysql\Repository;

use Domain\Identity\Entity\Session;
use Domain\Identity\Repository\SessionRepositoryInterface;
use Infrastructure\Persistence\Mysql\Hydrator\UserHydrator;

class MysqlSessionRepository extends AbstractMysqlRepository implements SessionRepositoryInterface
{
    public function save(Session $session): void
    {
        // For sessions, we usually insert or update based on ID
        $sql = "INSERT INTO sessions (
            id, 
            user_id, 
            token, 
            ip_address, 
            user_agent, 
            created_at, 
            expires_at
        ) VALUES (
            :id, 
            :user_id, 
            :token, 
            :ip_address, 
            :user_agent, 
            :created_at, 
            :expires_at
        ) ON DUPLICATE KEY UPDATE
            token = VALUES(token),
            ip_address = VALUES(ip_address),
            user_agent = VALUES(user_agent),
            expires_at = VALUES(expires_at)";

        $params = [
            'id' => $session->id()->toString(),
            'user_id' => $session->userId()->toString(),
            'token' => $session->token(),
            'ip_address' => $session->ipAddress(),
            'user_agent' => $session->userAgent(),
            'created_at' => $session->createdAt()->format('Y-m-d H:i:s'),
            'expires_at' => $session->expiresAt()->format('Y-m-d H:i:s'),
        ];

        $this->connection->prepare($sql)->execute($params);
    }

    public function findByToken(string $token): ?Session
    {
        $sql = "SELECT * FROM sessions WHERE token = :token AND expires_at > NOW() LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['token' => $token]);
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateSession($row);
    }

    public function findById(\Domain\Identity\ValueObject\SessionId $sessionId): ?Session
    {
        $sql = "SELECT * FROM sessions WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['id' => $sessionId->toString()]);
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateSession($row);
    }

    public function findActiveByUserId(\Domain\Identity\ValueObject\UserId $userId): array
    {
        $sql = "SELECT * FROM sessions WHERE user_id = :user_id AND expires_at > NOW()";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrateSession($row);
        }
        
        return $results;
    }

    public function delete(string $id): void
    {
        $sql = "DELETE FROM sessions WHERE id = :id";
        $this->connection->prepare($sql)->execute(['id' => $id]);
    }

    public function deleteExpired(): int
    {
        $sql = "DELETE FROM sessions WHERE expires_at < NOW()";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateSession(array $row): Session
    {
        // Fix: Correct argument order and types for Session constructor
        // Params: sessionId, userId, ipAddress, userAgent, isActive, expiresAt, lastActivityAt, createdAt, token
        
        // Assuming 'is_active' column doesn't exist in schema (based on plan), we infer true if found.
        $isActive = true; 
        
        return new Session(
            \Domain\Identity\ValueObject\SessionId::fromString($row['id']),
            \Domain\Identity\ValueObject\UserId::fromString($row['user_id']),
            $row['ip_address'],
            $row['user_agent'],
            $isActive,
            new \DateTimeImmutable($row['expires_at']),
            null, // lastActivityAt (not persisted in simple schema)
            new \DateTimeImmutable($row['created_at']),
            $row['token']
        );
    }
}
