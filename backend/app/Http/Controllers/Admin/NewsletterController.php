<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index(): JsonResponse
    {
        $newsletters = Newsletter::with('sender')
            ->orderByDesc('sent_at')
            ->paginate(20);

        return response()->json($newsletters);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body'    => ['required', 'string'],
        ]);

        $recipients = User::where('newsletter_opt_in', true)
            ->where('status', 'ACTIVE')
            ->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(
                new NewsletterMail($validated['subject'], $validated['body'])
            );
        }

        $newsletter = Newsletter::create([
            'subject'         => $validated['subject'],
            'body'            => $validated['body'],
            'sent_at'         => now(),
            'recipient_count' => $recipients->count(),
            'sent_by'         => $request->user()->id,
        ]);

        return response()->json($newsletter->load('sender'), 201);
    }
}
