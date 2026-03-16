<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::with(['category', 'games:id,title,slug'])
            ->withCount('games')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $packages]);
    }

    public function show(Package $package): JsonResponse
    {
        if (!$package->is_active) {
            return response()->json(['message' => 'Paket nicht gefunden.'], 404);
        }

        $package->load(['category', 'games:id,title,slug']);
        $package->loadCount('games');

        return response()->json(['data' => $package]);
    }
}
