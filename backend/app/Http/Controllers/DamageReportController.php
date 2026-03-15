<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DamageReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_id'     => ['required', 'integer', 'exists:loans,id'],
            'description' => ['required', 'string', 'max:1000'],
            'photo_url'   => ['nullable', 'url'],
        ]);

        $loan = Loan::findOrFail($validated['loan_id']);

        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Nicht autorisiert.'], 403);
        }

        if (!in_array($loan->status, ['ACTIVE', 'EXTENDED', 'OVERDUE'])) {
            return response()->json(['message' => 'Schadensmeldung nur für aktive Ausleihen möglich.'], 422);
        }

        $report = DamageReport::create([
            'loan_id'     => $loan->id,
            'user_id'     => $request->user()->id,
            'description' => $validated['description'],
            'photo_url'   => $validated['photo_url'] ?? null,
        ]);

        return response()->json($report, 201);
    }
}
