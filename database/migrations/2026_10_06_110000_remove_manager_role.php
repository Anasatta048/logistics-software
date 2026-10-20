<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The built-in manager role is gone: admins build whatever roles they need
 * from Roles & Rights. Users who held it are left without a role until an
 * admin gives them one.
 */
return new class () extends Migration {
    public function up(): void
    {
        $id = DB::table('roles')->where('name', 'manager')->value('id');

        if ($id !== null) {
            DB::table('model_has_roles')->where('role_id', $id)->delete();
            DB::table('role_has_permissions')->where('role_id', $id)->delete();
            DB::table('roles')->where('id', $id)->delete();
        }
    }

    public function down(): void
    {
        // Intentionally empty: the role is recreated by RolePermissionSeeder history only if restored by hand.
    }
};
