<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Per-tenant application settings, one JSON value per (tenant, key). Read
 * through Setting::read(): the tenant's whole settings table is loaded once per
 * request, and a missing table, key or tenant quietly falls back to the
 * caller's default instead of breaking the page.
 */
class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        try {
            $settings = app(TenantContext::class)->settings();
        } catch (\Throwable) {
            return $default;
        }

        return $settings[$key] ?? $default;
    }

    public static function write(string $key, mixed $value): void
    {
        $context = app(TenantContext::class);

        if (! $context->has()) {
            throw new LogicException('Settings are per tenant; no tenant is active.');
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        $context->forgetSettings();
    }
}
