<?php

namespace App\Services\Tenancy;

use App\Support\Tenancy\TenantContext;

/**
 * Is a plan module switched on for the current tenant? No tenant (console,
 * super admin outside a workspace) or no plan means nothing is restricted,
 * the same rule TenantLimits applies to the caps.
 */
final class ModuleAccess
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function allows(string $module): bool
    {
        return $this->context->tenant()?->plan?->hasModule($module) ?? true;
    }
}
