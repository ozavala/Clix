<?php

namespace App\Http\View\Composers;

use App\Models\Tenant;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TenantComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $currentTenant = null;
        $userTenants = collect();
        
        if (Auth::check()) {
            $user = Auth::user();
            
            // Get current tenant from session or user's default
            if (session()->has('current_tenant_id')) {
                $currentTenant = Tenant::find(session('current_tenant_id'));
            } elseif ($user->tenant_id) {
                $currentTenant = $user->tenant;
            }
            
            // Get user's accessible tenants
            if ($user->can('view-all-tenants')) {
                $userTenants = Tenant::orderBy('name')->get();
            } elseif ($currentTenant) {
                $userTenants = collect([$currentTenant]);
            }
        }
        
        $view->with([
            'currentTenant' => $currentTenant,
            'userTenants' => $userTenants,
            'hasMultipleTenants' => $userTenants->count() > 1,
        ]);
    }
}
