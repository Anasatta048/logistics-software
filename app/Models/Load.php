<?php

namespace App\Models;

use App\Enums\LoadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Company|null $company
 * @property-read Broker|null $broker
 * @property-read Driver|null $driver
 * @property-read User|null $dispatcher
 * @property-read User|null $cancelledBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LoadStatusHistory> $statusHistory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LoadAttachment> $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CompanyPayment> $payments
 * @property float $outstanding_commission Set on the fly by CompanyPaymentService
 *   when previewing a receipt; not a column.
 */
class Load extends Model
{
    /** @use HasFactory<\Database\Factories\LoadFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reference_number',
        'company_id',
        'broker_id',
        'broker_contact_person',
        'broker_phone',
        'broker_email',
        'broker_contact_person',
        'broker_phone',
        'broker_email',
        'broker_contact_person',
        'broker_phone',
        'broker_email',
        'driver_id',
        'driver_name',
        'equipment_type',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'total_miles',
        'weight_lbs',
        'length_ft',
        'width_ft',
        'height_ft',
        'pickup_at',
        'pickup_timezone',
        'dropoff_at',
        'dropoff_timezone',
        'rate',
        'rate_per_mile',
        'company_commission_percentage',
        'company_commission_amount',
        'dispatcher_id',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'equipment_type' => 'string',
            'status' => LoadStatus::class,
            'origin_lat' => 'float',
            'origin_lng' => 'float',
            'destination_lat' => 'float',
            'destination_lng' => 'float',
            'total_miles' => 'decimal:1',
            'weight_lbs' => 'decimal:1',
            'length_ft' => 'decimal:2',
            'width_ft' => 'decimal:2',
            'height_ft' => 'decimal:2',
            'rate' => 'decimal:2',
            'rate_per_mile' => 'decimal:2',
            'company_commission_percentage' => 'decimal:2',
            'company_commission_amount' => 'decimal:2',

            'pickup_at' => 'datetime',
            'dropoff_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Denormalised off the driver on purpose — a driver can move companies
     * later, but a closed load must keep billing whoever actually ran it.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** @return BelongsTo<User, $this> */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LoadStatusHistory::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LoadAttachment::class);
    }

    /** @return BelongsToMany<CompanyPayment, $this> */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(CompanyPayment::class, 'company_payment_loads');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<InvoiceLine, $this> */
    public function invoiceLine(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InvoiceLine::class);
    }

    public function isCancellable(): bool
    {
        return $this->status->canTransitionTo(LoadStatus::Cancelled);
    }

    /**
     * The saved driver's record if there is one, otherwise the name that was
     * typed in. The driver's truck number is not part of it — a typed name has
     * no record to look it up on.
     */
    public function driverLabel(): ?string
    {
        return $this->driver->name ?? $this->driver_name;
    }
}
