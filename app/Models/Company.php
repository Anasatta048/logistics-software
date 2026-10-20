<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\PaymentCurrency;
use App\Enums\PaymentFrequency;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The trucking company that owns the truck and the MC number. Every driver
 * belongs to one and every load is billed to one (FLOW_UPDATE_PLAN Section 3).
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Driver> $drivers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Load> $loads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CompanyPayment> $payments
 */
class Company extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'mc_number',
        'address',
        'contact_person',
        'phone',
        'email',
        'default_commission_percentage',
        'payment_frequency',
        'payment_currency',
        'advance_balance',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'default_commission_percentage' => 'decimal:2',
            'payment_frequency' => PaymentFrequency::class,
            'payment_currency' => PaymentCurrency::class,
            'advance_balance' => 'decimal:2',
            'status' => CompanyStatus::class,
        ];
    }

    /** @return HasMany<Driver, $this> */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /** @return HasMany<Load, $this> */
    public function loads(): HasMany
    {
        return $this->hasMany(Load::class);
    }

    /** @return HasMany<CompanyPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(CompanyPayment::class);
    }

    /**
     * A dispatcher only sees the companies allocated to them; everyone else sees all.
     *
     * @param  Builder<Company>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        if ($user?->isScopedToOwnRecords()) {
            $query->whereIn('companies.id', $user->companies()->select('companies.id'));
        }
    }

    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }
}
