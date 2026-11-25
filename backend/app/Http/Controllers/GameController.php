<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Favorite;
use App\Models\Game;
use App\Models\Loan;
use App\Support\GameFilters;
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
            ->when($request->string('search')->toString(), fn ($q, string $search) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            )
            ->tap(fn ($q) => GameFilters::apply($q, $request))
            // "Neueste" means newest first; everything else reads A→Z / low→high
            ->orderBy(
                in_array($sort, ['title', 'created_at', 'min_age', 'duration_min', 'difficulty'], true) ? $sort : 'title',
                $sort === 'created_at' ? 'desc' : 'asc',
            )
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
