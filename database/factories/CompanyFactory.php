<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\PaymentCurrency;
use App\Enums\PaymentFrequency;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Carriers',
            'mc_number' => strtoupper(fake()->unique()->bothify('MC-######')),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('+1 ### ### ####'),
            'email' => fake()->unique()->safeEmail(),
            'default_commission_percentage' => fake()->randomFloat(2, 8, 15),
            'payment_frequency' => PaymentFrequency::Weekly,
            'payment_currency' => PaymentCurrency::Usd,
            'status' => CompanyStatus::Active,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => CompanyStatus::Inactive]);
    }

    public function commission(float $percentage): static
    {
        return $this->state(fn (array $attributes) => ['default_commission_percentage' => $percentage]);
    }
}
