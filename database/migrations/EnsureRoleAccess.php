<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRoleAccess
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        if ($user->status === 'pending') {
            return redirect()->route('login')->with('error', 'Your account is awaiting approval from your CEO.');
        }

        if (!in_array($user->role, $roles)) {
            abort(403, 'Unauthorized access to this dashboard.');
        }

        return $next($request);
    }
}