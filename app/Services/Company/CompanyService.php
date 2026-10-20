<?php

namespace App\Services\Company;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class CompanyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }

    /**
     * Soft delete: companies stay referenced by historical loads and payments
     * (FLOW_UPDATE_PLAN Section 8 denormalises company_id onto the load).
     */
    public function delete(Company $company): void
    {
        $company->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Company>
     */
    public function paginate(array $filters = [], int $perPage = 500, ?\App\Models\User $viewer = null): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $status = ($filters['status'] ?? null) !== null && $filters['status'] !== ''
            ? CompanyStatus::tryFrom((string) $filters['status'])
            : null;

        return Company::query()
            ->visibleTo($viewer)
            ->withCount('drivers')
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$term}%")
                ->orWhere('mc_number', 'like', "%{$term}%")
                ->orWhere('contact_person', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Lookup for the Load form's company picker: name or MC number.
     *
     * @return Collection<int, Company>
     */
    public function search(?string $term, int $limit = 15, ?\App\Models\User $viewer = null): Collection
    {
        $term = trim((string) $term);

        return Company::query()
            ->visibleTo($viewer)
            ->where('status', CompanyStatus::Active)
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$term}%")
                ->orWhere('mc_number', 'like', "%{$term}%")))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'mc_number', 'default_commission_percentage']);
    }

    /**
     * Active companies for any module's picker (driver form, load form filter,
     * settlement form).
     *
     * @return Collection<int, Company>
     */
    public function active(?\App\Models\User $viewer = null): Collection
    {
        return Company::query()
            ->visibleTo($viewer)
            ->where('status', CompanyStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'mc_number', 'default_commission_percentage', 'payment_frequency', 'payment_currency']);
    }
}
