<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Pins every query on a tenant model to the current tenant. With no tenant in
 * context (platform area, login, console) it does nothing: those code paths
 * never render tenant data, and the ResolveTenant middleware refuses to let a
 * signed-in non-platform user through without one.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = app(TenantContext::class)->id();

        if ($id !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), $id);
        }
    }
}
