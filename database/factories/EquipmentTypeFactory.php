<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EquipmentType>
 */
class EquipmentTypeFactory extends Factory
{
    protected $model = EquipmentType::class;

    public function definition(): array
    {
        $name = Str::headline(fake()->unique()->words(2, true));

        return ['name' => $name];
    }
}
