<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PickupLocationRequest;
use App\Http\Resources\PickupLocationResource;
use App\Models\Loan;
use App\Models\PickupLocation;
use App\Models\User;
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

    /** Active locations this user may use — what the move-loan menu offers for their loans. */
    public function forUser(User $user): AnonymousResourceCollection
    {
        return PickupLocationResource::collection(
            PickupLocation::active()->regular()->accessibleTo($user)->ordered()->get()
        );
    }

    public function store(PickupLocationRequest $request): JsonResponse
    {
        $data = $this->payload($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_personal'] = $request->boolean('is_personal');
        $maxOrder = PickupLocation::withTrashed()->max('sort_order');
        $data['sort_order'] = (is_numeric($maxOrder) ? (int) $maxOrder : 0) + 1;

        $location = DB::transaction(function () use ($data, $request) {
            $location = PickupLocation::create($data);
            $this->syncUsers($request, $location);

            return $location;
        });

        return $this->resource($location)->response()->setStatusCode(201);
    }

    public function update(PickupLocationRequest $request, PickupLocation $pickupLocation): JsonResponse|PickupLocationResource
    {
        $data = $this->payload($request);
        $data['is_active'] = $request->boolean('is_active', $pickupLocation->is_active);
        $data['is_personal'] = $request->boolean('is_personal', $pickupLocation->is_personal);

        if ($pickupLocation->is_active && ! $data['is_active'] && ($blocked = $this->deactivationBlocker($pickupLocation))) {
            return $blocked;
        }
        if ($data['is_personal'] !== $pickupLocation->is_personal && ($blocked = $this->personalSwitchBlocker($pickupLocation, $data['is_personal']))) {
            return $blocked;
        }

        DB::transaction(function () use ($pickupLocation, $data, $request) {
            $pickupLocation->update($data);
            $this->syncUsers($request, $pickupLocation);
        });

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

        if ($pickupLocation->events()->whereDate('date', '>=', today())->exists()) {
            return response()->json([
                'message' => 'An diesem Ort gibt es noch kommende Abholtermine. Bitte diese zuerst verlegen oder löschen.',
                'reason' => 'upcoming_pickup_events',
            ], 422);
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
        // Personal locations only work with events, so a regular one must stay
        if (! $location->is_personal && ! $this->otherRegularActiveExists($location)) {
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

    private function otherRegularActiveExists(PickupLocation $location): bool
    {
        return PickupLocation::active()->regular()->whereKeyNot($location->id)->exists();
    }

    /**
     * Turning personal on takes the place out of the regular choice, so one
     * regular location has to remain. Turning it off would strand the
     * upcoming pickup events held there.
     */
    private function personalSwitchBlocker(PickupLocation $location, bool $toPersonal): ?JsonResponse
    {
        if ($toPersonal && $location->is_active && ! $this->otherRegularActiveExists($location)) {
            return response()->json([
                'message' => 'Es muss immer mindestens ein regulärer aktiver Abholort bleiben.',
                'reason' => 'last_active_location',
            ], 422);
        }

        if (! $toPersonal && $location->events()->whereDate('date', '>=', today())->exists()) {
            return response()->json([
                'message' => 'An diesem Ort gibt es noch kommende Abholtermine. Bitte diese zuerst verlegen oder löschen.',
                'reason' => 'upcoming_pickup_events',
            ], 422);
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

    /** Only touches the assignment when the form sent it, so other callers keep it. */
    private function syncUsers(PickupLocationRequest $request, PickupLocation $location): void
    {
        if ($request->has('user_ids')) {
            /** @var list<int> $ids */
            $ids = $request->validated('user_ids', []);
            $location->users()->sync($ids);
        }
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
        return $query
            ->with(['users' => fn ($q) => $q->orderBy('name')])
            ->withCount(['loans as open_loans_count' => fn ($q) => $q->whereIn('status', Loan::OPEN_STATUSES)]);
    }
}
