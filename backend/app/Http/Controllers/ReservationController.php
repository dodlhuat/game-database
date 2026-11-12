<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Game;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\TokenWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct(private TokenWallet $wallet) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
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

        /** @var Game $game */
        $game = Game::findOrFail($request->game_id);

        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isMember()) {
            return response()->json([
                'message' => 'Eine aktive Mitgliedschaft ist erforderlich um Spiele zu reservieren.',
                'reason' => 'membership_required',
            ], 403);
        }

        // The fee is charged once and not refunded when the reservation is cancelled.
        $cost = $user->isAdmin() ? 0 : LoanSetting::instance()->reservation_cost;
        if (! $user->hasEnoughFreeTokens($cost)) {
            return response()->json([
                'message' => "Nicht genug Token. Reservieren kostet {$cost} Token.",
                'reason' => 'insufficient_tokens',
                'reservation_cost' => $cost,
            ], 402);
        }

        return DB::transaction(function () use ($game, $user, $cost): JsonResponse|ReservationResource {
            // Lock the game so a double click neither queues twice nor charges twice
            Game::whereKey($game->id)->lockForUpdate()->first();

            $already = Reservation::where('game_id', $game->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($already) {
                return response()->json(['message' => 'Du stehst bereits auf der Warteliste.'], 422);
            }

            $maxPosition = Reservation::where('game_id', $game->id)->max('position');
            $position = (is_numeric($maxPosition) ? (int) $maxPosition : 0) + 1;

            $reservation = Reservation::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position' => $position,
            ]);

            if ($cost > 0) {
                $this->wallet->spend($user, $cost);
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'RESERVATION',
                    'amount' => -$cost,
                    'description' => "Reservierung: {$game->title}",
                ]);
            }

            $reservation->load('game');

            return new ReservationResource($reservation);
        });
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        /** @var User $user */
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
