<?php

namespace App\Services\Activity;

use App\Support\Timezones;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Turns raw column values into what a person would say: "Booked → Loaded",
 * "Driver: Ali Khan" instead of driver_id 7, "1,250.00" instead of "1250.0000".
 */
final class ChangeFormatter
{
    private const ACRONYMS = ['Mc' => 'MC', 'Usd' => 'USD', 'Pkr' => 'PKR', 'Pk' => 'PK', 'Jv' => 'JV', 'Ft' => 'ft', 'Lbs' => 'lbs', 'Pod' => 'POD', 'Bol' => 'BOL'];

    public function fieldLabel(string $key): string
    {
        $key = str_ends_with($key, '_id') ? Str::beforeLast($key, '_id') : $key;

        return collect(explode(' ', Str::headline($key)))
            ->map(fn (string $word): string => self::ACRONYMS[$word] ?? $word)
            ->implode(' ');
    }

    /**
     * The human form of $model's attribute $key (casts applied).
     */
    public function value(Model $model, string $key): string
    {
        $value = $model->getAttribute($key);

        if ($value === null || $value === '') {
            return '—';
        }

        if (str_ends_with($key, '_id')) {
            return $this->related($model, $key, $value);
        }

        if ($value instanceof BackedEnum) {
            return method_exists($value, 'label') ? (string) $value->label() : (string) $value->value;
        }

        if ($value instanceof CarbonInterface) {
            $cast = (string) ($model->getCasts()[$key] ?? '');

            return str_starts_with($cast, 'date:') || $cast === 'date'
                ? $value->format('M j, Y')
                : Timezones::toUs($value)->format('M j, Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return Str::limit((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 160);
        }

        if (is_numeric($value) && str_starts_with((string) ($model->getCasts()[$key] ?? ''), 'decimal')) {
            return number_format((float) $value, 2);
        }

        return Str::limit((string) $value, 160);
    }

    private function related(Model $model, string $key, mixed $id): string
    {
        $relation = Str::camel(Str::beforeLast($key, '_id'));

        try {
            if (method_exists($model, $relation) && ($rel = $model->{$relation}()) instanceof BelongsTo) {
                $related = $rel->getRelated();
                $query = $related->newQuery();

                if (in_array(SoftDeletes::class, class_uses_recursive($related), true)) {
                    $query->withTrashed();
                }

                $found = $query->find($id);

                if ($found) {
                    $column = config('activity.models.'.$found::class.'.label');
                    $text = $column ? $found->getAttribute($column) : null;

                    return (string) ($text ?: $found->getAttribute('name') ?: '#'.$id);
                }
            }
        } catch (\Throwable) {
            // fall through to the bare id
        }

        return '#'.$id;
    }
}
