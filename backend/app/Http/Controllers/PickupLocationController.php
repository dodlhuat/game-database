<?php

namespace App\Http\Controllers;

use App\Http\Resources\PickupLocationResource;
use App\Models\PickupLocation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PickupLocationController extends Controller
{
    /** Active pickup locations, in the order members get to choose from. */
    public function index(): AnonymousResourceCollection
    {
        return PickupLocationResource::collection(PickupLocation::active()->ordered()->get());
    }
}
