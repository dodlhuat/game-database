<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PickupLocationRequest;
use App\Http\Resources\PickupLocationResource;
use App\Models\Loan;
use App\Models\PickupLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PickupLocationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PickupLocationResource::collection(
            $this->withOpenLoans(PickupLocation::query())->ordered()->get()
        );
    }

    public function store(PickupLocationRequest $request): PickupLocationResource
    {
        $data = $this->payload($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $maxOrder = PickupLocation::withTrashed()->max('sort_order');
        $data['sort_order'] = (is_numeric($maxOrder) ? (int) $maxOrder : 0) + 1;

        $location = PickupLocation::create($data);
        $location->setAttribute('open_loans_count', 0);

        return new PickupLocationResource($location);
    }

    public function update(PickupLocationRequest $request, PickupLocation $pickupLocation): JsonResponse|PickupLocationResource
    {
        $data = $this->payload($request);
        $data['is_active'] = $request->boolean('is_active', $pickupLocation->is_active);

        if ($pickupLocation->is_active && ! $data['is_active'] && ($blocked = $this->deactivationBlocker($pickupLocation))) {
            return $blocked;
        }

        $pickupLocation->update($data);

        return $this->resource($pickupLocation);
    }

    public function toggle(PickupLocation $pickupLocation): JsonResponse|PickupLocationResource
    {
        if ($pickupLocation->is_active && ($blocked = $this->deactivationBlocker($pickupLocation))) {
            return $blocked;
        }

        $pickupLocation->update(['is_active' => ! $pickupLocation->is_active]);

        return $this->resource($pickupLocation);
    }

    public function reorder(Request $request): Response
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:pickup_locations,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                PickupLocation::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return response()->noContent();
    }

    /** Soft delete — past loans keep pointing at the location. */
    public function destroy(PickupLocation $pickupLocation): JsonResponse|Response
    {
        if ($pickupLocation->is_active && ($blocked = $this->deactivationBlocker($pickupLocation))) {
            return $blocked;
        }

        if (! $pickupLocation->is_active && $pickupLocation->openLoanCount() > 0) {
            return $this->openLoansResponse($pickupLocation->openLoanCount());
        }

        $pickupLocation->delete();

        return response()->noContent();
    }

    /**
     * Members must always have somewhere to pick up and return games, and
     * games already out must still have a valid place to come back to.
     */
    private function deactivationBlocker(PickupLocation $location): ?JsonResponse
    {
        $othersActive = PickupLocation::active()->whereKeyNot($location->id)->exists();
        if (! $othersActive) {
            return response()->json([
                'message' => 'Es muss immer mindestens ein aktiver Abholort vorhanden sein.',
                'reason' => 'last_active_location',
            ], 422);
        }

        $open = $location->openLoanCount();
        if ($open > 0) {
            return $this->openLoansResponse($open);
        }

        return null;
    }

    private function openLoansResponse(int $open): JsonResponse
    {
        return response()->json([
            'message' => $open === 1
                ? 'Ein ausgeliehenes Spiel muss noch an diesen Ort zurück. Der Ort kann erst danach deaktiviert werden.'
                : "{$open} ausgeliehene Spiele müssen noch an diesen Ort zurück. Der Ort kann erst danach deaktiviert werden.",
            'reason' => 'open_loans',
            'open_loans_count' => $open,
        ], 422);
    }

    /** @return array<string, mixed> */
    private function payload(PickupLocationRequest $request): array
    {
        $data = $request->safe()->only(['name', 'address', 'opening_hours', 'notes', 'map_url']);
        foreach (['address', 'opening_hours', 'notes', 'map_url'] as $field) {
            $value = $data[$field] ?? null;
            $data[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return $data;
    }

    private function resource(PickupLocation $location): PickupLocationResource
    {
        /** @var PickupLocation $fresh */
        $fresh = $this->withOpenLoans(PickupLocation::query())->findOrFail($location->id);

        return new PickupLocationResource($fresh);
    }

    /**
     * @param  Builder<PickupLocation>  $query
     * @return Builder<PickupLocation>
     */
    private function withOpenLoans(Builder $query): Builder
    {
        return $query->withCount(['loans as open_loans_count' => fn ($q) => $q->whereIn('status', Loan::OPEN_STATUSES)]);
    }
}
