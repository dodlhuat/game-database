<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\Loan;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DamageReportController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loans,id'],
            'description' => ['required', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp,bmp', 'max:8192'],
        ]);

        /** @var Loan $loan */
        $loan = Loan::findOrFail($validated['loan_id']);

        /** @var User $user */
        $user = $request->user();

        if ($loan->user_id !== $user->id) {
            return response()->json(['message' => 'Nicht autorisiert.'], 403);
        }

        if (! in_array($loan->status, ['ACTIVE', 'EXTENDED', 'OVERDUE'])) {
            return response()->json(['message' => 'Schadensmeldung nur für aktive Ausleihen möglich.'], 422);
        }

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->imageUpload->uploadDamagePhoto($request->file('photo'));
        }

        $report = DamageReport::create([
            'loan_id' => $loan->id,
            'user_id' => $user->id,
            'description' => $validated['description'],
            'photo_url' => $photoUrl,
        ]);

        return response()->json($report, 201);
    }
}
