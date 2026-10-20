<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * One customer company on the platform. Deliberately NOT tenant-scoped: it is
 * the thing the scope is keyed on.
 */
class Tenant extends Model
{
    /** Days a lapsed paid subscription keeps working before access is cut. */
    public const GRACE_DAYS = 3;

    protected $fillable = [
        'name', 'slug', 'plan_id', 'status', 'billing_cycle', 'trial_ends_at',
        'subscription_ends_at', 'suspended_at', 'suspended_reason',
        'contact_name', 'email', 'phone', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $forget = static fn (self $tenant) => Cache::forget(self::cacheKey($tenant->id));

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Every request of a tenant user needs its tenant row; one cached lookup
     * (Redis in production) instead of a query per request.
     */
    public static function cached(int $id): ?self
    {
        return Cache::remember(self::cacheKey($id), 300, fn (): ?self => self::query()->find($id));
    }

    private static function cacheKey(int $id): string
    {
        return "tenant.{$id}";
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<TenantRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(TenantRequest::class);
    }

    /**
     * Whether the workspace may be used right now. Computed from the dates, not
     * only the stored status, so access ends on time even if the nightly sweep
     * has not run yet.
     */
    public function isUsable(): bool
    {
        return match ($this->status) {
            TenantStatus::Trial => $this->trial_ends_at === null || $this->trial_ends_at->isFuture(),
            TenantStatus::Active => $this->subscription_ends_at === null
                || $this->subscription_ends_at->copy()->addDays(self::GRACE_DAYS)->isFuture(),
            default => false,
        };
    }

    /** Why the workspace is locked, in words for the customer. */
    public function lockedReason(): string
    {
        return match (true) {
            $this->status === TenantStatus::Suspended => $this->suspended_reason ?: 'This workspace has been suspended.',
            $this->status === TenantStatus::Cancelled => 'This workspace has been cancelled.',
            $this->status === TenantStatus::Trial => 'Your free trial has ended.',
            default => 'Your subscription has expired.',
        };
    }

    public function endsAt(): ?\Carbon\CarbonInterface
    {
        return $this->status === TenantStatus::Trial ? $this->trial_ends_at : $this->subscription_ends_at;
    }

    public function monthlyRevenue(): float
    {
        if ($this->status !== TenantStatus::Active || ! $this->plan) {
            return 0.0;
        }

        return $this->billing_cycle === 'yearly'
            ? round((float) $this->plan->yearly_price / 12, 2)
            : (float) $this->plan->monthly_price;
    }
}
