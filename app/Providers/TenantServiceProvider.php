<?php

namespace App\Providers;

use App\Http\View\Composers\TenantComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
{
    $this->app->singleton('currentTenant', function ($app) {
        // Get the current tenant ID from the session, request, or wherever you store it
        $tenantId = session('tenant_id') ?? request()->header('X-Tenant-ID');
        
        if (!$tenantId) {
            // If no tenant ID is found, return null or handle it appropriately
            return null;
        }
        // Return the tenant model
        return \App\Models\Tenant::find($tenantId);// ?? new \App\Models\Tenant();
    });
}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the tenant composer for all views
        View::composer('*', TenantComposer::class);
    }
}
