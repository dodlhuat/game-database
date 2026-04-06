<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageLoanResource;
use App\Models\PackageLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PackageLoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $loans = PackageLoan::query()
            ->with(['package', 'user', 'loans.copy.game'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return PackageLoanResource::collection($loans);
    }
}
