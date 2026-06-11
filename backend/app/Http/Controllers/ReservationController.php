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
        /** @var \App\Models\User $user */
        $user = $request->user();
        $reservations = $user->reservations()
            ->with('game')
            ->orderBy('position')
            ->get();

        return ReservationResource::collection($reservations);
    }

    public function store(Request $request): JsonResponse|ReservationResource
    {
        $request->validate(['game_id' => ['required', 'integer', 'exists:games,id']]);

        /** @var \App\Models\Game $game */
        $game = Game::findOrFail($request->game_id);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $already = Reservation::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Du stehst bereits auf der Warteliste.'], 422);
        }

        $position = Reservation::where('game_id', $game->id)->max('position') + 1;

        $reservation = Reservation::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'position' => $position,
        ]);

        $reservation->load('game');

        return new ReservationResource($reservation);
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if ($reservation->user_id !== $user->id) {
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
