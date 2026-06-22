<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

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
        Carbon::setLocale('id');

        // Force HTTPS scheme when appropriate so generated redirects use https
        // - If APP_URL is https or
        // - Running in production environment or
        // - Explicit env flag FORCE_HTTPS is truthy
        $appUrl = config('app.url');
        $forceEnv = env('FORCE_HTTPS', false);

        if (
            app()->environment('production') ||
            ($appUrl && str_starts_with((string) $appUrl, 'https')) ||
            $forceEnv
        ) {
            URL::forceScheme('https');
        }
    }
}
