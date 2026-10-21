<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $growth = ['reports', 'accounts', 'expenses', 'journal_vouchers', 'company_payments', 'commissions', 'roles', 'data'];

        $plans = [
            [
                'name' => 'Starter', 'slug' => 'starter', 'tagline' => 'For solo dispatchers getting organised.',
                'monthly_price' => 49, 'yearly_price' => 490, 'monthly_price_pkr' => 13720, 'yearly_price_pkr' => 137200, 'trial_days' => 14,
                'max_users' => 3, 'max_drivers' => 10, 'max_loads_per_month' => 150,
                'features' => ['Load booking & tracking', 'Drivers & brokers', 'PDF invoices', 'Basic reports', 'Email support'],
                'modules' => ['reports'],
                'is_featured' => false, 'sort_order' => 1,
            ],
            [
                'name' => 'Growth', 'slug' => 'growth', 'tagline' => 'For growing dispatch teams that need the books too.',
                'monthly_price' => 129, 'yearly_price' => 1290, 'monthly_price_pkr' => 36120, 'yearly_price_pkr' => 361200, 'trial_days' => 14,
                'max_users' => 15, 'max_drivers' => 60, 'max_loads_per_month' => 1000,
                'features' => ['Everything in Starter', 'Accounts, expenses & journal vouchers', 'Dispatcher commissions', 'Company payments & receivables', 'Roles & rights', 'Real-time notifications', 'Priority support'],
                'modules' => $growth,
                'is_featured' => true, 'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'tagline' => 'For large fleets and brokerages. No limits.',
                'monthly_price' => 349, 'yearly_price' => 3490, 'monthly_price_pkr' => 97720, 'yearly_price_pkr' => 977200, 'trial_days' => 30,
                'max_users' => null, 'max_drivers' => null, 'max_loads_per_month' => null,
                'features' => ['Everything in Growth', 'Unlimited users, drivers & loads', 'Full activity log & audit trail', 'Advanced P&L and statements', 'Custom onboarding', 'Dedicated account manager'],
                'modules' => [...$growth, 'advanced_reports', 'activity_log'],
                'is_featured' => false, 'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
