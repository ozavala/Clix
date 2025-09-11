<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TenantAwareController extends Controller
{
    /**
     * The tenant model instance.
     */
    protected $tenant;

    /**
     * The current user's tenant ID.
     */
    protected $tenantId;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Get the authenticated user
            $user = Auth::user();
            
            // Set the current tenant ID (you may need to adjust this based on your auth setup)
            $this->tenantId = $user->tenant_id ?? null;
            
            // Share the current tenant with all views
            if ($this->tenantId) {
                $this->tenant = \App\Models\Tenant::find($this->tenantId);
                view()->share('currentTenant', $this->tenant);
                view()->share('userTenants', $user->tenants ?? collect([$this->tenant]));
            }
            
            return $next($request);
        });
    }

    /**
     * Apply tenant scope to a query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyTenantScope(Builder $query)
    {
        if ($this->tenantId) {
            return $query->where('tenant_id', $this->tenantId);
        }
        
        return $query;
    }

    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    protected function getTenantId()
    {
        return $this->tenantId;
    }

    /**
     * Get the current tenant.
     *
     * @return \App\Models\Tenant|null
     */
    protected function getTenant()
    {
        return $this->tenant;
    }
}
