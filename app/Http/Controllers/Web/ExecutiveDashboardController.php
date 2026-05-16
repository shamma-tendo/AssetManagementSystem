<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AssetRequest;
use App\Models\AssetAssignment;
use App\Models\Asset;
use App\Models\ActivityLog;
use Illuminate\View\View;

/**
 * Executive Dashboard Controller
 * 
 * For CEO/CFO users - provides strategic overview of:
 * - Pending asset requests for approval
 * - Overall asset status
 * - Staff activities
 * - KPIs and metrics
 */
class ExecutiveDashboardController extends Controller
{
    /**
     * Show the executive dashboard
     */
    public function index(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Ensure user is executive
        if (!$user->isExecutive()) {
            abort(403, 'Unauthorized access');
        }

        // Pending requests awaiting approval
        $pendingRequests = AssetRequest::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Asset status overview
        $assetStats = [
            'total' => Asset::where('organization_id', $organization->id)->count(),
            'active' => Asset::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count(),
            'damaged' => Asset::where('organization_id', $organization->id)
                ->where('status', 'damaged')
                ->count(),
            'stolen' => Asset::where('organization_id', $organization->id)
                ->where('status', 'stolen')
                ->count(),
            'maintenance' => Asset::where('organization_id', $organization->id)
                ->where('status', 'maintenance')
                ->count(),
        ];

        // Recent staff activities
        $recentActivities = ActivityLog::query()
            ->where('organization_id', $organization->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Asset assignments overview
        $assignmentStats = [
            'total_assigned' => AssetAssignment::where('organization_id', $organization->id)->count(),
            'active_assignments' => AssetAssignment::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count(),
            'returned_assignments' => AssetAssignment::where('organization_id', $organization->id)
                ->where('status', 'returned')
                ->count(),
        ];

        // Approval trend (last 30 days)
        $approvalTrend = AssetRequest::query()
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        return view('dashboards.executive', [
            'organization' => $organization,
            'pendingRequests' => $pendingRequests,
            'assetStats' => $assetStats,
            'recentActivities' => $recentActivities,
            'assignmentStats' => $assignmentStats,
            'approvalTrend' => $approvalTrend,
        ]);
    }

    /**
     * Show approval queue
     */
    public function approvalQueue(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$user->isExecutive()) {
            abort(403);
        }

        $requests = AssetRequest::query()
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->with('requestedBy', 'assets')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('dashboards.approval-queue', [
            'requests' => $requests,
        ]);
    }
}
