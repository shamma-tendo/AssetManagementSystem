<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AssetRequest;
use App\Models\AssetAssignment;
use App\Models\Asset;
use App\Models\ActivityLog;
use Illuminate\View\View;

/**
 * Asset Manager Dashboard Controller
 * 
 * For Asset Manager users - manages:
 * - Creating and tracking asset requests
 * - Distributing assets to staff
 * - Monitoring asset utilization
 * - Tracking asset status reports from staff
 */
class AssetManagerDashboardController extends Controller
{
    /**
     * Show the asset manager dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Redirect to appropriate dashboard if not asset manager
        if (!$user->isAssetManager()) {
            return redirect()->route($user->getDashboardRoute());
        }

        // My requests
        $myRequests = AssetRequest::query()
            ->where('organization_id', $organization->id)
            ->where('requested_by', $user->id)
            ->with('approvedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Request statistics
        $requestStats = [
            'total' => AssetRequest::where('organization_id', $organization->id)
                ->where('requested_by', $user->id)
                ->count(),
            'pending' => AssetRequest::where('organization_id', $organization->id)
                ->where('requested_by', $user->id)
                ->where('status', 'pending')
                ->count(),
            'approved' => AssetRequest::where('organization_id', $organization->id)
                ->where('requested_by', $user->id)
                ->where('status', 'approved')
                ->count(),
            'rejected' => AssetRequest::where('organization_id', $organization->id)
                ->where('requested_by', $user->id)
                ->where('status', 'rejected')
                ->count(),
        ];

        // Assets in my custody/control
        $managedAssets = Asset::query()
            ->where('organization_id', $organization->id)
            ->with('assignments')
            ->paginate(20);

        // Distribution tracking
        $distributionStats = [
            'total_distributed' => AssetAssignment::where('organization_id', $organization->id)
                ->where('assigned_by', $user->id)
                ->count(),
            'active' => AssetAssignment::where('organization_id', $organization->id)
                ->where('assigned_by', $user->id)
                ->whereIn('status', ['assigned', 'in_use'])
                ->count(),
            'returned' => AssetAssignment::where('organization_id', $organization->id)
                ->where('assigned_by', $user->id)
                ->whereIn('status', ['returned', 'lost', 'damaged'])
                ->count(),
        ];

        // Pending condition reports (issues reported by staff)
        $pendingReports = \App\Models\AssetConditionReport::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->with('asset', 'reportedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboards.asset-manager', [
            'organization' => $organization,
            'myRequests' => $myRequests,
            'requestStats' => $requestStats,
            'managedAssets' => $managedAssets,
            'distributionStats' => $distributionStats,
            'pendingReports' => $pendingReports,
        ]);
    }

    /**
     * Show asset request creation wizard
     */
    public function createRequest()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$user->isAssetManager()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $categories = \App\Models\Category::all();
        $locations = \App\Models\Location::where('organization_id', $organization->id)->get();

        return view('dashboards.asset-request-create', [
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }

    /**
     * Show asset distribution interface
     */
    public function distributeAssets()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$user->isAssetManager()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $undistributedAssets = Asset::query()
            ->where('organization_id', $organization->id)
            ->whereDoesntHave('assignments')
            ->with('category')
            ->paginate(20);

        $staff = $organization->users()
            ->whereNotNull('role_id')
            ->get();

        return view('dashboards.asset-distribution', [
            'assets' => $undistributedAssets,
            'staff' => $staff,
        ]);
    }
}
