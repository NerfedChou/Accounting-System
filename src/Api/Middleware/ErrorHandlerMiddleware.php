<?php

declare(strict_types=1);

namespace Api\Middleware;

use Api\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Error handler middleware for catching exceptions and returning JSON error responses.
 */
final class ErrorHandlerMiddleware
{
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Process the request and catch any exceptions.
     */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Handle exception and return appropriate JSON response.
     */
    private function handleException(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $statusCode = $this->mapExceptionToStatusCode($e);
        $message = $this->getErrorMessage($e);

        $payload = [
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
        ];

        // Add debug info in development
        if ($this->debug) {
            $payload['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 10),
            ];
        }

        // Add request ID if present
        $requestId = $request->getAttribute('request_id');
        if ($requestId !== null) {
            $payload['request_id'] = $requestId;
        }

        return new JsonResponse($payload, $statusCode);
    }

    /**
     * Map exception type to HTTP status code.
     */
    private function mapExceptionToStatusCode(Throwable $e): int
    {
        $class = get_class($e);

        // Domain exceptions
        return match (true) {
            str_contains($class, 'NotFound') => 404,
            str_contains($class, 'InvalidArgument') => 400,
            str_contains($class, 'Validation') => 422,
            str_contains($class, 'Unauthorized') => 401,
            str_contains($class, 'Forbidden') => 403,
            str_contains($class, 'Conflict') => 409,
            str_contains($class, 'DomainException') => 400,
            default => 500,
        };
    }

    /**
     * Get user-facing error message.
     */
    private function getErrorMessage(Throwable $e): string
    {
        // In production, hide internal error details
        if (!$this->debug && $this->mapExceptionToStatusCode($e) >= 500) {
            return 'An internal error occurred. Please try again later.';
        }

        return $e->getMessage();
    }
}
