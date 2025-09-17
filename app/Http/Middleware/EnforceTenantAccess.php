<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;

class EnforceTenantAccess
{
    /**
     * Routes that should be excluded from tenant access check.
     *
     * @var array
     */
    protected $except = [
        'login',
        'logout',
        'password/*',
        'email/verify/*',
        'register',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-authenticated users or excluded routes
        if (!Auth::check() || $this->inExceptArray($request)) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Get tenant from route parameter, request query, or session
        $currentTenant = $request->route('tenant');
        $currentTenantId = $currentTenant
            ? (is_object($currentTenant) ? $currentTenant->id : $currentTenant)
            : ($request->input('tenant_id') ?: $request->session()->get('current_tenant_id'));

        // If no tenant is set, redirect to tenant selection
        if (!$currentTenantId) {
            return redirect()->route('tenants.select');
        }

        // Convert tenant ID to integer if it's a string
        $currentTenantId = is_numeric($currentTenantId) ? (int)$currentTenantId : $currentTenantId;
        
        // Get the tenant model
        $tenant = is_object($currentTenant) ? $currentTenant : Tenant::find($currentTenantId);

        // Super admins can access all tenants
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // For non-superadmin users, check if they have access to the current tenant
        if ($tenant && !$user->hasTenantAccess($tenant)) {
            abort(403, 'You do not have access to this tenant.');
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should pass through tenant access check.
     */
    protected function inExceptArray($request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
