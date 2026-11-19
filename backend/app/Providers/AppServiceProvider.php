<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    /** Logins end after this many days without any use of the session. */
    public const SESSION_IDLE_DAYS = 30;

    public function boot(): void
    {
        $this->configureRateLimits();

        // Sliding session expiry: a token stops working once it hasn't been
        // used for SESSION_IDLE_DAYS (Sanctum's own `expiration` setting only
        // counts from creation, which would also log out daily users).
        // Sanctum updates last_used_at on every authenticated request.
        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $token, bool $isValid): bool => $isValid
                && ($token->last_used_at ?? $token->created_at)?->gt(now()->subDays(self::SESSION_IDLE_DAYS)) === true
        );

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

    /**
     * Brute-force and spam protection for the public auth endpoints and the
     * address check (which proxies to Nominatim's rate-limited public API).
     * Assumes $request->ip() is the real client: the API is served by
     * nginx + php-fpm without a CDN/proxy in front. Behind one, configure
     * trusted proxies first or every visitor shares one bucket.
     */
    private function configureRateLimits(): void
    {
        $tooMany = fn (Request $request, array $headers): JsonResponse => response()->json([
            'message' => 'Zu viele Versuche. Bitte warte kurz und versuche es dann erneut.',
        ], 429, $headers);

        $email = fn (Request $request): string => mb_strtolower(trim($request->string('email')->toString()));

        // Per account (slows guessing one password) and per IP (slows
        // trying many accounts from one machine).
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:'.$email($request).'|'.$request->ip())->response($tooMany),
            Limit::perMinute(30)->by('login-ip:'.$request->ip())->response($tooMany),
        ]);

        // Generous enough for a few people signing up on the same Wi-Fi at
        // an event, far too low for scripted sign-ups.
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by('register:'.$request->ip())->response($tooMany));

        // Endpoints that send an email: limit per address too, so nobody's
        // inbox can be flooded with reset or verification mails.
        RateLimiter::for('auth-email', fn (Request $request) => [
            Limit::perHour(5)->by('auth-email:'.$email($request))->response($tooMany),
            Limit::perHour(20)->by('auth-email-ip:'.$request->ip())->response($tooMany),
        ]);

        RateLimiter::for('auth-token', fn (Request $request) => Limit::perMinute(10)->by('auth-token:'.$request->ip())->response($tooMany));

        RateLimiter::for('address', fn (Request $request) => Limit::perMinute(10)->by('address:'.($request->user()->id ?? $request->ip()))->response($tooMany));
    }
}
