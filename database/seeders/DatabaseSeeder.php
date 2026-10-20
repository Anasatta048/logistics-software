<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\EquipmentType;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: the platform (standard roles, plans,
     * super admin) and one demo workspace to click around in.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(SuperAdminSeeder::class);

        app(TenantContext::class)->run(DemoDataSeeder::demoTenant(), function (): void {
            

            foreach (config('saas.starter_equipment_types') as $name) {
                EquipmentType::query()->firstOrCreate(['name' => $name]);
            }

            // Password is the factory default ("password").
            User::query()->firstOrCreate(
                ['email' => 'admin@dreamelectlogistics.com'],
                User::factory()->make(['name' => 'Admin', 'status' => UserStatus::Active])->getAttributes(),
            )->assignRole('admin');

            User::query()->firstOrCreate(
                ['email' => 'dispatcher@dreamelectlogistics.com'],
                User::factory()->make(['name' => 'Dispatch Demo', 'status' => UserStatus::Active, 'commission_percentage' => 10])->getAttributes(),
            )->assignRole('dispatcher');
        });
    }
}
