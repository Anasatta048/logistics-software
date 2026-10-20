<?php

namespace App\Services\Tenancy;

use App\Models\Driver;
use App\Models\Load;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

/**
 * Enforces the plan caps (users, drivers, loads per month) for the current
 * tenant. Wired to the models' `creating` events (see register()) so every way
 * of adding a row, including the driver created on the spot while booking a
 * load, is covered by one rule. A null cap means unlimited.
 */
final class TenantLimits
{
    public const RESOURCES = [
        'users' => ['max_users', 'users'],
        'drivers' => ['max_drivers', 'drivers'],
        'loads' => ['max_loads_per_month', 'loads this month'],
    ];

    public function __construct(private readonly TenantContext $context)
    {
    }

    public static function register(): void
    {
        User::creating(fn (User $user) => $user->is_super_admin ?: app(self::class)->ensure('users'));
        Driver::creating(fn () => app(self::class)->ensure('drivers'));
        Load::creating(fn () => app(self::class)->ensure('loads'));
    }

    /** @throws ValidationException when the plan's cap is already reached */
    public function ensure(string $resource): void
    {
        $limit = $this->limit($resource);

        if ($limit === null || $this->used($resource) < $limit) {
            return;
        }

        $label = self::RESOURCES[$resource][1];

        throw ValidationException::withMessages([
            'plan' => "Your plan allows {$limit} {$label}. Upgrade your plan to add more.",
        ]);
    }

    /** The cap for this tenant, or null when unlimited / no plan / no tenant. */
    public function limit(string $resource): ?int
    {
        $tenant = $this->context->tenant();

        return $tenant?->plan?->{self::RESOURCES[$resource][0]};
    }

    public function used(string $resource): int
    {
        return match ($resource) {
            'users' => User::query()->count(),
            'drivers' => Driver::query()->count(),
            default => Load::query()->where('created_at', '>=', now()->startOfMonth())->count(),
        };
    }
}
