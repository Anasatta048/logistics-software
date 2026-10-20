<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The platform owner. Login: superadmin@logistics.test / password123
 * (change after first login). Safe to re-run. Belongs to no tenant.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::withoutGlobalScopes()->firstOrNew(['email' => 'superadmin@logistics.test']);

        $user->fill([
            'name' => 'Super Admin',
            'password' => $user->exists ? $user->password : 'password123',
            'status' => UserStatus::Active,
        ]);
        $user->is_super_admin = true;
        $user->tenant_id = null;
        $user->email_verified_at ??= now();
        $user->save();
    }
}
