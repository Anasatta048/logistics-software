<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route guard for areas that share a permission with core screens (e.g. Roles use the User policy). */
final class EnsurePlanModule
{
    public function __construct(private readonly ModuleAccess $modules)
    {
    }

    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($request->user()?->is_super_admin || $this->modules->allows($module), 403, 'This feature is not included in your plan.');

        return $next($request);
    }
}
