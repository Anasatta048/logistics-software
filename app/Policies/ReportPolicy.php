<?php

namespace App\Policies;

use App\Models\User;

/**
 * Report has no Eloquent model, so these are registered as named Gates
 * (`reports.{ability}`, see AppServiceProvider). Every report needs the base
 * `reports.view` right plus the right of the module it reports on, so a role
 * never sees figures from a module it cannot open. A dispatcher is further
 * limited to their own records by ReportService.
 */
final class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }

    /* Loads */

    public function loads(User $user): bool
    {
        return $user->can('reports.view') && $user->can('loads.view');
    }

    public function drivers(User $user): bool
    {
        return $user->can('reports.view') && $user->can('drivers.view');
    }

    public function brokers(User $user): bool
    {
        return $user->can('reports.view') && $user->can('brokers.view') && ! $user->isScopedToOwnRecords();
    }

    /** Who still owes us, by broker and age. Not for dispatchers, who only see their own loads. */
    public function brokerAging(User $user): bool
    {
        return $user->can('reports.view') && $user->can('brokers.view') && ! $user->isScopedToOwnRecords();
    }

    /** Settlement statements show the company's commission, so they follow that right. */
    public function settlements(User $user): bool
    {
        return $user->can('reports.view') && $user->can('view-company-commission') && ! $user->isScopedToOwnRecords();
    }

    /* Invoices & payments */

    public function invoices(User $user): bool
    {
        return $user->can('reports.view') && $user->can('invoices.view');
    }

    public function receivables(User $user): bool
    {
        return $user->can('reports.view') && $user->can('invoices.view');
    }

    public function receipts(User $user): bool
    {
        return $user->can('reports.view') && $user->can('company-payments.view');
    }

    public function conversions(User $user): bool
    {
        return $user->can('reports.view') && $user->can('company-payments.view');
    }

    /* Accounts */

    public function expenses(User $user): bool
    {
        return $user->can('reports.view') && $user->can('expenses.view');
    }

    /** Where money came from and went, per account. */
    public function fundsFlow(User $user): bool
    {
        return $user->can('reports.view') && $user->can('accounts.view');
    }

    public function accountStatement(User $user): bool
    {
        return $user->can('reports.view') && $user->can('accounts.view');
    }

    /* Commissions & summary */

    public function dispatcherCommissions(User $user): bool
    {
        return $user->can('reports.view') && $user->can('commissions.view');
    }

    public function profitAndLoss(User $user): bool
    {
        return $user->can('reports.view') && ! $user->isScopedToOwnRecords();
    }
}
