<?php

namespace Database\Factories;

use App\Enums\CompanyPaymentStatus;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyPayment>
 */
class CompanyPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalDue = fake()->randomFloat(2, 500, 5000);

        return [
            'company_id' => Company::factory(),
            'period_from' => now()->startOfWeek()->toDateString(),
            'period_to' => now()->endOfWeek()->toDateString(),
            'total_due' => $totalDue,
            'amount_received' => $totalDue,
            'payment_date' => now()->toDateString(),
            'reference' => strtoupper(fake()->bothify('WIRE-#####')),
            'attachment_path' => null,
            'status' => CompanyPaymentStatus::FullySettled,
            'created_by' => User::factory(),
        ];
    }

    public function shortPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_received' => round((float) $attributes['total_due'] / 2, 2),
            'status' => CompanyPaymentStatus::PartiallySettled,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_received' => 0,
            'status' => CompanyPaymentStatus::Pending,
        ]);
    }
}
