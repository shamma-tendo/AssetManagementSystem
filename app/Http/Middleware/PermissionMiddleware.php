<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        if (!$request->user()->canPerform($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required_permission' => $permission,
                'current_role' => $request->user()->role->getDisplayName(),
            ], 403);
        }

        return $next($request);
    }
}
