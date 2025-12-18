<?php

declare(strict_types=1);

namespace Application\Handler\Identity;

use Application\Command\CommandInterface;
use Application\Command\Identity\RegisterUserCommand;
use Application\Dto\Identity\UserDto;
use Application\Handler\HandlerInterface;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\Entity\User;
use Domain\Identity\Repository\UserRepositoryInterface;
use Domain\Identity\ValueObject\Role;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\ValueObject\Email;

/**
 * Handler for user registration.
 *
 * @implements HandlerInterface<RegisterUserCommand>
 */
final readonly class RegisterUserHandler implements HandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): UserDto
    {
        assert($command instanceof RegisterUserCommand);

        // Check username uniqueness (BR-IAM-001)
        if ($this->userRepository->existsByUsername($command->username)) {
            throw new \DomainException('Username already exists');
        }

        // Check email uniqueness
        if ($this->userRepository->existsByEmail(Email::fromString($command->email))) {
            throw new \DomainException('Email already exists');
        }

        // Create user (using actual domain signature)
        $user = User::register(
            username: $command->username,
            email: Email::fromString($command->email),
            password: $command->password,
            role: Role::TENANT,
            companyId: $command->companyId ? CompanyId::fromString($command->companyId) : null,
        );

        // Persist
        $this->userRepository->save($user);

        // Dispatch events
        foreach ($user->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $this->toDto($user);
    }

    private function toDto(User $user): UserDto
    {
        return new UserDto(
            id: $user->id()->toString(),
            username: $user->username(),
            email: $user->email()->toString(),
            firstName: '',  // Not in domain entity
            lastName: '',   // Not in domain entity
            role: $user->role()->value,
            status: $user->registrationStatus()->value,
            companyId: $user->companyId()?->toString(),
            createdAt: $user->createdAt()->format('Y-m-d H:i:s'),
        );
    }
}
