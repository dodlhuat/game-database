<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class DamageReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = DamageReport::with(['loan.copy.game', 'user'])
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->loan_id, fn($q, $id) => $q->where('loan_id', $id))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($reports);
    }
}
