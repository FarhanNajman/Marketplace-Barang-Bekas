<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Paksa skema HTTPS dan cookie sesi aman HANYA di lingkungan production
        // agar environment lokal (Laragon / localhost) tetap berjalan normal melalui HTTP.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            if (config('session.secure') === null) {
                config(['session.secure' => true]);
            }
        }
    }
}
