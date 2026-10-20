<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Roles come in two kinds: the platform's standard roles (team_id NULL, shared
 * by every tenant, edited only by the super admin) and a tenant's own custom
 * roles (team_id = that tenant). Keeping the standard ones shared means the
 * permission cache stays the same size however many tenants exist.
 */
/**
 * @property int|null $team_id
 */
class Role extends SpatieRole
{
    /** The standard roles every tenant gets. */
    public const STANDARD = ['admin', 'dispatcher', 'accountant', 'viewer'];

    public function isStandard(): bool
    {
        return $this->team_id === null;
    }

    /**
     * Deliberately NOT a global scope: Spatie builds its shared permission cache
     * from Role queries, and a tenant-filtered cache would hide other tenants'
     * custom roles from their own users. Visibility is applied where roles are
     * listed or picked (this scope, TenantRule::roleExists, RolePolicy).
     *
     * Roles the given tenant can see and assign: the shared ones plus its own. */
    public function scopeVisibleTo(Builder $query, ?int $tenantId): Builder
    {
        $column = $query->getModel()->qualifyColumn('team_id');

        return $query->where(fn (Builder $q) => $q->whereNull($column)->when(
            $tenantId !== null,
            fn (Builder $q) => $q->orWhere($column, $tenantId),
        ));
    }
}
