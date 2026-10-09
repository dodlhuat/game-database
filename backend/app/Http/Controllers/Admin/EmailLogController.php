<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = EmailLog::query()
            ->with('user:id,name,email')
            ->when($request->template_key, fn ($q, $key) => $q->where('template_key', $key))
            ->when($request->string('date_from')->toString(), fn ($q, string $date) => $q->whereDate('sent_at', '>=', $date))
            ->when($request->string('date_to')->toString(), fn ($q, string $date) => $q->whereDate('sent_at', '<=', $date))
            ->orderByDesc('sent_at')
            ->paginate(50);

        return response()->json($logs);
    }
}
