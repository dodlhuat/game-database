<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Favorite;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $games = Game::whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
            ->with(['category', 'tags'])
            ->withCount('copies')
            ->withCount(['copies as available_copies_count' => fn ($q) => $q->where('condition', '!=', 'LOCKED')->whereDoesntHave('activeLoans'),
            ])
            ->get()
            ->each(fn ($game) => $game->is_favorited = true);

        return GameResource::collection($games);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['game_id' => ['required', 'integer', 'exists:games,id']]);

        /** @var User $user */
        $user = $request->user();
        Favorite::firstOrCreate([
            'game_id' => $request->game_id,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Zu Favoriten hinzugefügt.']);
    }

    public function destroy(Request $request, Game $game): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        Favorite::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['message' => 'Aus Favoriten entfernt.']);
    }
}
