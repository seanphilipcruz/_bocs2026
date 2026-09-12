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
        // Docker uses a managed volume for secure key permissions. Production
        // can omit this setting and use Passport's default storage directory.
        if ($keyPath = config('auth.passport_key_path')) {
            Passport::loadKeysFrom($keyPath);
        }

        // The imported Passport 10 database uses incremental integer client IDs.
        Passport::$clientUuids = false;
    }
}
