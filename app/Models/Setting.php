<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin-editable application settings, one JSON value per key. Read through
 * Setting::read() so a missing table or key (fresh install, tests) quietly
 * falls back to the caller's default instead of breaking the page.
 */
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        try {
            $row = static::query()->find($key);
        } catch (\Throwable) {
            return $default;
        }

        return $row?->value ?? $default;
    }

    public static function write(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
