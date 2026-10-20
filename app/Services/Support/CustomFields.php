<?php

namespace App\Services\Support;

use App\Models\CustomField;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Reads the company's custom field definitions (once per request) and turns
 * them into validation rules, cleaned values and display text.
 */
final class CustomFields
{
    /** @var array<string, Collection<int, CustomField>> */
    private static array $memo = [];

    public static function flush(): void
    {
        self::$memo = [];
    }

    /** @return Collection<int, CustomField> active fields of an entity, in order */
    public static function for(string $entity): Collection
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            return collect();
        }

        return self::$memo["{$tenantId}.{$entity}"] ??= CustomField::query()
            ->where('entity', $entity)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    /** @return array<string, array<int, mixed>> rules keyed "custom.{key}" */
    public static function rules(string $entity): array
    {
        $rules = [];

        foreach (self::for($entity) as $field) {
            $base = [$field->is_required && $field->type !== 'checkbox' ? 'required' : 'nullable'];

            $rules["custom.{$field->key}"] = match ($field->type) {
                'number' => [...$base, 'numeric', 'max:999999999999'],
                'date' => [...$base, 'date'],
                'select' => [...$base, Rule::in($field->options ?? [])],
                'checkbox' => ['nullable', 'boolean'],
                default => [...$base, 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * Keep only defined keys, cast to their type. A ticked box is true, a missing one false.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function clean(string $entity, array $input): array
    {
        $clean = [];

        foreach (self::for($entity) as $field) {
            $value = $input[$field->key] ?? null;

            $clean[$field->key] = match ($field->type) {
                'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'number' => $value === null || $value === '' ? null : $value + 0,
                default => $value === '' ? null : $value,
            };
        }

        return $clean;
    }

    /** Human text for one stored value. */
    public static function display(CustomField $field, mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '—',
            $field->type === 'checkbox' => $value ? 'Yes' : 'No',
            $field->type === 'date' => \Illuminate\Support\Carbon::parse($value)->format(df()),
            default => (string) $value,
        };
    }
}
