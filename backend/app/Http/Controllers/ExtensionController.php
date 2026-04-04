<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreExtensionRequest;
use App\Http\Resources\ExtensionResource;
use App\Models\Loan;
use App\Models\LoanSetting;
use Illuminate\Http\JsonResponse;

class ExtensionController extends Controller
{
    public function store(StoreExtensionRequest $request, Loan $loan): JsonResponse|ExtensionResource
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        if (!in_array($loan->status, ['ACTIVE', 'OVERDUE'])) {
            return response()->json(['message' => 'Für diese Ausleihe kann keine Verlängerung beantragt werden.'], 422);
        }

        $pending = $loan->extensions()->where('status', 'PENDING')->exists();
        if ($pending) {
            return response()->json(['message' => 'Es gibt bereits einen offenen Verlängerungsantrag.'], 422);
        }

        $setting = LoanSetting::instance();
        $usedExtensions = $loan->extensions()->where('status', '!=', 'REJECTED')->count();
        if ($usedExtensions >= $setting->max_extensions) {
            return response()->json(['message' => "Diese Ausleihe kann nicht weiter verlängert werden (Maximum: {$setting->max_extensions})."], 422);
        }

        $extension = $loan->extensions()->create([
            'requested_at'       => now(),
            'requested_due_date' => $request->requested_due_date,
            'status'             => 'PENDING',
        ]);

        return new ExtensionResource($extension);
    }
}
