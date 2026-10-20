<?php

namespace App\Support\Settings;

use App\Enums\PaymentMethod;
use App\Enums\TaxType;
use App\Models\Setting;
use Illuminate\Validation\Rule;

/**
 * Typed access to the per-tenant settings declared in config/tenant-settings.php.
 * One Setting row per group (`settings.{group}`); every read after the first in
 * a request is served from the TenantContext memo, so asking for a value on
 * every page costs no extra query.
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
            $values[$name] = array_key_exists($name, $saved) && $saved[$name] !== null && $saved[$name] !== ''
                ? $saved[$name]
                : self::defaultFor($group, $name, $field);
        }

        return $values;
    }

    /** @param array<string, mixed> $field */
    private static function defaultFor(string $group, string $name, array $field): mixed
    {
        if ($group === 'business') {
            $tenant = app(\App\Support\Tenancy\TenantContext::class)->tenant();

            return match ($name) {
                'name' => $tenant->name ?? config('app.name'),
                'email' => $tenant->email ?? null,
                'phone' => $tenant->phone ?? null,
                default => $field['default'] ?? null,
            };
        }

        if ($field['type'] === 'multiselect') {
            return array_keys(self::options($field['options']));
        }

        return $field['default'] ?? null;
    }

    /** @return array<string, string> */
    public static function options(string $name): array
    {
        return match ($name) {
            'tax_types' => collect(TaxType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'payment_methods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
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
     * @param  array<string, mixed>  $input
     */
    public static function save(string $group, array $input): void
    {
        $clean = [];

        foreach (self::fields($group) as $name => $field) {
            $value = $input[$name] ?? null;

            $clean[$name] = match ($field['type']) {
                'number' => $value === null || $value === '' ? null : $value + 0,
                'multiselect' => array_values(array_unique((array) $value)),
                default => is_string($value) ? trim($value) : $value,
            };
        }

        Setting::write("settings.{$group}", $clean);
    }

    /** Payment methods the tenant has switched on (always a non-empty list). */
    public static function paymentMethods(): array
    {
        $enabled = (array) self::get('finance.payment_methods', []);

        return $enabled === [] ? array_keys(self::options('payment_methods')) : $enabled;
    }
}
