<?php

namespace App\Models\Concerns;

use App\Services\Support\CustomFields;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Gives a model the company's custom field values in `custom` (JSON). Whatever
 * is assigned is cleaned against the current definitions, so unknown keys and
 * wrong types never reach the database. Set `$customEntity` on the model.
 */
trait HasCustomFields
{
    protected function custom(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): array => $value ? (array) json_decode($value, true) : [],
            set: fn (mixed $value): ?string => json_encode(CustomFields::clean($this->customEntity, (array) $value)),
        );
    }

    /** The value of one custom field, or null. */
    public function customValue(string $key): mixed
    {
        return $this->custom[$key] ?? null;
    }
}
