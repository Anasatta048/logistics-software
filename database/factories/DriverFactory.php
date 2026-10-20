<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Company;
use App\Models\Driver;
use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'truck_number' => strtoupper(fake()->bothify('TRK-####')),
            'trailer_number' => strtoupper(fake()->bothify('TRL-####')),
            'equipment_type' => fake()->randomElement(EquipmentType::pluck('name')->all()),
            'phone' => fake()->numerify('+1 ### ### ####'),
            'email' => fake()->unique()->safeEmail(),
            'status' => DriverStatus::Available,
            'notes' => null,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    public function onLoad(): static
    {
        return $this->state(fn (array $attributes) => ['status' => DriverStatus::Loaded]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => DriverStatus::Inactive]);
    }
}
