<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\User;
use App\Notifications\UserApproved;
use App\Notifications\UserRejected;
use App\Notifications\UserUnsuspended;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            // ?deleted=1 lists the deleted users instead of the others
            ->when($request->boolean('deleted'), fn ($q) => $q->onlyTrashed())
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->string('search')->toString(), fn ($q, string $search) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function store(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:USER,SUPPORTER,MEMBER,ADMIN'],
            'status' => ['required', 'in:PENDING,ACTIVE,REJECTED,SUSPENDED'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        return new UserResource($user);
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:USER,SUPPORTER,MEMBER,ADMIN'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return new UserResource($user);
    }

    public function approve(User $user): JsonResponse
    {
        $user->update(['status' => 'ACTIVE']);
        $user->notify(new UserApproved);

        return response()->json(['message' => 'Mitglied freigeschaltet.', 'user' => new UserResource($user)]);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $user->update(['status' => 'REJECTED']);
        $user->notify(new UserRejected($request->filled('reason') ? $request->string('reason')->toString() : null));

        return response()->json(['message' => 'Mitglied abgelehnt.', 'user' => new UserResource($user)]);
    }

    public function suspend(User $user): JsonResponse
    {
        $user->update(['status' => 'SUSPENDED']);
        // End every login right away, not only at the next request check
        $user->tokens()->delete();

        return response()->json(['message' => 'Mitglied gesperrt.', 'user' => new UserResource($user)]);
    }

    /** Lifts a suspension. Anonymized accounts have no data left to come back to. */
    public function unsuspend(User $user): JsonResponse
    {
        if ($user->status !== 'SUSPENDED') {
            return response()->json(['message' => 'Dieses Konto ist nicht gesperrt.'], 422);
        }
        if ($user->anonymized_at) {
            return response()->json(['message' => 'Anonymisierte Konten können nicht entsperrt werden.', 'reason' => 'anonymized'], 422);
        }

        $user->update(['status' => 'ACTIVE']);
        $user->notify(new UserUnsuspended);

        return response()->json(['message' => 'Mitglied entsperrt.', 'user' => new UserResource($user)]);
    }

    /**
     * Soft-deletes a suspended or rejected user: gone from every list, login
     * and mailing, restorable for AnonymizeDeletedAccounts::GRACE_DAYS days.
     * Not while games are still out — they have to come back first.
     */
    public function destroy(User $user): JsonResponse
    {
        if (! in_array($user->status, ['SUSPENDED', 'REJECTED'], true)) {
            return response()->json(['message' => 'Nur gesperrte oder abgelehnte Konten können gelöscht werden.', 'reason' => 'not_blocked'], 422);
        }

        $running = Loan::where('user_id', $user->id)->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])->exists()
            || PackageLoan::where('user_id', $user->id)->whereIn('status', ['ACTIVE', 'OVERDUE'])->exists();
        if ($running) {
            return response()->json(['message' => 'Dieses Konto hat noch laufende Ausleihen. Sie müssen zuerst zurückgegeben werden.', 'reason' => 'has_loans'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Konto gelöscht.']);
    }

    /** Brings a deleted user back, still suspended: unlocking stays a separate decision. */
    public function restore(User $user): JsonResponse
    {
        if (! $user->trashed()) {
            return response()->json(['message' => 'Dieses Konto ist nicht gelöscht.'], 422);
        }
        if ($user->anonymized_at) {
            return response()->json(['message' => 'Dieses Konto wurde bereits anonymisiert und kann nicht wiederhergestellt werden.', 'reason' => 'anonymized'], 422);
        }

        $user->restore();
        $user->update(['status' => 'SUSPENDED']);

        return response()->json(['message' => 'Konto wiederhergestellt.', 'user' => new UserResource($user)]);
    }
}
