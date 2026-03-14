<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request): JsonResponse|ReviewResource
    {
        $already = Review::where('game_id', $request->game_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Du hast dieses Spiel bereits bewertet.'], 422);
        }

        $review = Review::create([
            'game_id' => $request->game_id,
            'loan_id' => $request->loan_id,
            'user_id' => $request->user()->id,
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load('user');

        return new ReviewResource($review);
    }

    public function update(Request $request, Review $review): JsonResponse|ReviewResource
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        $request->validate([
            'rating'  => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update($request->only('rating', 'comment'));
        $review->load('user');

        return new ReviewResource($review);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Bewertung gelöscht.']);
    }
}
