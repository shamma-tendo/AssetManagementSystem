<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['department', 'location']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username|regex:/^[a-zA-Z0-9_]+$/',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['admin', 'manager', 'technician', 'auditor', 'viewer'])],
            'department_id' => 'nullable|uuid|exists:departments,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['password'] = Hash::make($validated['password']);

        // Only admins can create admin users
        if ($validated['role'] === 'admin' && !$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can create admin users',
            ], 403);
        }

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load(['department', 'location']),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['department', 'location', 'createdAssets', 'updatedAssets', 'managedDepartments']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id), 'regex:/^[a-zA-Z0-9_]+$/'],
            'phone' => 'sometimes|nullable|string|max:20',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'manager', 'technician', 'auditor', 'viewer'])],
            'department_id' => 'sometimes|nullable|uuid|exists:departments,id',
            'location_id' => 'sometimes|nullable|uuid|exists:locations,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Only admins can change role to admin
        if (isset($validated['role']) && $validated['role'] === 'admin' && !$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can assign admin role',
            ], 403);
        }

        // Users cannot change their own role or deactivate themselves
        if ($user->id === $request->user()->id) {
            if (isset($validated['role']) && $validated['role'] !== $user->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own role',
                ], 403);
            }
            if (isset($validated['is_active']) && !$validated['is_active']) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account',
                ], 403);
            }
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load(['department', 'location']),
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        // Users cannot delete themselves
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        // Only admins can delete other admins
        if ($user->hasRole(UserRole::ADMIN) && !request()->user()->hasRole(UserRole::ADMIN)) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete admin users',
            ], 403);
        }

        // Check if user has created assets or managed departments
        if ($user->createdAssets()->exists() || $user->managedDepartments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user with associated assets or managed departments',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        // Users cannot deactivate themselves
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own status',
            ], 403);
        }

        // Only admins can deactivate other admins
        if ($user->hasRole(UserRole::ADMIN) && !request()->user()->hasRole(UserRole::ADMIN)) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can change admin status',
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => "User " . ($user->is_active ? 'activated' : 'deactivated') . " successfully",
            'data' => $user->load(['department', 'location']),
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Only admins can reset admin passwords
        if ($user->hasRole(UserRole::ADMIN) && !$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can reset admin passwords',
            ], 403);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Revoke all tokens (force re-login)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'manager' => User::where('role', 'manager')->count(),
                'technician' => User::where('role', 'technician')->count(),
                'auditor' => User::where('role', 'auditor')->count(),
                'viewer' => User::where('role', 'viewer')->count(),
            ],
            'recent_logins' => User::whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->limit(10)
                ->get(['id', 'first_name', 'last_name', 'email', 'last_login_at']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get users by role.
     */
    public function byRole(string $role): JsonResponse
    {
        if (!in_array($role, ['admin', 'manager', 'technician', 'auditor', 'viewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role',
            ], 422);
        }

        $users = User::where('role', $role)
            ->with(['department', 'location'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
