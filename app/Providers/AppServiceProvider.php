<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set app URL from environment (important for form actions and redirects)
        // In production, this should be https://fintask.onrender.com
        if (env('APP_URL')) {
            \URL::forceRootUrl(env('APP_URL'));
            
            // If APP_URL starts with https, force the scheme
            if (str_starts_with(env('APP_URL'), 'https')) {
                \URL::forceScheme('https');
            }
        }
    }
}
