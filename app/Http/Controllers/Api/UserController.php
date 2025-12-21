<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['farm', 'shop', 'roles']);

        if ($request->has('role')) {
            $query->role($request->role);
        }

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if (!$request->boolean('include_inactive')) {
            $query->active();
        }

        $users = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'farm_id' => ['nullable', 'exists:farms,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'role' => ['required', 'in:farm_staff,shop_staff,manager,customer'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'farm_id' => $validated['farm_id'] ?? null,
            'shop_id' => $validated['shop_id'] ?? null,
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user->load(['farm', 'shop', 'roles']),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load(['farm', 'shop', 'roles', 'permissions']),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'farm_id' => ['nullable', 'exists:farms,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user->fresh(['farm', 'shop', 'roles']),
        ]);
    }

    /**
     * Update user's password.
     */
    public function updatePassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => 'User status updated.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:farm_staff,shop_staff,manager,customer'],
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'data' => $user->fresh(['roles']),
        ]);
    }
}
