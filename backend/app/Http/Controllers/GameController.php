<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Copy;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GameController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = auth('sanctum')->id();

        $games = Game::query()
            ->where('is_active', true)
            ->with(['category', 'tags'])
            ->withCount('copies')
            ->withCount(['copies as available_copies_count' => function ($q) {
                $q->where('condition', '!=', 'LOCKED')
                  ->whereDoesntHave('activeLoans');
            }])
            ->withCount('reviews')
            ->when($request->category, fn($q, $slug) =>
                $q->whereHas('category', fn($q) => $q->where('slug', $slug))
            )
            ->when($request->tag, fn($q, $slug) =>
                $q->whereHas('tags', fn($q) => $q->where('slug', $slug))
            )
            ->when($request->search, fn($q, $search) =>
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'ilike', "%{$search}%")
                      ->orWhere('description', 'ilike', "%{$search}%");
                })
            )
            ->when($request->difficulty, fn($q, $diff) => $q->where('difficulty', $diff))
            ->when($request->available, fn($q) =>
                $q->whereHas('copies', fn($q) =>
                    $q->where('condition', '!=', 'LOCKED')->whereDoesntHave('activeLoans')
                )
            )
            ->orderBy($request->get('sort', 'title'))
            ->paginate(24);

        // is_favorited Flag für eingeloggte User
        if ($userId) {
            $favoritedIds = \App\Models\Favorite::where('user_id', $userId)
                ->pluck('game_id')
                ->flip();

            $games->each(function ($game) use ($favoritedIds) {
                $game->is_favorited = $favoritedIds->has($game->id);
            });
        }

        return GameResource::collection($games);
    }

    public function show(Request $request, Game $game): GameResource|JsonResponse
    {
        if (!$game->is_active) {
            return response()->json(['message' => 'Spiel nicht gefunden.'], 404);
        }

        $userId = auth('sanctum')->id();

        $game->load(['category', 'tags', 'reviews.user', 'images']);
        $game->loadCount('copies');
        $game->loadCount(['copies as available_copies_count' => function ($q) {
            $q->where('condition', '!=', 'LOCKED')
              ->whereDoesntHave('activeLoans');
        }]);

        // Kopien mit Verfügbarkeitsstatus
        $game->load(['copies' => function ($q) {
            $q->with(['activeLoans' => function ($q) {
                $q->select('id', 'copy_id', 'due_date', 'status');
            }]);
        }]);

        if ($userId) {
            $game->is_favorited = \App\Models\Favorite::where('user_id', $userId)
                ->where('game_id', $game->id)
                ->exists();

            $game->already_borrowed = \App\Models\Loan::where('user_id', $userId)
                ->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])
                ->whereHas('copy', fn ($q) => $q->where('game_id', $game->id))
                ->exists();
        }

        return new GameResource($game);
    }
}
