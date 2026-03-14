<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyRequest;
use App\Http\Resources\CopyResource;
use App\Models\Copy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CopyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $copies = Copy::with(['game', 'activeLoans'])
            ->when($request->game_id, fn($q, $id) => $q->where('game_id', $id))
            ->when($request->condition, fn($q, $c) => $q->where('condition', $c))
            ->orderBy('game_id')
            ->paginate(50);

        return CopyResource::collection($copies);
    }

    public function store(CopyRequest $request): CopyResource
    {
        $data = $request->validated();
        $data['qr_code'] = $data['qr_code'] ?? strtoupper(Str::random(8));

        $copy = Copy::create($data);
        $copy->load(['game', 'activeLoans']);

        return new CopyResource($copy);
    }

    public function show(Copy $copy): CopyResource
    {
        return new CopyResource($copy->load(['game', 'activeLoans']));
    }

    public function update(CopyRequest $request, Copy $copy): CopyResource
    {
        $copy->update($request->validated());

        return new CopyResource($copy->load(['game', 'activeLoans']));
    }

    public function destroy(Copy $copy): JsonResponse
    {
        if ($copy->activeLoans()->exists()) {
            return response()->json(['message' => 'Kopie ist aktuell ausgeliehen.'], 422);
        }

        $copy->delete();

        return response()->json(['message' => 'Kopie gelöscht.']);
    }
}
