<?php

namespace App\Http\Controllers;

use App\Models\CookieVersion;
use Illuminate\Http\JsonResponse;

class CookieController extends Controller
{
    public function show(): JsonResponse
    {
        $cookie = CookieVersion::orderByDesc('published_at')->first();

        if (! $cookie) {
            return response()->json(['message' => 'Keine Cookie-Richtlinie gefunden.'], 404);
        }

        return response()->json($cookie);
    }
}
