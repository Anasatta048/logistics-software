<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A heading expenses are filed under (Salaries, Rent, Load board…). Editable per company. */
class ExpenseCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Same-shaped value the old enum had, so views keep reading ->label(). */
    public function label(): string
    {
        return $this->name;
    }

    /** Create the starter set for the tenant currently in context. */
    public static function seedStarter(): void
    {
        foreach (config('saas.starter_expense_categories') as $i => $name) {
            static::query()->firstOrCreate(['name' => $name], ['sort_order' => $i]);
        }
    }
}
