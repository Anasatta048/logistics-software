<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'role' => null,
            ...$this->matrixData(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));
        $this->logRights($role, []);

        return redirect()
            ->route('roles.index')
            ->with('success', "Role {$role->name} created.");
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role,
            ...$this->matrixData($role),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $before = $role->permissions()->pluck('name')->all();
        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));
        $this->logRights($role, $before);

        return redirect()
            ->route('roles.index')
            ->with('success', "Role {$role->name} updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        // A role with people on it is not empty: deleting it would silently
        // strip their access mid-month.
        if ($role->users()->exists()) {
            return redirect()
                ->route('roles.index')
                ->with('error', "{$role->name} is still assigned to {$role->users()->count()} user(s). Move them to another role first.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', "Role {$name} removed.");
    }

    /**
     * Record which rights a role gained or lost; roles are the access-control
     * surface, so who changed them matters.
     *
     * @param  list<string>  $before
     */
    private function logRights(Role $role, array $before): void
    {
        $after = $role->permissions()->pluck('name')->all();
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added === [] && $removed === []) {
            return;
        }

        $parts = array_filter([
            $added ? 'granted '.implode(', ', $added) : null,
            $removed ? 'revoked '.implode(', ', $removed) : null,
        ]);

        app(ActivityLogger::class)->log(
            'Roles & Rights',
            'updated',
            "Rights of role {$role->name}: ".implode('; ', $parts),
            $role,
            $role->name,
            array_filter([
                $added ? ['field' => 'Granted', 'old' => null, 'new' => implode(', ', $added)] : null,
                $removed ? ['field' => 'Revoked', 'old' => implode(', ', $removed), 'new' => null] : null,
            ]),
        );
    }

    /**
     * The checkbox matrix: every permission grouped by its module, plus the
     * ones already on the role.
     *
     * @return array<string, mixed>
     */
    private function matrixData(?Role $role = null): array
    {
        $permissions = Permission::query()->orderBy('name')->get();

        return [
            'grouped' => $permissions->groupBy(fn (Permission $permission): string => Str::before($permission->name, '.')),
            'actions' => $permissions
                ->map(fn (Permission $permission): string => Str::after($permission->name, '.'))
                ->unique()
                ->values(),
            'selected' => $role?->permissions->pluck('name')->all() ?? [],
        ];
    }
}
