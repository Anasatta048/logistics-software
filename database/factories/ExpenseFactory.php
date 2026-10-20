<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(2, 20, 900),
            'account_id' => Account::factory(),
            'attachment_path' => null,
            'entered_by' => User::factory(),
            'notes' => null,
        ];
    }

    public function inCategory(ExpenseCategory $category): static
    {
        return $this->state(fn (array $attributes) => ['category' => $category]);
    }
}
