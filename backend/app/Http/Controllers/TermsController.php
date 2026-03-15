<?php

namespace App\Http\Controllers;

use App\Models\TermsVersion;
use Illuminate\Http\JsonResponse;

class TermsController extends Controller
{
    public function show(): JsonResponse
    {
        $terms = TermsVersion::orderByDesc('published_at')->first();

        if (!$terms) {
            return response()->json(['message' => 'Keine Nutzungsbedingungen gefunden.'], 404);
        }

        return response()->json($terms);
    }
}
