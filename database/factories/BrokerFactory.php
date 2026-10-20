<?php

namespace Database\Factories;

use App\Enums\BrokerStatus;
use App\Models\Broker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Broker>
 */
class BrokerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company().' Logistics',
            'broker_mc_number' => strtoupper(fake()->unique()->bothify('MC-######')),
            'credit_notes' => null,
            'status' => BrokerStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BrokerStatus::Inactive]);
    }
}
