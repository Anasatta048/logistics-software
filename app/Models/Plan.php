<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'tagline', 'monthly_price', 'yearly_price', 'monthly_price_pkr', 'yearly_price_pkr', 'trial_days',
        'max_users', 'max_drivers', 'max_loads_per_month', 'features', 'modules',
        'is_featured', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'monthly_price_pkr' => 'decimal:2',
            'yearly_price_pkr' => 'decimal:2',
            'features' => 'array',
            'modules' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<TenantRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(TenantRequest::class);
    }

    /** @return HasMany<Tenant, $this> */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /** A plan with no module list (null) includes everything; an empty list includes none. */
    public function hasModule(string $module): bool
    {
        return $this->modules === null || in_array($module, $this->modules, true);
    }

    public function hasPkrPrice(): bool
    {
        return $this->monthly_price_pkr !== null && $this->yearly_price_pkr !== null;
    }

    /** What a workspace pays for one cycle: PKR when the plan has local prices, otherwise USD. */
    public function charge(string $cycle): array
    {
        $yearly = $cycle === 'yearly';

        return $this->hasPkrPrice()
            ? ['currency' => 'PKR', 'amount' => (float) ($yearly ? $this->yearly_price_pkr : $this->monthly_price_pkr)]
            : ['currency' => 'USD', 'amount' => (float) ($yearly ? $this->yearly_price : $this->monthly_price)];
    }

    /** Share saved by paying yearly instead of 12 months, in whole percent. */
    public function yearlySavingPercent(): int
    {
        $monthly = (float) ($this->hasPkrPrice() ? $this->monthly_price_pkr : $this->monthly_price) * 12;
        $yearly = (float) ($this->hasPkrPrice() ? $this->yearly_price_pkr : $this->yearly_price);

        return $monthly > 0 && $yearly < $monthly ? (int) round((1 - $yearly / $monthly) * 100) : 0;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** "Unlimited" for a null cap, otherwise the number. */
    public static function limit(?int $value): string
    {
        return $value === null ? 'Unlimited' : number_format($value);
    }
}
