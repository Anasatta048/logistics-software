<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

final class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('expenses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('expenses.edit');
    }

    /**
     * Removing an expense is a money decision, not a typo fix: it is the same
     * permission that lets someone create one, so an accountant can put a
     * mistaken entry right without an admin in the loop.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('expenses.delete');
    }
}
