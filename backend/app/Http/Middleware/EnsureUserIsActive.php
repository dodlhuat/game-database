<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isActive()) {
            return response()->json([
                'message' => 'Dein Konto ist noch nicht freigeschaltet.',
            ], 403);
        }

        return $next($request);
    }
}
