<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Scramble docs UI only available in non-production environments
        if (app()->isProduction()) {
            Scramble::ignoreDefaultRoutes();
        }

        // Any authenticated API request counts as activity for the
        // inactive-account cleanup (accounts:prune-inactive). The SPA keeps
        // its token for good, so members who never log in again are still
        // active. markSeen() writes at most once a day per user.
        Event::listen(TokenAuthenticated::class, function (TokenAuthenticated $event): void {
            $user = $event->token->tokenable;
            if ($user instanceof User) {
                $user->markSeen();
            }
        });
    }
}
