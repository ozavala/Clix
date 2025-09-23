<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantController extends Controller
{
    /**
     * Show the tenant selection page.
     *
     * @return \Illuminate\View\View
     */
    public function select()
    {
        $user = Auth::user();
        
        // Superadmins can see all tenants, regular users only see their assigned tenants
        $tenants = $user->isSuperAdmin() 
            ? Tenant::orderBy('name')->get()
            : $user->tenants()->orderBy('name')->get();
            
        return view('tenants.select', [
            'tenants' => $tenants,
            'currentTenant' => $user->primaryTenant()
        ]);
    }
    
    /**
     * Switch the current tenant for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $tenantId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(Request $request, $tenantId)
    {
        $user = Auth::user();
        $tenant = Tenant::findOrFail($tenantId);
        
        // Check if user has access to this tenant
        if (!$user->isSuperAdmin() && !$user->hasTenantAccess($tenant)) {
            return redirect()->back()->with('error', 'You do not have access to this tenant.');
        }
        
        // For non-superadmin users, they can only switch to their primary tenant
        if (!$user->isSuperAdmin() && $tenant->getKey() !== $user->primaryTenant()?->getKey()) {
            return redirect()->back()->with('error', 'You can only access your primary tenant.');
        }
        
        // Set the tenant as primary
        $user->setPrimaryTenant($tenant);
        
        // Store the current tenant ID in the session
        Session::put('current_tenant_id', $tenant->getKey());
        
        return redirect()->back()->with('success', __('Switched to :tenant', ['tenant' => $tenant->name]));
    }
}
