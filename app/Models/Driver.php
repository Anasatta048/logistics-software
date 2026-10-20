<?php

namespace App\Models;

use App\Enums\DriverStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasCustomFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A driver always belongs to a company — the company owns the truck and the MC
 * number, and pays its own driver outside this system (FLOW_UPDATE_PLAN
 * Section 3.2).
 *
 * @property-read Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Load> $loads
 */
class Driver extends Model
{
    use BelongsToTenant;
    use HasCustomFields;

    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasFactory;

    use SoftDeletes;

    protected string $customEntity = 'driver';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'custom',
        'company_id',
        'name',
        'truck_number',
        'trailer_number',
        'truck_length_ft',
        'truck_width_ft',
        'truck_height_ft',
        'truck_max_weight_lbs',
        'equipment_type',
        'phone',
        'email',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'equipment_type' => 'string',
            'status' => DriverStatus::class,
            'truck_length_ft' => 'decimal:2',
            'truck_width_ft' => 'decimal:2',
            'truck_height_ft' => 'decimal:2',
            'truck_max_weight_lbs' => 'decimal:1',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    /**
     * A dispatcher only sees drivers of the companies allocated to them; everyone else sees all.
     *
     * @param  Builder<Driver>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        if ($user?->isScopedToOwnRecords()) {
            $query->whereIn('drivers.company_id', $user->companies()->select('companies.id'));
        }
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<Load, $this> */
    public function loads(): HasMany
    {
        return $this->hasMany(Load::class);
    }

    /**
     * Loads this driver is still carrying (booked, picked up or loaded) — what
     * the Load form shows so a dispatcher sees the driver's schedule. Delivered,
     * paid and cancelled loads are history and left out.
     *
     * @return list<array<string, mixed>>
     */
    public function openLoadsSummary(?int $exceptLoadId = null): array
    {
        return $this->loads()
            ->whereIn('status', [\App\Enums\LoadStatus::Booked, \App\Enums\LoadStatus::Pickup, \App\Enums\LoadStatus::Loaded])
            ->when($exceptLoadId, fn ($query, $id) => $query->whereKeyNot($id))
            ->orderBy('pickup_at')
            ->get(['id', 'reference_number', 'status', 'origin_address', 'destination_address', 'pickup_at'])
            ->map(fn (Load $load): array => [
                'id' => $load->id,
                'reference' => $load->reference_number,
                'status' => $load->status->value,
                'status_label' => $load->status->label(),
                'origin' => $load->origin_address,
                'destination' => $load->destination_address,
                'pickup' => \App\Support\Timezones::toUs($load->pickup_at)?->format('M j, H:i').' '.\App\Support\Timezones::usLabel(),
            ])
            ->all();
    }

    public function isAvailable(): bool
    {
        return $this->status === DriverStatus::Available;
    }
}
