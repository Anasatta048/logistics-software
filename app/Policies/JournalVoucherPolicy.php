<?php

namespace App\Policies;

use App\Models\JournalVoucher;
use App\Models\User;

final class JournalVoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('journal-vouchers.view');
    }

    public function view(User $user, JournalVoucher $jv): bool
    {
        return $user->can('journal-vouchers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('journal-vouchers.create');
    }

    public function update(User $user, JournalVoucher $jv): bool
    {
        return $user->can('journal-vouchers.edit') && $jv->isPending();
    }

    /**
     * There is no delete route: a raised voucher is voided, not removed, so the
     * ledger keeps the reason it was written off (SRD 6.3).
     */
    public function void(User $user, JournalVoucher $jv): bool
    {
        return $user->can('journal-vouchers.void') && $jv->isPending();
    }

    /**
     * Approving is the money-moving action, so it is gated by its own
     * permission rather than piggy-backing on `edit` (SRD 2.2).
     */
    public function approve(User $user, JournalVoucher $jv): bool
    {
        return $user->can('journal-vouchers.approve') && $jv->isPending();
    }
}
