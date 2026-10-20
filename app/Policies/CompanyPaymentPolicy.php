<?php

namespace App\Policies;

use App\Models\CompanyPayment;
use App\Models\User;

final class CompanyPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company-payments.view');
    }

    public function view(User $user, CompanyPayment $payment): bool
    {
        return $user->can('company-payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('company-payments.create');
    }

    public function update(User $user, CompanyPayment $payment): bool
    {
        return $user->can('company-payments.edit');
    }

    /**
     * Converting moves money into an account, so it needs the same right as
     * recording the payment, and only a payment that arrived and is not yet
     * converted can be.
     */
    public function convert(User $user, CompanyPayment $payment): bool
    {
        return $user->can('company-payments.create')
            && ! $payment->isConverted()
            && (float) $payment->amount_received > 0;
    }

    /**
     * Cancelling a load whose commission was already received needs to book an
     * adjustment line against money that has physically moved, so it is an
     * approval ability rather than an ordinary edit.
     */
    public function approve(User $user, CompanyPayment $payment): bool
    {
        return $user->can('company-payments.approve');
    }

    /**
     * No delete: a receipt that has been converted is an accounting record, and
     * one that has not is better corrected by editing its loads. Section 5.4's
     * adjustment line is the reversal path.
     */
}
