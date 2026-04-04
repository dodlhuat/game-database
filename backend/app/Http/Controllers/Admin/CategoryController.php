<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        // Return all categories (flat) with children loaded and game counts
        $categories = Category::withCount('games')
            ->with(['children' => fn ($q) => $q->withCount('games')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): CategoryResource
    {
        return new CategoryResource(Category::create($request->validated()));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('games')->load('children'));
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->load('children'));
    }

    public function destroy(Category $category): JsonResponse
    {
        // Move children to top level before deleting
        $category->children()->update(['parent_id' => null]);
        // Remove category assignment from games
        $category->games()->update(['category_id' => null]);
        $category->delete();

        return response()->json(['message' => 'Kategorie gelöscht.']);
    }
}
