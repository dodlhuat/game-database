<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameImage;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameImageController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function store(Request $request, Game $game): JsonResponse
    {
        $request->validate([
            'images'   => ['required', 'array', 'max:10'],
            'images.*' => ['required', 'image', 'max:10240'],
        ]);

        $nextOrder = $game->images()->max('sort_order') + 1;
        $created = [];

        foreach ($request->file('images') as $file) {
            $url = $this->imageUpload->uploadGameImage($file);
            $created[] = $game->images()->create([
                'url'        => $url,
                'sort_order' => $nextOrder++,
            ]);
        }

        return response()->json([
            'images' => array_map(fn ($img) => ['id' => $img->id, 'url' => $img->url], $created),
        ]);
    }

    public function destroy(Game $game, GameImage $image): JsonResponse
    {
        if ($image->game_id !== $game->id) {
            return response()->json(['message' => 'Nicht gefunden.'], 404);
        }

        $this->imageUpload->deleteByUrl($image->url);
        $image->delete();

        return response()->json(['message' => 'Bild gelöscht.']);
    }
}
