<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

final class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('drivers.view');
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->can('drivers.view') && $this->allocated($user, $driver);
    }

    /** A dispatcher only reaches the drivers of companies allocated to them. */
    private function allocated(User $user, Driver $driver): bool
    {
        return ! $user->isScopedToOwnRecords() || $user->companies()->whereKey($driver->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('drivers.create');
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->can('drivers.edit') && $this->allocated($user, $driver);
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->can('drivers.delete') && $this->allocated($user, $driver);
    }
}
