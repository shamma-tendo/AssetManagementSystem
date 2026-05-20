<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        // No services to register.
    }

    /**
     * Boot any authentication / authorization services.
     */
    public function boot(): void
    {
        // Admin bypass for all abilities
        Gate::before(function (User $user) {
            $role = strtolower($user->role ? $user->role->name : '');
            if (in_array($role, ['admin', 'super admin'])) {
                return true; // Grant all abilities
            }
        });

        Gate::define('view-executive-dashboard', function (User $user) {
            // Allow executive roles (CEO, CFO, etc.) and any generic admin role
            $role = strtolower($user->role ? $user->role->name : '');
            return $user->isExecutive() || $role === 'admin' || $role === 'super admin';
        });

        // Asset Manager dashboard access
        Gate::define('view-manager-dashboard', function (User $user) {
            $role = strtolower($user->role ? $user->role->name : '');
            return $user->isAssetManager() || $role === 'admin' || $role === 'super admin';
        });

        // Staff dashboard access
        Gate::define('view-staff-dashboard', function (User $user) {
            $role = strtolower($user->role ? $user->role->name : '');
            return $user->isStaff() || $user->isEmployee() || $role === 'admin' || $role === 'super admin';
        });

        // Household/Individual dashboard access
        Gate::define('view-household-dashboard', function (User $user) {
            $role = strtolower($user->role ? $user->role->name : '');
            return $user->isHouseholdOwner() || $role === 'admin' || $role === 'super admin';
        });
    }
}
