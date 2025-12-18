<?php

declare(strict_types=1);

namespace Api\Middleware;

use Api\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rate limiting middleware to prevent API abuse.
 * Uses in-memory storage (for demo). Production should use Redis/Memcached.
 */
final class RateLimitMiddleware
{
    /** @var array<string, array{count: int, window_start: int}> */
    private static array $storage = [];

    public function __construct(
        private readonly int $maxRequests = 100,
        private readonly int $windowSeconds = 60,
    ) {
    }

    /**
     * Process the request and enforce rate limits.
     */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $clientId = $this->getClientIdentifier($request);
        $now = time();

        // Initialize or reset window
        if (!isset(self::$storage[$clientId]) || 
            self::$storage[$clientId]['window_start'] + $this->windowSeconds < $now
        ) {
            self::$storage[$clientId] = [
                'count' => 0,
                'window_start' => $now,
            ];
        }

        // Check rate limit
        if (self::$storage[$clientId]['count'] >= $this->maxRequests) {
            $retryAfter = self::$storage[$clientId]['window_start'] + $this->windowSeconds - $now;
            
            return $this->createRateLimitResponse($retryAfter);
        }

        // Increment counter
        self::$storage[$clientId]['count']++;

        // Add rate limit headers to response
        $response = $next($request);
        
        return $this->addRateLimitHeaders($response, $clientId);
    }

    /**
     * Get unique identifier for rate limiting (IP + User ID if available).
     */
    private function getClientIdentifier(ServerRequestInterface $request): string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $userId = $request->getAttribute('user_id');

        return $userId ? "{$ip}:{$userId}" : $ip;
    }

    /**
     * Create 429 Too Many Requests response.
     */
    private function createRateLimitResponse(int $retryAfter): ResponseInterface
    {
        return JsonResponse::error(
            'Rate limit exceeded. Please slow down.',
            429,
            ['retry_after' => $retryAfter]
        )->withHeader('Retry-After', (string) $retryAfter);
    }

    /**
     * Add rate limit headers to response.
     */
    private function addRateLimitHeaders(ResponseInterface $response, string $clientId): ResponseInterface
    {
        $remaining = $this->maxRequests - self::$storage[$clientId]['count'];
        $resetTime = self::$storage[$clientId]['window_start'] + $this->windowSeconds;

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $remaining))
            ->withHeader('X-RateLimit-Reset', (string) $resetTime);
    }

    /**
     * Clear rate limit storage (for testing).
     */
    public static function clearStorage(): void
    {
        self::$storage = [];
    }
}
