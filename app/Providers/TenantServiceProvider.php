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
    public function register(): void
    {
        //
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
