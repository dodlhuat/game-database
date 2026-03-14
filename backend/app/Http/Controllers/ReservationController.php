<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Game;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with('game')
            ->orderBy('position')
            ->get();

        return ReservationResource::collection($reservations);
    }

    public function store(Request $request): JsonResponse|ReservationResource
    {
        $request->validate(['game_id' => ['required', 'integer', 'exists:games,id']]);

        $game = Game::findOrFail($request->game_id);

        $already = Reservation::where('game_id', $game->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Du stehst bereits auf der Warteliste.'], 422);
        }

        $position = Reservation::where('game_id', $game->id)->max('position') + 1;

        $reservation = Reservation::create([
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'position' => $position,
        ]);

        $reservation->load('game');

        return new ReservationResource($reservation);
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        // Positionen der Nachfolger verringern
        Reservation::where('game_id', $reservation->game_id)
            ->where('position', '>', $reservation->position)
            ->decrement('position');

        $reservation->delete();

        return response()->json(['message' => 'Reservierung entfernt.']);
    }
}
