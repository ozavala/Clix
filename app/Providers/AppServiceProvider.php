<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use App\Http\Middleware\EnforceTenantAccess;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EnforceTenantAccess::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Payment::observe(PaymentObserver::class);
        // Solo intentar leer settings si la tabla existe
        if (\Schema::hasTable('settings')) {
            $defaultLocale = Setting::where('key', 'default_locale')->value('value') ?? config('app.locale');
            app()->setLocale($defaultLocale);
        }
    }
}
