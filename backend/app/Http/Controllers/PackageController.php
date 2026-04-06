<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::with(['category'])
            ->withCount('games')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $packages->each(fn($package) => $this->appendAvailability($package));

        return response()->json(['data' => $packages]);
    }

    public function show(Package $package): JsonResponse
    {
        if (!$package->is_active) {
            return response()->json(['message' => 'Paket nicht gefunden.'], 404);
        }

        $package->load(['category']);
        $package->loadCount('games');

        $games = $package->games()
            ->withCount(['copies as available_copies_count' => function ($q) {
                $q->where('condition', '!=', 'LOCKED')
                  ->whereDoesntHave('activeLoans');
            }])
            ->get(['games.id', 'games.title', 'games.slug']);

        $package->setRelation('games', $games);
        $package->setAttribute('available', $games->every(fn($g) => $g->available_copies_count > 0));

        return response()->json(['data' => $package]);
    }

    private function appendAvailability(Package $package): void
    {
        $games = $package->games()
            ->withCount(['copies as available_copies_count' => function ($q) {
                $q->where('condition', '!=', 'LOCKED')
                  ->whereDoesntHave('activeLoans');
            }])
            ->get(['games.id']);

        $package->setAttribute('available', $games->isNotEmpty() && $games->every(fn($g) => $g->available_copies_count > 0));
    }
}
