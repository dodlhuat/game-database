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
        $games = Game::with(['category', 'tags'])
            ->withCount('copies')
            ->when($request->search, fn($q, $s) =>
                $q->where('title', 'like', "%{$s}%")
            )
            ->when($request->has('is_active'), fn($q) =>
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->orderBy('title')
            ->paginate(25);

        return GameResource::collection($games);
    }

    public function store(GameRequest $request): GameResource
    {
        $data = $request->except(['cover_image', 'tag_ids']);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->imageUpload->uploadGameCover($request->file('cover_image'));
        }

        $game = Game::create($data);

        if ($request->filled('tag_ids')) {
            $game->tags()->sync($request->tag_ids);
        }

        $game->load(['category', 'tags']);

        return new GameResource($game);
    }

    public function show(Game $game): GameResource
    {
        $game->load(['category', 'tags', 'copies', 'images']);

        return new GameResource($game);
    }

    public function update(GameRequest $request, Game $game): GameResource
    {
        $data = $request->except(['cover_image', 'tag_ids']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->imageUpload->uploadGameCover(
                $request->file('cover_image'),
                $game->cover_image_url,
            );
        }

        $game->update($data);

        if ($request->has('tag_ids')) {
            $game->tags()->sync($request->tag_ids ?? []);
        }

        $game->load(['category', 'tags']);

        return new GameResource($game);
    }

    public function destroy(Game $game): JsonResponse
    {
        if ($game->cover_image_url) {
            $this->imageUpload->deleteByUrl($game->cover_image_url);
        }

        foreach ($game->images as $image) {
            $this->imageUpload->deleteByUrl($image->url);
        }

        $game->delete();

        return response()->json(['message' => 'Spiel gelöscht.']);
    }
}
