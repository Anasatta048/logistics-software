<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $opening = fake()->randomFloat(2, 0, 10000);

        return [
            'name' => fake()->unique()->company().' '.fake()->randomElement(['Cash', 'Bank']),
            'type' => fake()->randomElement(AccountType::cases()),
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'status' => AccountStatus::Active,
        ];
    }
}
