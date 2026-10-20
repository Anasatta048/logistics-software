<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Platform seed, safe to re-run on every deploy: the standard roles, the plans
 * and the super admin (superadmin@logistics.test / password123: change it
 * after the first login). Workspaces are created by approving access requests.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(SuperAdminSeeder::class);
    }
}
