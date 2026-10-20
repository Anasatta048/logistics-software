<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

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
        // The shared standard roles are the platform's; a tenant only edits its own.
        return $this->ownedBy($role) && $user->can('roles.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->ownedBy($role) && $user->can('roles.delete');
    }

    /** Only a tenant's own custom role is its to change; the shared ones and other tenants' are not. */
    private function ownedBy(Role $role): bool
    {
        $tenantId = app(\App\Support\Tenancy\TenantContext::class)->id();

        return $tenantId !== null && $role->team_id === $tenantId;
    }
}
