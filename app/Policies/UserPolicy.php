<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management is Admin-only. That is enforced by the permission matrix in
 * RolePermissionSeeder (`users.*` is granted to `admin` and nobody else), not
 * by hardcoding a role check here.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * A dispatcher's commission ledger: whoever sees commissions, but a
     * dispatcher only ever their own.
     */
    public function viewCommissionLedger(User $user, User $model): bool
    {
        return $user->can('commissions.view')
            && (! $user->isScopedToOwnRecords() || $user->is($model));
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.edit');
    }
}
