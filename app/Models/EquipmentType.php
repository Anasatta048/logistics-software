<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Editable equipment list (Dry Van, Reefer, ...). The name is the key:
 * drivers.equipment_type and loads.equipment_type store it directly, so a
 * dropdown, a list and a validation rule all read the same string.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Driver> $drivers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Load> $loads
 */
class EquipmentType extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\EquipmentTypeFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    /** @return HasMany<Driver, $this> */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'equipment_type', 'name');
    }

    /** @return HasMany<Load, $this> */
    public function loads(): HasMany
    {
        return $this->hasMany(Load::class, 'equipment_type', 'name');
    }
}
