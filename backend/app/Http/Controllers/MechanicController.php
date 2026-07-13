<?php

namespace App\Http\Controllers;

use App\Http\Resources\MechanicResource;
use App\Models\Mechanic;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MechanicController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MechanicResource::collection(Mechanic::orderBy('name')->get());
    }
}
