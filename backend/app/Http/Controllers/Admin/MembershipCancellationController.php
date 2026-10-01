<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipCancellation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipCancellationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MembershipCancellation::with('user:id,name,email')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $query->paginate(25);

        return response()->json([
            'data' => $page->getCollection()->map(fn (MembershipCancellation $c) => [
                'id' => $c->id,
                'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name, 'email' => $c->user->email] : null,
                'previous_role' => $c->previous_role,
                'tokens_total' => $c->tokens_total,
                'bonus_tokens_forfeited' => $c->bonus_tokens_forfeited,
                'refund_tokens' => $c->refund_tokens,
                'gross_cents' => $c->gross_cents,
                'fee_cents' => $c->fee_cents,
                'refund_cents' => $c->refund_cents,
                'account_holder' => $c->account_holder,
                'iban' => $c->iban,
                'reason' => $c->reason,
                'status' => $c->status,
                'refunded_at' => $c->refunded_at,
                'created_at' => $c->created_at,
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function markRefunded(MembershipCancellation $cancellation): JsonResponse
    {
        if ($cancellation->status !== 'REQUESTED') {
            return response()->json(['message' => 'Diese Kündigung ist nicht offen.'], 422);
        }

        $cancellation->update(['status' => 'REFUNDED', 'refunded_at' => now()]);

        return response()->json(['message' => 'Als überwiesen markiert.']);
    }
}
