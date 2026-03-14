<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExtensionResource;
use App\Models\Extension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExtensionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $extensions = Extension::with(['loan.copy.game', 'loan.user'])
            ->when($request->status ?? 'PENDING', fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('requested_at')
            ->paginate(25);

        return ExtensionResource::collection($extensions);
    }

    public function approve(Request $request, Extension $extension): JsonResponse|ExtensionResource
    {
        if ($extension->status !== 'PENDING') {
            return response()->json(['message' => 'Antrag wurde bereits bearbeitet.'], 422);
        }

        $extension->update([
            'status'     => 'APPROVED',
            'admin_note' => $request->admin_note,
        ]);

        $extension->loan->update([
            'due_date' => $extension->requested_due_date,
            'status'   => 'EXTENDED',
        ]);

        return new ExtensionResource($extension->load(['loan.copy.game', 'loan.user']));
    }

    public function reject(Request $request, Extension $extension): JsonResponse|ExtensionResource
    {
        if ($extension->status !== 'PENDING') {
            return response()->json(['message' => 'Antrag wurde bereits bearbeitet.'], 422);
        }

        $extension->update([
            'status'     => 'REJECTED',
            'admin_note' => $request->admin_note,
        ]);

        return new ExtensionResource($extension->load(['loan.copy.game', 'loan.user']));
    }
}
