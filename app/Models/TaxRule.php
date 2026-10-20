<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A named tax the company deducts on conversions, e.g. "Withholding tax - 1%". */
class TaxRule extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'type', 'value', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['value' => 'decimal:4', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('is_default')->orderBy('name');
    }

    public function summary(): string
    {
        $v = rtrim(rtrim(number_format((float) $this->value, 4, '.', ''), '0'), '.');

        return $this->type === 'fixed' ? cur_local().' '.$v : $v.'%';
    }
}
