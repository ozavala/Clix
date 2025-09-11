<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScopeTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Get tenant ID from the request if provided (for users who can switch tenants)
        $tenantId = $request->input('tenant_id');
        
        // If no tenant ID in request, use the user's default tenant
        if (!$tenantId) {
            $tenantId = $user->tenant_id;
        }
        
        // If we have a tenant ID, set it in the request and session
        if ($tenantId) {
            $request->merge(['current_tenant_id' => $tenantId]);
            $request->session()->put('current_tenant_id', $tenantId);
            
            // Share the current tenant with all views
            $tenant = \App\Models\Tenant::find($tenantId);
            view()->share('currentTenant', $tenant);
        }
        
        return $next($request);
    }
}
