<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Favorite;
use App\Models\Game;
use App\Services\SmartSearch\QueryParser;
use App\Services\SmartSearch\SimilarityScorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameSearchController extends Controller
{
    public function __construct(
        private readonly QueryParser $parser,
        private readonly SimilarityScorer $scorer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json(['data' => [], 'meta' => ['intent' => 'EMPTY']]);
        }

        $parsed = $this->parser->parse($q);
        $userId = auth('sanctum')->id();

        $baseQuery = Game::query()
            ->where('is_active', true)
            ->with(['category', 'tags', 'languages'])
            ->withCount('copies')
            ->withCount(['copies as available_copies_count' => function ($q): void {
                $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])
                    ->whereDoesntHave('activeLoans');
            }])
            ->withCount('reviews');

        $meta = [];

        switch ($parsed['intent']) {
            case 'SIMILARITY':
                $ref = $this->parser->findGameByTitle($parsed['reference'] ?? '');

                if ($ref === null) {
                    $term = $parsed['reference'] ?? '';
                    $games = $baseQuery
                        ->where('title', 'like', "%{$term}%")
                        ->orderBy('title')
                        ->get();
                    $meta = ['intent' => 'FULLTEXT', 'query' => $term];
                } else {
                    $reference = Game::where('is_active', true)
                        ->with('tags')
                        ->findOrFail($ref->id);
                    $all = $baseQuery->get();
                    $games = $this->scorer->score($reference, $all);
                    $meta = [
                        'intent' => 'SIMILARITY',
                        'reference_title' => $reference->title,
                        'reference_slug' => $reference->slug,
                    ];
                }
                break;

            case 'CATEGORY':
                $slug = $parsed['slug'] ?? '';
                $games = $baseQuery
                    ->whereHas('category', fn ($q) => $q->where('slug', $slug)
                        ->orWhereHas('parent', fn ($q) => $q->where('slug', $slug)))
                    ->orderBy('title')
                    ->get();
                $meta = ['intent' => 'CATEGORY', 'slug' => $slug];
                break;

            case 'TAG':
                $slug = $parsed['slug'] ?? '';
                $games = $baseQuery
                    ->whereHas('tags', fn ($q) => $q->where('slug', $slug))
                    ->orderBy('title')
                    ->get();
                $meta = ['intent' => 'TAG', 'slug' => $slug];
                break;

            default:
                $term = $parsed['query'] ?? $q;
                $games = $baseQuery
                    ->where(function ($query) use ($term): void {
                        $query->where('title', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    })
                    ->orderBy('title')
                    ->get();
                $meta = ['intent' => 'FULLTEXT'];
                break;
        }

        if ($userId) {
            $favoritedIds = Favorite::where('user_id', $userId)->pluck('game_id')->flip();
            $games->each(fn (Game $game) => $game->is_favorited = $favoritedIds->has($game->id));
        }

        return response()->json([
            'data' => GameResource::collection($games),
            'meta' => $meta,
        ]);
    }
}
