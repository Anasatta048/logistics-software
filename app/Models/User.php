<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use BelongsToTenant;

    use HasFactory;
    use HasRoles {
        hasRole as private spatieHasRole;
    }
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'commission_type',
        'commission_percentage',
        'commission_fixed_amount',
        'nav_position',
        'theme_mode',
        'notification_preferences',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'commission_type' => CommissionType::class,
            'commission_percentage' => 'decimal:2',
            'commission_fixed_amount' => 'decimal:2',
            'notification_preferences' => 'array',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * The default shape for `notification_preferences` — every event opted
     * in. Applied at creation time (see `booted()`) so every new user, seeded
     * or registered, starts with an explicit set of toggles rather than a
     * null column silently meaning "everything on".
     *
     * @return array<string, bool>
     */
    public static function defaultNotificationPreferences(): array
    {
        return [
            'load_booked' => true,
            'load_status_changed' => true,
            'load_cancelled' => true,
            'load_paid' => true,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->notification_preferences ??= self::defaultNotificationPreferences();
        });
    }

    /**
     * The platform owner is not bound to a tenant's role set: while inside a
     * workspace (impersonating) they hold every role, so role checks written
     * for tenant users pass for them without a special case at each call site.
     *
     * @param  mixed  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->is_super_admin || $this->spatieHasRole($roles, $guard);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Whoever lacks the `loads.view-all` right only ever sees their own rows
     * (the standard dispatcher role). It is a right, not a role name, so a
     * custom role is scoped, or not, exactly like a standard one.
     *
     * Used by the Load and Commission query scopes *and* their policies, so a
     * list and a single record can never disagree.
     */
    public function isScopedToOwnRecords(): bool
    {
        return ! $this->is_super_admin && ! $this->can('loads.view-all');
    }

    /**
     * Companies a dispatcher is allocated to; none means none for them.
     *
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    /**
     * Whose company allocation limits what `$viewer` may pick: a dispatcher is
     * limited by their own, an admin who chose a dispatcher on the load form by
     * that dispatcher's, and an admin who did not choose one is not limited.
     */
    public static function allocationOwner(self $viewer, mixed $dispatcherId): ?self
    {
        if ($viewer->isScopedToOwnRecords()) {
            return $viewer;
        }

        $chosen = filled($dispatcherId) ? self::find($dispatcherId) : null;

        return $chosen?->isScopedToOwnRecords() ? $chosen : null;
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * How this person is paid, in one string — the users list and the
     * commission form would otherwise each re-implement the switch.
     */
    public function commissionLabel(): string
    {
        return $this->commission_type === CommissionType::Fixed
            ? '$'.number_format((float) $this->commission_fixed_amount, 2).' fixed'
            : rtrim(rtrim((string) ($this->commission_percentage ?? 0), '0'), '.').'%';
    }

    /**
     * Where this user lands after logging in (SRD 2.2 / plan Phase 1).
     *
     * ponytail: the first branch whose route actually exists wins, so this
     * resolves itself as Phases 3 and 7 register `loads.index` /
     * `accounts.index` without another edit here.
     */
    public function homeRouteName(): string
    {
        $byRole = match (true) {
            $this->is_super_admin && ! app(\App\Support\Tenancy\TenantContext::class)->has() => 'super-admin.dashboard',
            $this->is_super_admin => 'dashboard',
            $this->hasRole('dispatcher') => 'loads.index',
            $this->hasRole('accountant') => 'accounts.index',
            default => 'dashboard',
        };

        return Route::has($byRole) ? $byRole : 'dashboard';
    }
}
