<?php

declare(strict_types=1);

namespace Application\Handler\Admin;

use Domain\Identity\Entity\User;
use Domain\Identity\Repository\UserRepositoryInterface;
use Domain\Identity\ValueObject\Role;
use Domain\Shared\ValueObject\Email;
use Infrastructure\Service\TotpService;
use InvalidArgumentException;

final class SetupAdminHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TotpService $totpService
    ) {
    }

    public function handle(string $username, string $email, string $password, string $secret, string $code): array
    {
        // 1. Double check initialization state (Race condition protection)
        if ($this->userRepository->hasAnyAdmin()) {
            throw new InvalidArgumentException('System already initialized');
        }

        // 2. Verify OTP Code
        if (!$this->totpService->verify($secret, $code)) {
            throw new InvalidArgumentException('Invalid OTP code');
        }

        // 3. Create Admin User
        $user = User::register(
            username: $username,
            email: Email::fromString($email),
            password: $password,
            role: Role::ADMIN,
            companyId: null
        );

        // 4. Enable OTP
        $user->enableOtp($secret);
        
        // 5. Genesis Approval
        $user->approveGenesis();
        
        $this->userRepository->save($user);

        return [
            'id' => $user->id()->toString(),
            'username' => $user->username(),
            'recovery_codes' => [] // TODO: Generate recovery codes
        ];
    }
}
