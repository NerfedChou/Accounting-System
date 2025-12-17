<?php

declare(strict_types=1);

namespace Domain\Identity\Service;

use Domain\Identity\Entity\User;
use Domain\Identity\ValueObject\Role;

/**
 * Port for authorization operations.
 * Implementation should be in Infrastructure layer.
 */
interface AuthorizationServiceInterface
{
    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(User $user, string $permission): bool;

    /**
     * Check if user has a specific role.
     */
    public function hasRole(User $user, Role $role): bool;

    /**
     * Check if user can access a specific resource.
     */
    public function canAccess(User $user, string $resource, string $action): bool;

    /**
     * Check if user can perform action on another user.
     */
    public function canManageUser(User $actor, User $target): bool;
}
