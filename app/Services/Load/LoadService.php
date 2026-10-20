<?php

namespace App\Services\Load;

use App\Enums\AttachmentType;
use App\Enums\BrokerStatus;
use App\Enums\DriverStatus;
use App\Enums\LoadStatus;
use App\Enums\UserStatus;
use App\Events\LoadBooked;
use App\Models\Broker;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Load;
use App\Models\LoadAttachment;
use App\Models\User;
use App\Services\Driver\DriverService;
use App\Services\Support\AttachmentOptimizer;
use App\Support\Timezones;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class LoadService
{
    public function __construct(
        private DriverService $driverService,
        private LoadStatusService $statusService,
    ) {
    }

    /**
     * A new load is always booked; status only moves through LoadStatusService.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Load
    {
        $load = DB::transaction(function () use ($data, $actor): Load {
            $data = $this->resolveParties($data);

            $load = new Load($this->withComputed($data, $actor));
            $load->status = LoadStatus::Booked;
            $load->save();

            // ponytail: the primary key is the only collision-free counter we
            // have, so the reference is stamped in a second write rather than
            // adding a sequence table.
            if (blank($load->reference_number)) {
                $load->update(['reference_number' => $this->referenceNumberFor($load->id)]);
            }

            $this->statusService->record($load, null, LoadStatus::Booked, $actor);

            return $load;
        });

        $this->syncDriver($load->driver_id);

        LoadBooked::dispatch($load, $actor);

        return $load;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Load $load, array $data, User $actor): Load
    {
        $previousDriverId = $load->driver_id;

        DB::transaction(function () use ($load, $data, $actor): void {
            $data = $this->resolveParties($data);

            // A blank number on edit keeps the one the load already has.
            if (blank($data['reference_number'] ?? null)) {
                unset($data['reference_number']);
            }

            $load->update($this->withComputed($data, $actor, $load));
        });

        $this->syncDriver($previousDriverId);
        $this->syncDriver($load->driver_id);

        return $load;
    }

    /**
     * Commission rate per mile; zero miles means nothing to divide by yet.
     */
    public function calculateRatePerMile(float $rate, float $miles): float
    {
        return $miles > 0 ? round($rate / $miles, 2) : 0.0;
    }

    /**
     * @return LengthAwarePaginator<int, Load>
     */
    public function paginate(array $filters, User $viewer, int $perPage = 500): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $status = ($filters['status'] ?? '') !== '' ? LoadStatus::tryFrom((string) $filters['status']) : null;

        return Load::query()
            ->with(['broker:id,company_name', 'company:id,name,mc_number', 'driver:id,name,truck_number', 'dispatcher:id,name'])
            ->when($term !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('reference_number', 'like', "%{$term}%")
                ->orWhereHas('company', fn ($company) => $company->where('mc_number', 'like', "%{$term}%"))
                ->orWhere('origin_address', 'like', "%{$term}%")
                ->orWhere('destination_address', 'like', "%{$term}%")))
            ->when($status, fn ($query) => $query->where('status', $status))
            // Scoped in the query, not just the policy, so a dispatcher can
            // never read someone else's loads by hitting the index directly.
            ->when($viewer->isScopedToOwnRecords(), fn ($query) => $query->where('dispatcher_id', $viewer->id))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function withHistory(Load $load): Load
    {
        return $load->load([
            'company',
            'broker',
            'driver',
            'dispatcher',
            'cancelledBy',
            'statusHistory.changedBy',
            'attachments.uploader',
            'payments',
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  list<string>  $types  one AttachmentType value per file, same order
     */
    public function attachDocuments(Load $load, array $files, array $types, User $actor): void
    {
        foreach (array_values($files) as $i => $file) {
            $this->attachDocument($load, AttachmentType::from($types[$i] ?? AttachmentType::Other->value), $file, $actor);
        }
    }

    public function attachDocument(Load $load, AttachmentType $type, UploadedFile $file, User $actor): LoadAttachment
    {
        return LoadAttachment::create([
            'load_id' => $load->id,
            'type' => $type,
            'file_path' => app(AttachmentOptimizer::class)->store($file, 'load-attachments'),
            'uploaded_by' => $actor->id,
        ]);
    }

    /**
     * The broker, company and driver the form should show as already chosen:
     * what was just submitted (after a validation error) or, failing that, what
     * the load being edited carries. The pickers search on demand, so only these
     * three rows are ever sent to the page, never the whole lists.
     *
     * @param  array<string, mixed>  $old
     * @return array{broker: ?Broker, company: ?Company, driver: ?Driver}
     */
    public function selection(?Load $load, array $old = []): array
    {
        $pick = static fn (string $key, string $model, ?Model $current) => array_key_exists($key, $old)
            ? (filled($old[$key]) ? $model::query()->find($old[$key]) : null)
            : $current;

        return [
            'broker' => $pick('broker_id', Broker::class, $load?->broker),
            'company' => $pick('company_id', Company::class, $load?->company),
            'driver' => $pick('driver_id', Driver::class, $load?->driver),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function dispatchers(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'dispatcher'))
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name']);
    }


    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withComputed(array $data, User $actor, ?Load $existing = null): array
    {
        // Typed into the form in the chosen US zone (default: the entry zone); stored as UTC.
        foreach (['pickup', 'dropoff'] as $stage) {
            $zone = Timezones::isUs($data["{$stage}_timezone"] ?? null) ? $data["{$stage}_timezone"] : Timezones::us();
            $data["{$stage}_timezone"] = $zone;

            if (isset($data["{$stage}_at"]) && is_string($data["{$stage}_at"])) {
                $data["{$stage}_at"] = Timezones::parseIn($data["{$stage}_at"], $zone);
            }
        }

        $data['rate_per_mile'] = $this->calculateRatePerMile(
            (float) ($data['rate'] ?? 0),
            (float) ($data['total_miles'] ?? 0),
        );

        $data['company_id'] = (int) $data['company_id'];
        $data['company_commission_percentage'] = $this->commissionPercentageFor($data['company_id'], $existing);
        $data['company_commission_amount'] = round(
            (float) ($data['rate'] ?? 0) * $data['company_commission_percentage'] / 100,
            2,
        );

        // ponytail: a dispatcher's loads always count toward their own
        // commission, whatever the payload claims (SRD 2.1).
        $data['dispatcher_id'] = $actor->isScopedToOwnRecords() || empty($data['dispatcher_id'])
            ? $actor->id
            : $data['dispatcher_id'];

        return $data;
    }

    /**
     * The company's default rate, frozen onto the load when it is booked. There
     * is no per-load input: editing a load keeps what it already carries while it
     * stays with the same company, and switching company takes the new
     * company's default.
     */
    private function commissionPercentageFor(?int $companyId, ?Load $existing): float
    {
        if ($existing !== null && (int) $existing->company_id === $companyId) {
            return (float) $existing->company_commission_percentage;
        }

        if ($companyId === null) {
            return 0.0;
        }

        $company = Company::query()->find($companyId);

        return $company instanceof Company ? (float) $company->default_commission_percentage : 0.0;
    }

    /**
     * The Load form lets a dispatcher key in a broker or driver that is not on
     * file yet. They are created here, in the load's own transaction, so a load
     * that fails to save never leaves an orphan broker or driver behind.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveParties(array $data): array
    {
        $broker = $data['new_broker'] ?? null;
        $driver = $data['new_driver'] ?? null;
        unset($data['new_broker'], $data['new_driver']);

        if (empty($data['broker_id']) && ! empty($broker['broker_mc_number'])) {
            $data['broker_id'] = Broker::create([
                'broker_mc_number' => $broker['broker_mc_number'],
                'company_name' => $broker['company_name'],
                'status' => BrokerStatus::Active,
            ])->id;
        }

        // An existing driver's truck decides the equipment type, whatever was posted.
        if (! empty($data['driver_id']) && ($equipment = Driver::query()->whereKey($data['driver_id'])->value('equipment_type'))) {
            $data['equipment_type'] = $equipment;
        }

        if (empty($data['driver_id']) && ! empty($driver['name'])) {
            $data['driver_id'] = Driver::create([
                'company_id' => $data['company_id'],
                'name' => $driver['name'],
                'phone' => $driver['phone'],
                'truck_number' => $driver['truck_number'],
                'equipment_type' => $data['equipment_type'],
                'status' => DriverStatus::Available,
            ])->id;
        }

        return $data;
    }

    private function referenceNumberFor(int $id): string
    {
        $base = 'DEL-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $reference = $base;

        // A hand-typed number may already sit on the next automatic one.
        for ($suffix = 2; Load::withTrashed()->where('reference_number', $reference)->exists(); $suffix++) {
            $reference = "{$base}-{$suffix}";
        }

        return $reference;
    }

    /**
     * Re-derive the driver's status from the loads they hold now. A driver may
     * carry several loads (advance bookings included), so the only question is
     * whether any is still open, never "is this their one load".
     */
    private function syncDriver(?int $driverId): void
    {
        $driver = $driverId === null ? null : Driver::find($driverId);

        if ($driver) {
            $this->driverService->syncStatus($driver);
        }
    }
}
