<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Im Plattform-Wartungsmodus (config app.maintenance_mode) werden bestehende
 * Logins von Nicht-Admins beendet: Der Token wird gelöscht und die Anfrage mit
 * 401 abgelehnt, worauf das Frontend den User ausloggt.
 */
class BlockNonAdminsDuringMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (config('app.maintenance_mode') && $user && ! $user->isAdmin()) {
            // Sanctum typisiert den Token als nie null, ohne Token-Login (z. B. actingAs) ist er es aber.
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) { // @phpstan-ignore instanceof.alwaysTrue
                $token->delete();
            }

            return response()->json([
                'message' => 'Wegen Wartungsarbeiten ist die Anmeldung gerade nicht möglich.',
                'reason' => 'maintenance',
            ], 401);
        }

        return $next($request);
    }
}
