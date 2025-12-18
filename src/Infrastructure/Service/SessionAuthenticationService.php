<?php

declare(strict_types=1);

namespace Infrastructure\Service;

use DateTimeImmutable;
use Domain\Identity\Entity\Session;
use Domain\Identity\Entity\User;
use Domain\Identity\Service\AuthenticationServiceInterface;
use Domain\Identity\Service\PasswordServiceInterface;
use Domain\Identity\ValueObject\SessionId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Exception\AuthenticationException;
use Infrastructure\Persistence\Mysql\Connection\PdoConnectionFactory;
use Domain\Identity\Repository\UserRepositoryInterface;
use PDO;
use RuntimeException;

/**
 * Session-based authentication service implementation.
 */
final class SessionAuthenticationService implements AuthenticationServiceInterface
{
    private const TOKEN_LENGTH = 64;
    private const DEFAULT_SESSION_HOURS = 24;

    private int $sessionHours = self::DEFAULT_SESSION_HOURS; // Default value for session hours

    public function __construct(
        private readonly PdoConnectionFactory $connectionFactory,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordServiceInterface $passwordService
    ) {
    }

    /**
     * Authenticate user with credentials.
     *
     * @throws AuthenticationException
     */
    public function authenticate(string $username, string $password, string $ipAddress, string $userAgent): Session
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user === null || !$user->isActive()) {
             throw new AuthenticationException('Invalid credentials');
        }

        if (!$this->passwordService->verify($password, $user->passwordHash())) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Create new session
        return $this->createSession($user, $ipAddress, $userAgent);
    }

    /**
     * Create a new session for a user.
     */
    private function createSession(User $user, string $ipAddress, string $userAgent): Session
    {
        $sessionId = SessionId::generate();
        $token = $this->generateToken();
        $expiresAt = new DateTimeImmutable("+{$this->sessionHours} hours");
        $createdAt = new DateTimeImmutable();

        $connection = $this->connectionFactory->createConnection();
        $sql = <<<SQL
            INSERT INTO sessions (id, user_id, token, ip_address, user_agent, expires_at, created_at)
            VALUES (:id, :user_id, :token, :ip_address, :user_agent, :expires_at, :created_at)
        SQL;

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'id' => $sessionId->toString(),
            'user_id' => $user->id()->toString(),
            'token' => hash('sha256', $token), // Store hashed token
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ]);

        return new Session(
            $sessionId,
            $user->id(),
            $ipAddress,
            $userAgent,
            true, // isActive
            $expiresAt,
            $createdAt, // lastActivityAt
            $createdAt,
            $token // Return unhashed token to client
        );
    }

    /**
     * Validate a session token and return the User if valid.
     */
    public function validateSession(string $sessionToken): ?User
    {
        $hashedToken = hash('sha256', $sessionToken);

        $connection = $this->connectionFactory->createConnection();
        $sql = <<<SQL
            SELECT user_id FROM sessions 
            WHERE token = :token AND expires_at > NOW()
        SQL;

        $stmt = $connection->prepare($sql);
        $stmt->execute(['token' => $hashedToken]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->userRepository->findById(UserId::fromString($row['user_id']));
    }

    /**
     * Invalidate a session (logout).
     */
    public function terminateSession(string $sessionToken): void
    {
        $hashedToken = hash('sha256', $sessionToken);
        $connection = $this->connectionFactory->createConnection();
        $stmt = $connection->prepare('DELETE FROM sessions WHERE token = :token');
        $stmt->execute(['token' => $hashedToken]);
    }

    public function terminateAllUserSessions(User $user): void
    {
        $connection = $this->connectionFactory->createConnection();
        $stmt = $connection->prepare('DELETE FROM sessions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user->id()->toString()]);
    }

    /**
     * Clean up expired sessions.
     */
    public function cleanupExpiredSessions(): int
    {
        $sql = 'DELETE FROM sessions WHERE expires_at < NOW()';
        $connection = $this->connectionFactory->createConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Extend a session's expiration.
     */
    public function extendSession(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        $newExpiresAt = new DateTimeImmutable("+{$this->sessionHours} hours");

        $sql = <<<SQL
            UPDATE sessions SET expires_at = :expires_at 
            WHERE token = :token AND expires_at > NOW()
        SQL;

        $connection = $this->connectionFactory->createConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'token' => $hashedToken,
            'expires_at' => $newExpiresAt->format('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Generate a cryptographically secure random token.
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
    }
}
