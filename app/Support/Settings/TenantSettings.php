<?php

namespace App\Support\Settings;

use App\Enums\PaymentMethod;
use App\Enums\TaxType;
use App\Models\Setting;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Typed access to the per-tenant settings declared in config/tenant-settings.php.
 * One Setting row per group (`settings.{group}`); every read after the first in
 * a request is served from the TenantContext memo, so asking for a value on
 * every page costs no extra query.
 *
 * Field types: text, textarea, email, number, select, multiselect, color,
 * toggle (on/off) and image (an uploaded file, stored per tenant).
 */
final class TenantSettings
{
    /** @return array<string, array<string, mixed>> */
    public static function groups(): array
    {
        return config('tenant-settings.groups', []);
    }

    /** @return array<string, array<string, mixed>> */
    public static function fields(string $group): array
    {
        return self::groups()[$group]['fields'] ?? [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        [$group, $field] = explode('.', $key, 2);

        $value = self::all($group)[$field] ?? null;

        return $value ?? $default;
    }

    public static function on(string $key): bool
    {
        return (bool) self::get($key, false);
    }

    /**
     * Every field of a group with saved values over defaults.
     *
     * @return array<string, mixed>
     */
    public static function all(string $group): array
    {
        $saved = Setting::read("settings.{$group}", []);
        $saved = is_array($saved) ? $saved : [];
        $values = [];

        foreach (self::fields($group) as $name => $field) {
            $has = array_key_exists($name, $saved) && $saved[$name] !== null && ($saved[$name] !== '' || $field['type'] === 'text');
            $values[$name] = $has ? $saved[$name] : self::defaultFor($group, $name, $field);
        }

        return $values;
    }

    /** Public URL of an uploaded image setting, or null. */
    public static function url(string $key): ?string
    {
        $path = self::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** @param array<string, mixed> $field */
    private static function defaultFor(string $group, string $name, array $field): mixed
    {
        if ($group === 'business') {
            $tenant = app(TenantContext::class)->tenant();

            return match ($name) {
                'name' => $tenant->name ?? config('app.name'),
                'email' => $tenant->email ?? null,
                'phone' => $tenant->phone ?? null,
                default => $field['default'] ?? null,
            };
        }

        if ($field['type'] === 'multiselect') {
            return $field['default'] ?? array_keys(self::options($field['options']));
        }

        return $field['default'] ?? null;
    }

    /** @return array<string, string> */
    public static function options(string $name): array
    {
        return match ($name) {
            'tax_types' => collect(TaxType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'payment_methods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'commission_modes' => ['flat' => 'Flat: each dispatcher has their own %', 'tiered' => 'Slabs: % rises with the dispatcher\'s monthly margin'],
            'invoice_templates' => ['classic' => 'Classic (roomy)', 'compact' => 'Compact (smaller type, fits more per page)'],
            'date_formats' => ['M j, Y' => 'Oct 20, 2026', 'd M Y' => '20 Oct 2026', 'd/m/Y' => '20/10/2026', 'm/d/Y' => '10/20/2026', 'Y-m-d' => '2026-10-20'],
            'week_starts' => ['monday' => 'Monday', 'sunday' => 'Sunday', 'saturday' => 'Saturday'],
            'months' => collect(range(1, 12))->mapWithKeys(fn (int $m) => [(string) $m => date('F', mktime(0, 0, 0, $m, 1))])->all(),
            'chart_ranges' => ['7' => 'Last 7 days', '14' => 'Last 14 days', '30' => 'Last 30 days', '90' => 'Last 90 days'],
            'dashboard_widgets' => [
                'onboarding' => 'Get-set-up checklist',
                'alerts' => 'Alerts',
                'attention' => 'Needs attention',
                'revenue_chart' => 'Revenue and margin chart',
                'status_chart' => 'Loads by status',
                'money' => 'Money summary',
                'trend' => 'Income vs spending, expense breakdown',
                'plan' => 'Plan usage',
            ],
            'notify_events' => ['load_booked' => 'Load booked', 'load_status_changed' => 'Load status changed', 'load_cancelled' => 'Load cancelled', 'load_paid' => 'Load paid'],
            default => [],
        };
    }

    /**
     * Validation rules for a group's form, derived from the registry.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $group): array
    {
        $rules = [];

        foreach (self::fields($group) as $name => $field) {
            $r = [($field['required'] ?? false) ? 'required' : 'nullable'];

            $r = match ($field['type']) {
                'number' => [...$r, 'numeric', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 999999999)],
                'email' => [...$r, 'email', 'max:'.($field['max'] ?? 255)],
                'color' => [...$r, 'regex:/^#[0-9a-fA-F]{6}$/'],
                'select' => [...$r, Rule::in(array_keys(self::options($field['options'])))],
                'multiselect' => [...$r, 'array'],
                'toggle' => ['nullable', 'boolean'],
                'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
                default => [...$r, 'string', 'max:'.($field['max'] ?? 255)],
            };

            $rules[$name] = $r;

            if ($field['type'] === 'multiselect') {
                $rules["{$name}.*"] = [Rule::in(array_keys(self::options($field['options'])))];
            }
        }

        return $rules;
    }

    /**
     * Persist validated input for one group; unknown keys are dropped.
     *
     * @param  array<string, mixed>  $input  the request data, files included
     */
    public static function save(string $group, array $input): void
    {
        $current = Setting::read("settings.{$group}", []);
        $current = is_array($current) ? $current : [];
        $clean = [];

        foreach (self::fields($group) as $name => $field) {
            $value = $input[$name] ?? null;

            $clean[$name] = match ($field['type']) {
                'number' => $value === null || $value === '' ? null : $value + 0,
                'multiselect' => array_values(array_unique((array) $value)),
                'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'image' => self::storeImage($current[$name] ?? null, $value, ! empty($input["remove_{$name}"])),
                default => is_string($value) ? trim($value) : $value,
            };
        }

        Setting::write("settings.{$group}", $clean);
    }

    private static function storeImage(?string $existing, mixed $upload, bool $remove): ?string
    {
        $disk = Storage::disk('public');

        if ($upload instanceof UploadedFile) {
            $existing && $disk->delete($existing);
            $tenantId = app(TenantContext::class)->id();

            // The extension comes from the file's real type, never from the name the browser sent
            // (a "logo.php" holding a PNG would otherwise be stored as .php on the public disk).
            $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][(string) $upload->getMimeType()] ?? 'png';

            return $upload->storeAs("tenants/{$tenantId}/branding", 'logo-'.bin2hex(random_bytes(6)).'.'.$extension, 'public');
        }

        if ($remove && $existing) {
            $disk->delete($existing);

            return null;
        }

        return $existing;
    }

    /** Payment methods the tenant has switched on (always a non-empty list). */
    public static function paymentMethods(): array
    {
        $enabled = (array) self::get('finance.payment_methods', []);

        return $enabled === [] ? array_keys(self::options('payment_methods')) : $enabled;
    }
}
