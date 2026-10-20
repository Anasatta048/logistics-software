<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Staging seed: roles/permissions + one admin. Safe to re-run on every deploy.
 * Login: admin@dreamelectlogistics.com / password (change after first login).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(InvoiceProfileSeeder::class);

        User::firstOrCreate(
            ['email' => 'admin@dreamelectlogistics.com'],
            [
                'name' => 'Admin',
                'password' => 'password123',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        )->syncRoles('admin');
    }
}
