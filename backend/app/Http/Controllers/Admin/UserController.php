<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\UserApproved;
use App\Notifications\UserRejected;
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
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->role, fn($q, $role) => $q->where('role', $role))
            ->when($request->search, fn($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'in:USER,MEMBER,ADMIN'],
            'status'   => ['required', 'in:PENDING,ACTIVE,REJECTED,SUSPENDED'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'status'   => $data['status'],
        ]);

        return new UserResource($user);
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', 'in:USER,MEMBER,ADMIN'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $update = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return new UserResource($user);
    }

    public function approve(User $user): JsonResponse
    {
        $user->update(['status' => 'ACTIVE']);
        $user->notify(new UserApproved());

        return response()->json(['message' => 'Mitglied freigeschaltet.', 'user' => new UserResource($user)]);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $user->update(['status' => 'REJECTED']);
        $user->notify(new UserRejected($request->reason));

        return response()->json(['message' => 'Mitglied abgelehnt.', 'user' => new UserResource($user)]);
    }

    public function suspend(User $user): JsonResponse
    {
        $user->update(['status' => 'SUSPENDED']);

        return response()->json(['message' => 'Mitglied gesperrt.', 'user' => new UserResource($user)]);
    }
}
