<?php

namespace App\Providers;

use App\Services\ConfigurationService;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ConfigurationService::class, function ($app) {
            $tenantId = null;
            
            // Try to get tenant ID from tenant helper if available
            if (function_exists('tenant')) {
                $tenantId = tenant('id');
            }
            
            // Fall back to currentTenant binding if tenant helper not available
            if (!$tenantId && $app->bound('currentTenant')) {
                $tenant = $app->make('currentTenant');
                $tenantId = $tenant ? $tenant->id : null;
            }
            
            return new ConfigurationService($tenantId);
        });
        
        // Alias for easier access
        $this->app->alias(ConfigurationService::class, 'config-service');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
