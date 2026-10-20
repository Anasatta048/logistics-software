<?php

namespace App\Services\Broker;

use App\Enums\BrokerStatus;
use App\Models\Broker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class BrokerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Broker
    {
        return Broker::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Broker $broker, array $data): Broker
    {
        $broker->update($data);

        return $broker;
    }

    /**
     * Soft delete: brokers stay referenced by historical loads (SRD 12).
     */
    public function delete(Broker $broker): void
    {
        $broker->delete();
    }

    /**
     * @return LengthAwarePaginator<int, Broker>
     */
    public function paginate(array $filters = [], int $perPage = 500): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $status = ($filters['status'] ?? null) !== null && $filters['status'] !== ''
            ? BrokerStatus::tryFrom((string) $filters['status'])
            : null;

        return Broker::query()
            // Inactive brokers are never offered in a picker.
            ->where('status', BrokerStatus::Active)
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('company_name', 'like', "%{$term}%")
                ->orWhere('broker_mc_number', 'like', "%{$term}%")))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Lookup endpoint for the Load form's broker picker.
     *
     * @return Collection<int, Broker>
     */
    public function search(?string $term, int $limit = 10): Collection
    {
        $term = trim((string) $term);

        // Only the load form's picker uses this, and a blocked broker must not be bookable.
        return Broker::query()
            ->where('status', BrokerStatus::Active)
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('company_name', 'like', "%{$term}%")
                ->orWhere('broker_mc_number', 'like', "%{$term}%")))
            ->orderBy('company_name')
            ->limit($limit)
            ->get();
    }

    public function markAsInactive(Broker $broker): Broker
    {
        return $this->setStatus($broker, BrokerStatus::Inactive);
    }

    public function markAsActive(Broker $broker): Broker
    {
        return $this->setStatus($broker, BrokerStatus::Active);
    }

    private function setStatus(Broker $broker, BrokerStatus $status): Broker
    {
        $broker->update(['status' => $status]);

        return $broker;
    }
}
