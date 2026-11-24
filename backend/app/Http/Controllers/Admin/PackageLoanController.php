<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageLoanResource;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PackageLoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $loans = PackageLoan::query()
            ->with(['package', 'user', 'loans.copy.game'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->pickup_location_id, fn ($q, $id) => $q->where('pickup_location_id', $id))
            ->orderByDesc('created_at')
            ->paginate(20);

        return PackageLoanResource::collection($loans);
    }

    /** Moves the package — and every game in it — to another pickup location. */
    public function updatePickupLocation(Request $request, PackageLoan $packageLoan): JsonResponse|PackageLoanResource
    {
        $request->validate([
            'pickup_location_id' => ['required', 'integer', Rule::exists('pickup_locations', 'id')->whereNull('deleted_at')->where('is_active', true)],
        ]);

        if (! in_array($packageLoan->status, ['ACTIVE', 'OVERDUE'])) {
            return response()->json(['message' => 'Dieses Paket ist bereits zurückgegeben.'], 422);
        }

        $locationId = $request->integer('pickup_location_id');
        /** @var PickupLocation $location */
        $location = PickupLocation::findOrFail($locationId);
        /** @var User $borrower */
        $borrower = $packageLoan->user;
        if (! $location->isAccessibleTo($borrower)) {
            return response()->json(['message' => "Dieser Ort ist für {$borrower->name} nicht freigegeben."], 422);
        }

        DB::transaction(function () use ($packageLoan, $locationId) {
            $packageLoan->update(['pickup_location_id' => $locationId]);
            $packageLoan->loans()->update(['pickup_location_id' => $locationId]);
        });

        return new PackageLoanResource($packageLoan->load(['package', 'user', 'loans.copy.game', 'pickupLocation']));
    }
}
