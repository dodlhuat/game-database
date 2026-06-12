<?php

namespace App\Http\Controllers;

use App\Models\PrivacyVersion;
use Illuminate\Http\JsonResponse;

class PrivacyController extends Controller
{
    public function show(): JsonResponse
    {
        $privacy = PrivacyVersion::orderByDesc('published_at')->first();

        if (! $privacy) {
            return response()->json(['message' => 'Keine Datenschutzerklärung gefunden.'], 404);
        }

        return response()->json($privacy);
    }
}
