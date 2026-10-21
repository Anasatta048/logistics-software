<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides which tenant a signed-in request belongs to, once, early, and fails
 * closed: a user with no (usable) tenant never reaches a tenant screen.
 *
 *  - tenant user  -> their own tenant, or the "workspace locked" page
 *  - super admin  -> platform screens only, unless they entered a workspace
 *                    (session `impersonating_tenant_id`), then that tenant
 *  - guest        -> nothing to resolve
 */
final class ResolveTenant
{
    /** Routes a super admin may open without being inside a workspace. */
    private const PLATFORM_ROUTES = [
        'super-admin.*', 'profile.*', 'password.*', 'logout', 'home', 'notifications.*', 'request-access.*',
    ];

    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            return $this->superAdmin($request, $next);
        }

        $tenant = $user->tenant_id ? Tenant::cached((int) $user->tenant_id) : null;

        if ($tenant === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account is not attached to a workspace.']);
        }

        // A locked workspace can still sign out and open Billing, so it can pay its way back in.
        if (! $tenant->isUsable() && ! $request->routeIs('logout', 'billing.*')) {
            return response()->view('tenant.locked', ['tenant' => $tenant], 403);
        }

        $this->context->set($tenant);

        return $next($request);
    }

    private function superAdmin(Request $request, Closure $next): Response
    {
        $tenantId = $request->session()->get('impersonating_tenant_id');
        $tenant = $tenantId ? Tenant::cached((int) $tenantId) : null;

        if ($tenantId && $tenant === null) {
            $request->session()->forget('impersonating_tenant_id');
        }

        if ($tenant !== null) {
            $this->context->set($tenant);

            return $next($request);
        }

        if (! $request->routeIs(...self::PLATFORM_ROUTES)) {
            return redirect()->route('super-admin.dashboard');
        }

        return $next($request);
    }
}
