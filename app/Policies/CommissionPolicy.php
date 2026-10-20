<?php

namespace App\Policies;

use App\Models\Commission;
use App\Models\User;

final class CommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commissions.view');
    }

    public function view(User $user, Commission $commission): bool
    {
        return $user->can('commissions.view');
    }

    /**
     * No create/edit/delete abilities on purpose: the amount is computed by the
     * paid event and reversed by a cancellation, never hand-edited, so a
     * commission row always agrees with the load behind it. Paying a dispatcher
     * is the one write, and it needs approval because money leaves the business.
     * It is a class-level ability: a payout settles the dispatcher's ledger, not
     * one commission row.
     */
    public function pay(User $user): bool
    {
        return $user->can('commissions.pay');
    }
}
