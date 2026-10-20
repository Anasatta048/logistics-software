<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

final class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.view') && $this->allocated($user, $company->id);
    }

    /** A dispatcher only reaches the companies allocated to them. */
    private function allocated(User $user, int $companyId): bool
    {
        return ! $user->isScopedToOwnRecords() || $user->companies()->whereKey($companyId)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('companies.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.edit') && $this->allocated($user, $company->id);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('companies.delete') && $this->allocated($user, $company->id);
    }
}
