<?php

namespace App\Policies;

use App\Models\Broker;
use App\Models\User;

final class BrokerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('brokers.view');
    }

    public function view(User $user, Broker $broker): bool
    {
        return $user->can('brokers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('brokers.create');
    }

    public function update(User $user, Broker $broker): bool
    {
        return $user->can('brokers.edit');
    }

    public function delete(User $user, Broker $broker): bool
    {
        return $user->can('brokers.delete');
    }
}
