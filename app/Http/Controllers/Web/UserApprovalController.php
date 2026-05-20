<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    /**
     * Show pending approvals for CEO
     */
    public function index()
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Only executives can approve users
        if (!$user->isExecutive()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $pendingUsers = User::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->where('is_approved', false)
            ->get();

        return view('dashboards.approvals', [
            'organization' => $organization,
            'pendingUsers' => $pendingUsers,
        ]);
    }

    /**
     * Approve a user and assign role
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $currentUser = auth()->user();
        
        // Only executives can approve
        if (!$currentUser->isExecutive()) {
            return redirect()->route($currentUser->getDashboardRoute());
        }

        // Ensure user belongs to same organization
        if ($user->organization_id !== $currentUser->organization_id) {
            return back()->withErrors(['error' => 'Unauthorized']);
        }

        $request->validate([
            'role' => 'required|string|in:Asset Manager,Staff',
        ]);

        // Get or create role
        $role = Role::firstOrCreate(
            ['name' => $request->role],
            ['description' => $request->role . ' role']
        );

        // Update user
        $user->update([
            'role_id' => $role->id,
            'status' => 'active',
            'is_approved' => true,
            'requested_role' => null,
        ]);

        return back()->with('success', "{$user->name} has been approved as {$request->role}.");
    }

    /**
     * Reject a user
     */
    public function reject(User $user): RedirectResponse
    {
        $currentUser = auth()->user();
        
        // Only executives can reject
        if (!$currentUser->isExecutive()) {
            return redirect()->route($currentUser->getDashboardRoute());
        }

        // Ensure user belongs to same organization
        if ($user->organization_id !== $currentUser->organization_id) {
            return back()->withErrors(['error' => 'Unauthorized']);
        }

        $user->update([
            'status' => 'rejected',
            'is_approved' => false,
        ]);

        return back()->with('success', "{$user->name}'s request has been rejected.");
    }
}
