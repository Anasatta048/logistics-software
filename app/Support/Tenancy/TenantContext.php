<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Support\InvoiceProfile;
use App\Support\Timezones;
use Closure;
use Spatie\Permission\PermissionRegistrar;

/**
 * "Which tenant is this request/job working for?" One instance per request
 * (bound with scoped() so a long-lived worker never carries it over).
 *
 * The BelongsToTenant scope reads id(); Spatie's team id follows it so a tenant
 * only ever resolves its own roles.
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    /** @var array<string, mixed>|null this tenant's settings, loaded once per request */
    private ?array $settings = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->settings = null;

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);
        Timezones::flush();
        \App\Services\Support\CustomFields::flush();

        if ($tenant) {
            InvoiceProfile::apply();
        }
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function forget(): void
    {
        $this->set(null);
    }

    /**
     * Run a callback as a tenant (seeders, console commands, queued work) and
     * restore whatever was active before.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->set($tenant);

        try {
            return $callback();
        } finally {
            $this->set($previous);
        }
    }

    /**
     * Setting memo: the whole settings table of a tenant in one query, then
     * served from memory for the rest of the request.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return $this->settings ??= $this->tenant
            ? \App\Models\Setting::query()->pluck('value', 'key')->all()
            : [];
    }

    public function forgetSettings(): void
    {
        $this->settings = null;
    }
}
