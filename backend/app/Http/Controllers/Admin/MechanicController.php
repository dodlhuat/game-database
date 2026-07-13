<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MechanicResource;
use App\Models\Mechanic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class MechanicController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MechanicResource::collection(Mechanic::withCount('games')->orderBy('name')->get());
    }

    public function store(Request $request): MechanicResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:mechanics,name'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        return new MechanicResource(Mechanic::create($data));
    }

    public function update(Request $request, Mechanic $mechanic): MechanicResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:mechanics,name,'.$mechanic->id],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $mechanic->update($data);

        return new MechanicResource($mechanic->loadCount('games'));
    }

    public function destroy(Mechanic $mechanic): JsonResponse
    {
        $mechanic->delete();

        return response()->json(['message' => 'Mechanik gelöscht.']);
    }
}
