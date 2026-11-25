<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::with(['games:id,title'])
            ->withCount('games')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $packages]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:packages,slug',
            'description' => 'nullable|string',
            'type' => 'required|in:CATEGORY,CURATED',
            'game_ids' => 'nullable|array',
            'game_ids.*' => 'exists:games,id',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?? UniqueSlug::for(Package::class, $data['name'], fallback: 'paket');

        $package = Package::create($data);
        $package->games()->sync($data['game_ids'] ?? []);

        $package->load(['games:id,title']);
        $package->loadCount('games');

        return response()->json(['data' => $package], 201);
    }

    public function show(Package $package): JsonResponse
    {
        $package->load(['games:id,title']);

        return response()->json(['data' => $package]);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:packages,slug,'.$package->id,
            'description' => 'nullable|string',
            'type' => 'sometimes|in:CATEGORY,CURATED',
            'game_ids' => 'nullable|array',
            'game_ids.*' => 'exists:games,id',
            'is_active' => 'boolean',
        ]);

        $package->update($data);

        if (array_key_exists('game_ids', $data)) {
            $package->games()->sync($data['game_ids'] ?? []);
        }

        $package->load(['games:id,title']);
        $package->loadCount('games');

        return response()->json(['data' => $package]);
    }

    public function destroy(Package $package): JsonResponse
    {
        // Deleting would cascade to its package loans — running ones included —
        // and turn their games into loose single loans. Keep the history.
        if ($package->packageLoans()->exists()) {
            return response()->json([
                'message' => 'Dieses Paket wurde schon ausgeliehen und kann deshalb nicht gelöscht werden. Deaktiviere es stattdessen.',
                'reason' => 'has_loans',
            ], 422);
        }

        $package->delete();

        return response()->json(['message' => 'Paket gelöscht.']);
    }
}
