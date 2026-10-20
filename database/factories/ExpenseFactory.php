<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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
            'expense_category_id' => fn () => ExpenseCategory::query()->inRandomOrder()->value('id') ?? ExpenseCategory::query()->create(['name' => 'General'])->id,
            'payee' => fake()->company(),
            'amount' => fake()->randomFloat(2, 20, 900),
            'account_id' => Account::factory(),
            'attachment_path' => null,
            'entered_by' => User::factory(),
            'notes' => null,
        ];
    }

    public function inCategory(ExpenseCategory $category): static
    {
        return $this->state(fn (array $attributes) => ['expense_category_id' => $category->id]);
    }
}
