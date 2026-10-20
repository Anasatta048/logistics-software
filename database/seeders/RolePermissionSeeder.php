<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Exactly the rights the policies and routes enforce, per module. A right
     * is only listed here if some policy method or route gate actually checks
     * it. Anything else shows up in the Role form as a switch that does
     * nothing, which is how "approve" on a load or "delete" on a user got in.
     */
    private const RIGHTS = [
        'users' => ['view', 'create', 'edit'],
        'roles' => ['view', 'create', 'edit', 'delete'],
        'companies' => ['view', 'create', 'edit', 'delete'],
        'drivers' => ['view', 'create', 'edit', 'delete'],
        'brokers' => ['view', 'create', 'edit', 'delete'],
        'equipment-types' => ['view', 'create', 'edit', 'delete'],
        // The load workflow has three separable rights: `load` moves a load
        // through pickup / loaded / delivered, `paid` marks it paid, `cancel`
        // cancels it. (Who sees the company commission follows the accounting
        // rights, see the view-company-commission gate.)
        'loads' => ['view', 'create', 'edit', 'load', 'paid', 'cancel'],
        'commissions' => ['view', 'pay'],
        'company-payments' => ['view', 'create', 'edit'],
        'invoices' => ['view', 'create', 'delete'],
        'expenses' => ['view', 'create', 'edit', 'delete'],
        'accounts' => ['view', 'create', 'edit', 'delete'],
        'journal-vouchers' => ['view', 'create', 'edit', 'approve', 'void'],
        'reports' => ['view'],
    ];

    /**
     * SRD 2.2 role matrix, as Str::is() patterns so a new module only needs a
     * new permission row, not 45 new lines per role.
     */
    private const MATRIX = [
        'admin' => ['*'],

        // Own loads only (scoped in the Service/Policy, not the permission set).
        'dispatcher' => [
            'companies.view', 'drivers.view', 'brokers.view',
            // A dispatcher adds a broker or driver on the spot when booking a load.
            'drivers.create', 'brokers.create',
            'loads.view', 'loads.create', 'loads.edit',
            'loads.load', 'loads.paid', 'loads.cancel',
            'commissions.view', 'equipment-types.view', 'reports.view',
        ],

        // Full access to money, view-only on loads.
        'accountant' => [
            'companies.view', 'drivers.view', 'brokers.view', 'loads.view', 'commissions.view',
            'equipment-types.view',
            'expenses.*', 'accounts.*', 'journal-vouchers.*',
            'company-payments.*',
            'invoices.*',
            'reports.view',
        ],

        'viewer' => ['*.view'],
    ];

    public function run(): void
    {
        // Before: a stale cache would make findOrCreate() re-insert rows that
        // already exist. After: the cache still holds the pre-seed list, so
        // `syncPermissions()` would not find the rows just created.
        // Standard roles are shared (team NULL), so seed them outside any tenant.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $this->forgetPermissionCache();

        $permissions = $this->seedPermissions();

        $this->forgetPermissionCache();

        foreach (self::MATRIX as $role => $patterns) {
            Role::findOrCreate($role)
                ->syncPermissions(array_values(array_filter(
                    $permissions,
                    fn (string $permission): bool => Str::is($patterns, $permission),
                )));
        }
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function seedPermissions(): array
    {
        $names = [];

        foreach (self::RIGHTS as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        foreach ($names as $name) {
            Permission::findOrCreate($name);
        }

        // Rights the policies no longer enforce must not linger: the Role form
        // builds its matrix from this table, so a leftover row would render as
        // a switch that does nothing.
        Permission::query()->whereNotIn('name', $names)->delete();

        return $names;
    }
}
