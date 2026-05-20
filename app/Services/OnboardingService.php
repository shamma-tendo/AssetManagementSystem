<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class OnboardingService
{
    /**
     * Handles "Register a new company" logic.
     * First person becomes CEO/CFO (Super Admin) and is auto-approved.
     */
    public function registerNewCompany(array $orgData, array $userData): User
    {
        $organization = Organization::create(array_merge($orgData, [
            'type' => 'company',
            'is_active' => true
        ]));

        return User::create(array_merge($userData, [
            'organization_id' => $organization->organization_id,
            'role_id' => Role::where('name', 'CEO')->first()->id,
            'is_approved' => true, // Auto-approved for first user
            'password' => Hash::make($userData['password']),
        ]));
    }

    /**
     * Handles "Join an existing company" logic.
     * Users enter Company Code and stay pending until approval.
     */
    public function joinCompany(string $companyCode, array $userData, string $requestedRoleId): User
    {
        $organization = Organization::where('code', $companyCode)->firstOrFail();

        return User::create(array_merge($userData, [
            'organization_id' => $organization->organization_id,
            'role_id' => Role::where('name', 'Staff')->first()->id, // Lowest role until approved
            'requested_role_id' => $requestedRoleId,
            'is_approved' => false, // Stays pending
            'password' => Hash::make($userData['password']),
        ]));
    }

    /**
     * Logic for CEO to approve a member.
     */
    public function approveMember(User $user, string $assignedRoleId): void
    {
        $user->update([
            'role_id' => $assignedRoleId,
            'is_approved' => true,
            'requested_role_id' => null
        ]);
    }
}