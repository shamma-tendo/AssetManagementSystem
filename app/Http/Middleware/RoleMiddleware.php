<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserRole;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Accepts a minimum role — any user whose role level is >= that level is allowed.
     */
    public function handle(Request $request, Closure $next, string $minRole): mixed
    {
        if (!$request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }
            return redirect()->route('login');
        }

        try {
            $required = UserRole::from($minRole);
        } catch (\ValueError $e) {
            abort(400, 'Invalid role specified in route definition.');
        }

        $userLevel     = $request->user()->role->getLevel();
        $requiredLevel = $required->getLevel();

        if ($userLevel < $requiredLevel) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'       => false,
                    'message'       => 'Insufficient permissions.',
                    'required_role' => $required->getDisplayName(),
                    'your_role'     => $request->user()->role->getDisplayName(),
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action. Required role: ' . $required->getDisplayName() . '.');
        }

        return $next($request);
    }
}
