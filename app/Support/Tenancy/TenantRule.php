<?php

namespace App\Support\Tenancy;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Validation's exists/unique talk to the database directly and so bypass the
 * model scope. Without these a user could submit another tenant's driver id
 * (exists) or be told a value is "taken" by a stranger's record (unique).
 * Use these instead of Rule::exists / Rule::unique for every tenant table.
 */
final class TenantRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', app(TenantContext::class)->id());
    }

    /** A role name must be free among the shared roles and this tenant's own. */
    public static function roleUnique(): Unique
    {
        $tenantId = app(TenantContext::class)->id();

        return Rule::unique('roles', 'name')->where(fn ($q) => $q->where(
            fn ($w) => $w->whereNull('team_id')->orWhere('team_id', $tenantId),
        ));
    }

    /** An assignable role: a shared one or one of this tenant's own. */
    public static function roleExists(): Exists
    {
        $tenantId = app(TenantContext::class)->id();

        return Rule::exists('roles', 'name')->where(fn ($q) => $q->where(
            fn ($w) => $w->whereNull('team_id')->orWhere('team_id', $tenantId),
        ));
    }

    public static function unique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where('tenant_id', app(TenantContext::class)->id());
    }
}
