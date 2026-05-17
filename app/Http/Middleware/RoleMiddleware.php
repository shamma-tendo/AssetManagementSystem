<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserRole;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        try {
            $userRole = UserRole::from($role);
        } catch (\ValueError $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role specified',
            ], 400);
        }

        if (!$request->user()->hasRole($userRole)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required_role' => $userRole->getDisplayName(),
                'current_role' => $request->user()->role->getDisplayName(),
            ], 403);
        }

        return $next($request);
    }
}
