<?php

namespace App\Policies;

use App\Enums\LoadStatus;
use App\Models\Load;
use App\Models\User;

final class LoadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('loads.view');
    }

    public function view(User $user, Load $load): bool
    {
        return $this->canSee($user, $load);
    }

    public function create(User $user): bool
    {
        return $user->can('loads.create');
    }

    public function update(User $user, Load $load): bool
    {
        return $user->can('loads.edit') && $this->canSee($user, $load);
    }

    public function cancel(User $user, Load $load): bool
    {
        return $user->can('loads.cancel') && $this->canSee($user, $load) && $load->isCancellable();
    }

    /**
     * The workflow (booked → pickup → loaded → delivered → paid) has three rights:
     * `loads.load` for the in-transit steps, `loads.paid` and `loads.cancel`. Cancellation
     * reuses loads.cancel. The target status rides along as an argument, so the
     * route middleware can't decide this and the controller authorizes instead.
     */
    public function advanceStatus(User $user, Load $load, LoadStatus $to): bool
    {
        // Only the right and the ownership; whether the step itself is legal is
        // LoadStatusService's call, so a wrong order gives the form a readable
        // error instead of a bare 403.
        return $user->can(match ($to) {
            LoadStatus::Booked => 'loads.edit',
            LoadStatus::Pickup, LoadStatus::Loaded, LoadStatus::Delivered => 'loads.load',
            LoadStatus::Paid => 'loads.paid',
            LoadStatus::Cancelled => 'loads.cancel',
        }) && $this->canSee($user, $load);
    }

    private function canSee(User $user, Load $load): bool
    {
        return $user->can('loads.view')
            && (! $user->isScopedToOwnRecords() || $load->dispatcher_id === $user->id);
    }
}
