<?php

namespace App\Http\Controllers;

use App\Http\Resources\PickupLocationResource;
use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PickupLocationController extends Controller
{
    /** Active pickup locations open to this user, in the order members get to choose from. */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return PickupLocationResource::collection(
            PickupLocation::active()->accessibleTo($user)->ordered()->get()
        );
    }
}
