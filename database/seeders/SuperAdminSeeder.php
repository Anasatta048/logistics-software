<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The platform owner. Login: superadmin@logistics.test / password123
 * (change after first login). Safe to re-run.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@logistics.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password123',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        )->syncRoles('super-admin');
    }
}
