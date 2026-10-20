<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(InvoiceProfileSeeder::class);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@dreamelectlogistics.com',
            'status' => UserStatus::Active,
        ])->assignRole('admin');

        // Demo dispatcher so the Phase 1 "sign in as a dispatcher" check works out
        // of the box. Password is the factory default.
        User::factory()->create([
            'name' => 'Dispatch Demo',
            'email' => 'dispatcher@dreamelectlogistics.com',
            'status' => UserStatus::Active,
            'commission_percentage' => 10,
        ])->assignRole('dispatcher');
    }
}
