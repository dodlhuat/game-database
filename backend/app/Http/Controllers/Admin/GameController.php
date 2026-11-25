<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GameRequest;
use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $games = Game::with(['tags', 'mechanics', 'languages'])
            ->withCount('copies')
            ->when($request->string('search')->toString(), fn ($q, string $s) => $q->whereLike('title', "%{$s}%")
            )
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active'))
            )
            ->orderBy('title')
            ->paginate(25);

        return GameResource::collection($games);
    }

    public function store(GameRequest $request): GameResource
    {
        // Only checked fields — the raw request could set cover_image_url directly
        $data = collect($request->validated())
            ->except(['cover_image', 'tag_ids', 'mechanic_ids', 'language_ids'])
            ->all();
        // An emptied deposit field means no deposit (the column is not nullable)
        if (array_key_exists('deposit_tokens', $data)) {
            $data['deposit_tokens'] = is_numeric($data['deposit_tokens']) ? (int) $data['deposit_tokens'] : 0;
        }
        $data['slug'] = $data['slug'] ?? Str::slug($request->string('title')->toString());

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->imageUpload->uploadGameCover($request->file('cover_image'));
        }

        $game = Game::create($data);

        if ($request->filled('tag_ids')) {
            $game->tags()->sync($request->array('tag_ids'));
        }

        if ($request->filled('mechanic_ids')) {
            $game->mechanics()->sync($request->array('mechanic_ids'));
        }

        $game->languages()->sync($request->array('language_ids'));

        $game->load(['tags', 'mechanics', 'languages']);

        return new GameResource($game);
    }

    public function show(Game $game): GameResource
    {
        $game->load(['tags', 'mechanics', 'languages', 'copies', 'images']);

        return new GameResource($game);
    }

    public function update(GameRequest $request, Game $game): GameResource
    {
        // Only checked fields — the raw request could set cover_image_url directly
        $data = collect($request->validated())
            ->except(['cover_image', 'tag_ids', 'mechanic_ids', 'language_ids'])
            ->all();
        // An emptied deposit field means no deposit (the column is not nullable)
        if (array_key_exists('deposit_tokens', $data)) {
            $data['deposit_tokens'] = is_numeric($data['deposit_tokens']) ? (int) $data['deposit_tokens'] : 0;
        }

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->imageUpload->uploadGameCover(
                $request->file('cover_image'),
                $game->cover_image_url,
            );
        }

        $game->update($data);

        if ($request->has('tag_ids')) {
            $game->tags()->sync($request->array('tag_ids'));
        }

        if ($request->has('mechanic_ids')) {
            $game->mechanics()->sync($request->array('mechanic_ids'));
        }

        if ($request->has('language_ids')) {
            $game->languages()->sync($request->array('language_ids'));
        }

        $game->load(['tags', 'mechanics', 'languages']);

        return new GameResource($game);
    }

    public function destroy(Game $game): JsonResponse
    {
        $this->imageUpload->delete($game->cover_image_url);

        foreach ($game->images as $image) {
            $this->imageUpload->delete($image->url);
        }

        $game->delete();

        return response()->json(['message' => 'Spiel gelöscht.']);
    }
}
