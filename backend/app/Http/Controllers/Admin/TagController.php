<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection(Tag::withCount('games')->orderBy('name')->get());
    }

    public function store(Request $request): TagResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        return new TagResource(Tag::create($data));
    }

    public function update(Request $request, Tag $tag): TagResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name,' . $tag->id],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $tag->update($data);

        return new TagResource($tag->loadCount('games'));
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['message' => 'Tag gelöscht.']);
    }
}
