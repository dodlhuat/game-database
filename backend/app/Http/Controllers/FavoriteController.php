<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Favorite;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $games = Game::whereHas('favorites', fn($q) => $q->where('user_id', $request->user()->id))
            ->with(['category', 'tags'])
            ->withCount('copies')
            ->withCount(['copies as available_copies_count' => fn($q) =>
                $q->where('condition', '!=', 'LOCKED')->whereDoesntHave('activeLoans')
            ])
            ->get()
            ->each(fn($game) => $game->is_favorited = true);

        return GameResource::collection($games);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['game_id' => ['required', 'integer', 'exists:games,id']]);

        Favorite::firstOrCreate([
            'game_id' => $request->game_id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Zu Favoriten hinzugefügt.']);
    }

    public function destroy(Request $request, Game $game): JsonResponse
    {
        Favorite::where('game_id', $game->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Aus Favoriten entfernt.']);
    }
}
