<?php

namespace App\Http\Controllers;

use App\Http\Requests\DonationRequest;
use App\Mail\DonationMail;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class DonationController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function store(DonationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // compressForAttachment() already base64-encodes its returned contents:
        // queue payloads are JSON-encoded, so raw binary must survive serialization
        // (see DonationMail::attachments()).
        $images = collect($request->file('images', []))
            ->map(fn ($file) => $this->imageUpload->compressForAttachment($file))
            ->all();

        $adminEmails = User::where('role', 'ADMIN')->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails->all())->queue(
                new DonationMail($user->name, $user->email, $request->validated('games'), $images)
            );
        }

        return response()->json([
            'message' => 'Danke, du Legende! Wir melden uns bei dir, um die Übergabe zu organisieren.',
        ], 201);
    }
}
