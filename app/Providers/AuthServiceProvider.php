<?php

namespace App\Providers;


use App\Models\Permission;
use App\Models\Tenant;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Tenant::class => TenantPolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Use Gate::before to check for permissions dynamically. This is far more
        // performant as it avoids querying the database on every single request.
        // The closure will be executed before any other Gate checks.
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($ability)) {
                return true;
            }
        });

        Gate::define('view-feedback', function ($Crmuser, $user) {
            return $user->hasPermissionTo('view-feedback');
        });
        Gate::define('edit-feedback', function ($Crmuser, $user) {
            return $user->hasPermissionTo('edit-feedback');
        });
        Gate::define('edit-settings', function ($user) {
            return $user->hasPermissionTo('edit-settings');
        });


    }
}
