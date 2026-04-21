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
            ->with(['category', 'tags', 'languages'])
            ->withCount('copies')
            ->withCount(['copies as available_copies_count' => function ($q) {
                $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])
                  ->whereDoesntHave('activeLoans');
            }])
            ->withCount('reviews')
            ->when($request->category, function ($q, $value) {
                $slugs = array_filter(explode(',', $value));
                $q->whereHas('category', function ($q) use ($slugs) {
                    $q->whereIn('slug', $slugs)
                      ->orWhereHas('parent', fn($q) => $q->whereIn('slug', $slugs));
                });
            })
            ->when($request->tag, fn($q, $slug) =>
                $q->whereHas('tags', fn($q) => $q->where('slug', $slug))
            )
            ->when($request->search, fn($q, $search) =>
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                })
            )
            ->when($request->difficulty, fn($q, $diff) => $q->where('difficulty', $diff))
            ->when($request->players, function ($q, $n) {
                $n = (int) $n;
                $q->where(function ($q) use ($n) {
                    $q->whereNull('min_players')->orWhere('min_players', '<=', $n);
                })->where(function ($q) use ($n) {
                    $q->whereNull('max_players')->orWhere('max_players', '>=', $n);
                });
            })
            ->when($request->duration, function ($q, $dur) {
                if ($dur === 'short')  $q->whereNotNull('duration_min')->where('duration_min', '<=', 30);
                if ($dur === 'medium') $q->whereNotNull('duration_min')->whereBetween('duration_min', [31, 90]);
                if ($dur === 'long')   $q->whereNotNull('duration_min')->where('duration_min', '>', 90);
            })
            ->when($request->language, fn($q, $langId) =>
                $q->whereHas('languages', fn($q) => $q->where('languages.id', (int) $langId))
            )
            ->when($request->min_age, fn($q, $age) =>
                $q->where(function ($q) use ($age) {
                    $q->whereNull('min_age')->orWhere('min_age', '<=', (int) $age);
                })
            )
            ->when($request->available, fn($q) =>
                $q->whereHas('copies', fn($q) =>
                    $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])->whereDoesntHave('activeLoans')
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

        $game->load(['category', 'tags', 'languages', 'reviews.user', 'images']);
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
