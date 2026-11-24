<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    /**
     * All events, pickup events included — except those at a location the
     * user has no access to, which would only tease an unbookable date.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return EventResource::collection(
            Event::with('pickupLocation')
                ->where(fn (Builder $q) => $q
                    ->whereNull('pickup_location_id')
                    ->orWhereHas('pickupLocation', fn (Builder $l) => $l->accessibleTo($user)))
                ->orderBy('date')
                ->orderBy('time')
                ->get()
        );
    }
}
