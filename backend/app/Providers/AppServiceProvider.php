<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Scramble docs UI only available in non-production environments
        if (app()->isProduction()) {
            Scramble::ignoreDefaultRoutes();
        }
    }
}
