<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantController extends Controller
{
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
        
        // If user can view all tenants or is a member of the requested tenant
        if ($user->can('view-all-tenants') || 
            $user->tenants()->where('id', $tenantId)->exists()) {
            
            $tenant = Tenant::findOrFail($tenantId);
            
            // Store the selected tenant ID in the session
            Session::put('current_tenant_id', $tenant->id);
            
            // Update user's default tenant if they have the permission
            if ($user->can('update-tenant-default') && $request->has('set_default')) {
                $user->tenant_id = $tenant->id;
                $user->save();
            }
            
            return redirect()->back()
                ->with('success', __('Switched to :tenant', ['tenant' => $tenant->name]));
        }
        
        return redirect()->back()
            ->with('error', __('You do not have permission to access this tenant.'));
    }
    
    /**
     * Show the tenant selection page.
     *
     * @return \Illuminate\View\View
     */
    public function select()
    {
        $user = Auth::user();
        $tenants = $user->can('view-all-tenants') 
            ? Tenant::orderBy('name')->get()
            : $user->tenants()->orderBy('name')->get();
            
        return view('tenants.select', compact('tenants'));
    }
}
