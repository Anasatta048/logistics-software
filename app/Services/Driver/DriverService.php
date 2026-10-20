<?php

namespace App\Services\Driver;

use App\Enums\DriverStatus;
use App\Enums\LoadStatus;
use App\Models\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class DriverService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Driver
    {
        return $this->syncStatus(Driver::create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Driver $driver, array $data): Driver
    {
        $driver->update($data);

        // 'Active' arrives as available; the real status follows the loads.
        return $this->syncStatus($driver);
    }

    /**
     * Soft delete: drivers stay referenced by historical loads (SRD 12).
     */
    public function delete(Driver $driver): void
    {
        $driver->delete();
    }

    /**
     * @return LengthAwarePaginator<int, Driver>
     */
    public function paginate(array $filters = [], int $perPage = 500, ?\App\Models\User $viewer = null): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $status = ($filters['status'] ?? null) !== null && $filters['status'] !== ''
            ? DriverStatus::tryFrom((string) $filters['status'])
            : null;

        return Driver::query()
            ->visibleTo($viewer)
            ->with('company:id,name,mc_number')
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$term}%")
                ->orWhere('truck_number', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$term}%"))))
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Lookup endpoint for the Load form's driver picker. Carries company_id so
     * the form can read the company off the chosen driver without a second
     * request (FLOW_UPDATE_PLAN Section 4.1).
     *
     * ponytail: no status filter here — the caller decides. Add
     * `?status=available` handling when the Load form needs only free trucks.
     *
     * @return Collection<int, Driver>
     */
    public function search(?string $term, int $limit = 10, ?int $companyId = null, bool $includeInactive = false, ?\App\Models\User $viewer = null): Collection
    {
        $term = trim((string) $term);

        return Driver::query()
            ->with('company:id,name,mc_number,default_commission_percentage')
            // A dispatcher only gets the drivers of their allocated companies.
            ->when($viewer?->isScopedToOwnRecords(), fn ($query) => $query->whereIn('company_id', $viewer->companies()->select('companies.id')))
            // Inactive drivers are never offered in a picker.
            ->when(! $includeInactive, fn ($query) => $query->where('status', '!=', DriverStatus::Inactive))
            ->when($companyId, fn ($query, $id) => $query->where('company_id', $id))
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$term}%")
                ->orWhere('truck_number', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                // Company name too: a dispatcher remembers the carrier, not the
                // driver's name, and the company is what the load inherits.
                ->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$term}%"))))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * A driver can hold several loads, so their status is the furthest point any
     * open load has reached: Loaded, else Pickup, else Booked, else Available once
     * everything is delivered, paid or cancelled. Inactive is a manual decision
     * and is never overwritten.
     */
    public function syncStatus(Driver $driver): Driver
    {
        $driver->refresh();

        if ($driver->status === DriverStatus::Inactive) {
            return $driver;
        }

        $open = $driver->loads()
            ->whereIn('status', [LoadStatus::Booked, LoadStatus::Pickup, LoadStatus::Loaded])
            ->pluck('status')
            ->map(fn (LoadStatus $status): string => $status->value);

        $status = match (true) {
            $open->contains(LoadStatus::Loaded->value) => DriverStatus::Loaded,
            $open->contains(LoadStatus::Pickup->value) => DriverStatus::Pickup,
            $open->contains(LoadStatus::Booked->value) => DriverStatus::Booked,
            default => DriverStatus::Available,
        };

        return $driver->status === $status ? $driver : $this->setStatus($driver, $status);
    }

    private function setStatus(Driver $driver, DriverStatus $status): Driver
    {
        $driver->update(['status' => $status]);

        return $driver;
    }
}
