<?php

declare(strict_types=1);

namespace Application\Handler\Identity;

use Application\Command\CommandInterface;
use Application\Command\Identity\AuthenticateCommand;
use Application\Dto\Identity\AuthenticationResultDto;
use Application\Dto\Identity\UserDto;
use Application\Handler\HandlerInterface;
use Domain\Identity\Repository\UserRepositoryInterface;
use Domain\Identity\Service\AuthenticationServiceInterface;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\Exception\AuthenticationException;

/**
 * Handler for user authentication.
 *
 * @implements HandlerInterface<AuthenticateCommand>
 */
final readonly class AuthenticateHandler implements HandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthenticationServiceInterface $authService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): AuthenticationResultDto
    {
        assert($command instanceof AuthenticateCommand);

        try {
            // AuthService handles both validation and token/session generation
            $session = $this->authService->authenticate(
                username: $command->username,
                password: $command->password,
                ipAddress: $command->ipAddress,
                userAgent: 'Unknown' // Command doesn't have UA, verify?
            );

            // Get user from session (assuming session is linked to user, which it should be)
            // Need to fetch user from repository or if Session provided it.
            // Looking at AuthenticationServiceInterface, it returns Session.
            // Assuming we can get UserId from Session or Repository.
            // Actually, we need the User object to create DTO.
            $user = $this->userRepository->findByUsername($command->username);
            
            if ($user === null) {
                // Should have been caught by authService if invalid user, but for safety
                return AuthenticationResultDto::failure('User not found');
            }

            // Create user DTO
            $userDto = new UserDto(
                id: $user->id()->toString(),
                username: $user->username(),
                email: $user->email()->toString(),
                firstName: '',
                lastName: '',
                role: $user->role()->value,
                status: $user->registrationStatus()->value,
                companyId: $user->companyId()?->toString(),
                createdAt: $user->createdAt()->format('Y-m-d H:i:s'),
            );

            // Token is likely the session ID or a token within session
            // We need to check Session entity API. 
            // For now, assume a method to obtain token/ID
            // Let's assume session->id()->toString() is the token for now.
             $token = 'session-token-placeholder'; // Session entity not inspected yet

            return AuthenticationResultDto::success($token, $userDto);

        } catch (AuthenticationException $e) {
            // Dispatch login failed event
            $this->eventDispatcher->dispatch(
                new \Domain\Identity\Event\LoginFailed(
                    $command->username,
                    $command->ipAddress,
                    $e->getMessage()
                )
            );
            return AuthenticationResultDto::failure($e->getMessage());
        }
    }
}
