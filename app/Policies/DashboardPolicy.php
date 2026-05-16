<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Auth\Access\Response;

/**
 * Dashboard Access Policy
 * Controls which users can access which dashboard
 */
class DashboardPolicy
{
    /**
     * Determine if the user can view the executive dashboard
     */
    public function viewExecutiveDashboard(User $user): bool
    {
        return $user->organization && $user->isExecutive();
    }

    /**
     * Determine if the user can view the asset manager dashboard
     */
    public function viewManagerDashboard(User $user): bool
    {
        return $user->organization && $user->isAssetManager();
    }

    /**
     * Determine if the user can view the staff dashboard
     */
    public function viewStaffDashboard(User $user): bool
    {
        return $user->organization && $user->isStaff();
    }

    /**
     * Determine if the user can view the household dashboard
     */
    public function viewHouseholdDashboard(User $user): bool
    {
        return $user->organization && $user->organization->isHousehold();
    }
}
