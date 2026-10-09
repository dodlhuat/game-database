<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Favorite;
use App\Models\Game;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GameController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = auth('sanctum')->id();

        $sort = $request->string('sort')->toString();

        $games = Game::query()
            ->where('is_active', true)
            ->with(['tags', 'mechanics', 'languages'])
            ->withCount('copies')
            ->withCount(['copies as available_copies_count' => function ($q) {
                $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])
                    ->whereDoesntHave('activeLoans');
            }])
            ->withCount('reviews')
            ->when($request->string('mechanic')->toString(), function ($q, string $value) {
                $slugs = array_filter(explode(',', $value));
                $q->whereHas('mechanics', fn ($q) => $q->whereIn('slug', $slugs));
            })
            ->when($request->string('tag')->toString(), fn ($q, string $slug) => $q->whereHas('tags', fn ($q) => $q->where('slug', $slug))
            )
            ->when($request->string('search')->toString(), fn ($q, string $search) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            )
            ->when($request->string('difficulty')->toString(), fn ($q, string $diff) => $q->where('difficulty', $diff))
            ->when($request->integer('players'), function ($q, int $n) {
                $q->where(function ($q) use ($n) {
                    $q->whereNull('min_players')->orWhere('min_players', '<=', $n);
                })->where(function ($q) use ($n) {
                    $q->whereNull('max_players')->orWhere('max_players', '>=', $n);
                });
            })
            ->when($request->string('duration')->toString(), function ($q, string $dur) {
                if ($dur === 'short') {
                    $q->whereNotNull('duration_min')->where('duration_min', '<=', 30);
                }
                if ($dur === 'medium') {
                    $q->whereNotNull('duration_min')->whereBetween('duration_min', [31, 90]);
                }
                if ($dur === 'long') {
                    $q->whereNotNull('duration_min')->where('duration_min', '>', 90);
                }
            })
            ->when($request->integer('language'), fn ($q, int $langId) => $q->whereHas('languages', fn ($q) => $q->where('languages.id', $langId))
            )
            ->when($request->filled('min_age_from') || $request->filled('min_age_to'), function ($q) use ($request) {
                $from = $request->filled('min_age_from') ? $request->integer('min_age_from') : null;
                $to = $request->filled('min_age_to') ? $request->integer('min_age_to') : null;
                $q->where(function ($q) use ($from, $to) {
                    $q->whereNull('min_age')->orWhere(function ($q) use ($from, $to) {
                        if ($from !== null) {
                            $q->where('min_age', '>=', $from);
                        }
                        if ($to !== null) {
                            $q->where('min_age', '<=', $to);
                        }
                    });
                });
            })
            ->when($request->boolean('available'), fn ($q) => $q->whereHas('copies', fn ($q) => $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])->whereDoesntHave('activeLoans')
            )
            )
            ->orderBy(in_array($sort, ['title', 'created_at', 'min_age', 'duration_min', 'difficulty'], true) ? $sort : 'title')
            ->paginate(24);

        // is_favorited Flag für eingeloggte User
        if ($userId) {
            $favoritedIds = Favorite::where('user_id', $userId)
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
        if (! $game->is_active) {
            return response()->json(['message' => 'Spiel nicht gefunden.'], 404);
        }

        $userId = auth('sanctum')->id();

        $game->load(['tags', 'mechanics', 'languages', 'reviews.user', 'images']);
        $game->loadCount('copies');
        $game->loadCount(['copies as available_copies_count' => function ($q) {
            $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])
                ->whereDoesntHave('activeLoans');
        }]);

        // Kopien mit Verfügbarkeitsstatus
        $game->load(['copies' => function ($q) {
            $q->with(['activeLoans' => function ($q) {
                $q->select('id', 'copy_id', 'due_date', 'status');
            }]);
        }]);

        if ($userId) {
            $game->is_favorited = Favorite::where('user_id', $userId)
                ->where('game_id', $game->id)
                ->exists();

            $game->already_borrowed = Loan::where('user_id', $userId)
                ->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])
                ->whereHas('copy', fn ($q) => $q->where('game_id', $game->id))
                ->exists();
        }

        return new GameResource($game);
    }
}
