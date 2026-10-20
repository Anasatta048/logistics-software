<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'tagline', 'monthly_price', 'yearly_price', 'trial_days',
        'max_users', 'max_drivers', 'max_loads_per_month', 'features',
        'is_featured', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<TenantRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(TenantRequest::class);
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
