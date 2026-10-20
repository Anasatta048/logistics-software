<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * `loads.dispatch` (the Delivered step) now belongs to `loads.load`, and
 * `loads.commission` (seeing the company commission) follows the accounting
 * rights. Neither is a switch worth offering, so both rows go.
 */
return new class () extends Migration {
    private const GONE = ['loads.dispatch', 'loads.commission'];

    public function up(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::GONE)->pluck('id');

        // Anyone who could dispatch keeps working: give those roles `loads.load`.
        $loadId = DB::table('permissions')->where('name', 'loads.load')->value('id');
        $dispatchId = DB::table('permissions')->where('name', 'loads.dispatch')->value('id');

        if ($loadId !== null && $dispatchId !== null) {
            foreach (DB::table('role_has_permissions')->where('permission_id', $dispatchId)->pluck('role_id') as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $loadId, 'role_id' => $roleId]);
            }
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally empty: the rights are recreated by RolePermissionSeeder if ever restored.
    }
};
