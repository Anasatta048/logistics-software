<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Role and permission editing is Admin-only, same deal as UserPolicy: the grant
 * lives in RolePermissionSeeder's matrix (`roles.*` only admin), not here.
 */
final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete');
    }
}
