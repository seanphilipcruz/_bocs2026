<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        // Keep signing keys on a Docker-managed filesystem so Passport can
        // enforce private-key permissions when the project is bind-mounted.
        Passport::loadKeysFrom(storage_path('passport'));

        // The imported Passport 10 database uses incremental integer client IDs.
        Passport::$clientUuids = false;
    }
}
