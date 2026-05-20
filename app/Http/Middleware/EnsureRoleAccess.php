<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has an organization (for multi-tenant routes)
        // If it's a household user, they bypass role checks or have household-specific checks.
        if ($user->isHouseholdOwner()) {
            if (in_array('household', $roles)) {
                return $next($request);
            }
            return redirect()->route($user->getDashboardRoute());
        }



        // Now check roles
        $userRoleName = $user->role ? $user->role->name : '';

        // Standardize "CEO" or "CFO" check under "executive" or matching specific roles
        // We'll support 'CEO', 'CFO', 'CEO/CFO', 'Executive', 'Admin', 'Asset Manager', 'Staff'
        $hasAccess = false;
        foreach ($roles as $role) {
            if (strtolower($role) === 'executive' || strtolower($role) === 'ceo' || strtolower($role) === 'cfo' || strtolower($role) === 'ceo/cfo') {
                if ($user->isExecutive()) {
                    $hasAccess = true;
                    break;
                }
            } else if (strtolower($role) === 'manager' || strtolower($role) === 'asset manager') {
                if ($user->isAssetManager()) {
                    $hasAccess = true;
                    break;
                }
            } else if (strtolower($role) === 'staff' || strtolower($role) === 'employee') {
                if ($user->isStaff() || $user->isEmployee()) {
                    $hasAccess = true;
                    break;
                }
            } else if (strcasecmp($userRoleName, $role) === 0) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return redirect()->route($user->getDashboardRoute());
        }

        return $next($request);
    }
}
