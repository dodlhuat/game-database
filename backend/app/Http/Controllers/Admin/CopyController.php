<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyRequest;
use App\Http\Resources\CopyResource;
use App\Models\Copy;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\TokenTransaction;
use App\Notifications\DepositForfeited;
use App\Notifications\DepositReleased;
use App\Notifications\ReservationAvailable;
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

    public function approve(Copy $copy): JsonResponse
    {
        if ($copy->condition !== 'REVIEW') {
            return response()->json(['message' => 'Kopie ist nicht im Status "Überprüfen".'], 422);
        }

        $setting      = LoanSetting::instance();
        $newCondition = $copy->resolveConditionFromBorrowCount($setting);
        $copy->update(['condition' => $newCondition]);

        $loan = $copy->loans()
            ->where('status', 'RETURNED')
            ->latest('returned_at')
            ->with(['user', 'copy.game'])
            ->first();

        if ($loan && $loan->deposit_tokens > 0) {
            $loan->user->decrement('tokens_blocked', $loan->deposit_tokens);
            TokenTransaction::create([
                'user_id'     => $loan->user_id,
                'loan_id'     => $loan->id,
                'type'        => 'DEPOSIT_RELEASE',
                'amount'      => $loan->deposit_tokens,
                'description' => "Kaution freigegeben: {$loan->copy->game->title}",
            ]);
            $loan->user->notify(new DepositReleased($loan));
        }

        $reservation = Reservation::where('game_id', $copy->game_id)
            ->orderBy('position')
            ->first();

        if ($reservation) {
            $reservation->user->notify(new ReservationAvailable($copy->game));
            $reservation->update(['notified_at' => now()]);
        }

        return response()->json([
            'message' => 'Kopie freigegeben.',
            'copy'    => new CopyResource($copy->load(['game', 'activeLoans'])),
        ]);
    }

    public function markDamaged(Request $request, Copy $copy): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($copy->condition !== 'REVIEW') {
            return response()->json(['message' => 'Kopie ist nicht im Status "Überprüfen".'], 422);
        }

        $copy->update([
            'condition' => 'DAMAGED',
            'notes'     => $request->notes ?? $copy->notes,
        ]);

        $loan = $copy->loans()
            ->where('status', 'RETURNED')
            ->latest('returned_at')
            ->with(['user', 'copy.game'])
            ->first();

        if ($loan && $loan->deposit_tokens > 0) {
            $loan->user->decrement('tokens_blocked', $loan->deposit_tokens);
            TokenTransaction::create([
                'user_id'     => $loan->user_id,
                'loan_id'     => $loan->id,
                'type'        => 'DEPOSIT_FORFEIT',
                'amount'      => -$loan->deposit_tokens,
                'description' => "Kaution einbehalten (Beschädigung): {$loan->copy->game->title}",
            ]);
            $loan->user->notify(new DepositForfeited($loan, $request->notes));
        }

        return response()->json(['message' => 'Kopie als beschädigt markiert.']);
    }
}
