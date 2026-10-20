<?php

namespace Database\Factories;

use App\Enums\LoadStatus;
use App\Models\Broker;
use App\Models\Company;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\Load;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Load>
 */
class LoadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $miles = fake()->randomFloat(1, 50, 900);
        $rate = fake()->randomFloat(2, 800, 6000);
        $percentage = fake()->randomFloat(2, 8, 15);

        return [
            'reference_number' => null, // LoadService stamps it from the id
            'company_id' => Company::factory(),
            'broker_id' => Broker::factory(),
            'driver_id' => null,
            'driver_name' => null,
            'equipment_type' => fake()->randomElement(EquipmentType::pluck('name')->all()),
            'origin_address' => fake()->address(),
            'origin_lat' => fake()->latitude(30, 45),
            'origin_lng' => fake()->longitude(-100, -80),
            'destination_address' => fake()->address(),
            'destination_lat' => fake()->latitude(30, 45),
            'destination_lng' => fake()->longitude(-100, -80),
            'total_miles' => $miles,
            'weight_lbs' => fake()->randomFloat(1, 1000, 45000),
            'pickup_at' => fake()->dateTimeBetween('now', '+5 days'),
            'dropoff_at' => fake()->dateTimeBetween('+1 day', '+7 days'),
            'rate' => $rate,
            'rate_per_mile' => round($rate / $miles, 2),
            'company_commission_percentage' => $percentage,
            'company_commission_amount' => round($rate * $percentage / 100, 2),
            'dispatcher_id' => User::factory(),
            'status' => LoadStatus::Booked,
            'cancellation_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'notes' => null,
        ];
    }

    public function withDriver(?Driver $driver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => ($driver ?? Driver::factory()->create())->id,
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    public function forDispatcher(User $dispatcher): static
    {
        return $this->state(fn (array $attributes) => ['dispatcher_id' => $dispatcher->id]);
    }

    public function status(LoadStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoadStatus::Cancelled,
            'cancellation_reason' => 'Broker cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
