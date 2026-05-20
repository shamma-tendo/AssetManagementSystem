<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AssetRequest;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
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
    public function index()
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Redirect to appropriate dashboard if not executive
        if (!$user->isExecutive()) {
            return redirect()->route($user->getDashboardRoute());
        }

        // Pending team member approvals (join requests)
        $pendingUserApprovals = \App\Models\User::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->where('is_approved', false)
            ->count();

        // Pending asset requests awaiting approval
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
                ->where('status', 'Active')
                ->count(),
            'damaged' => Asset::where('organization_id', $organization->id)
                ->whereIn('status', ['damaged', 'Damaged'])
                ->count(),
            'stolen' => Asset::where('organization_id', $organization->id)
                ->whereIn('status', ['stolen', 'Stolen'])
                ->count(),
            'maintenance' => Asset::where('organization_id', $organization->id)
                ->whereIn('status', ['Under Maintenance', 'maintenance'])
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
                ->whereIn('status', ['assigned', 'in_use'])
                ->count(),
            'returned_assignments' => AssetAssignment::where('organization_id', $organization->id)
                ->whereIn('status', ['returned', 'lost', 'damaged'])
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

        $requestStats = [
            'pending'  => AssetRequest::where('organization_id', $organization->id)->where('status', 'pending')->count(),
            'approved' => AssetRequest::where('organization_id', $organization->id)->where('status', 'approved')->count(),
            'fulfilled'=> AssetRequest::where('organization_id', $organization->id)->where('status', 'fulfilled')->count(),
        ];

        $unreviewedReports = AssetConditionReport::where('organization_id', $organization->id)
            ->whereNull('reviewed_at')
            ->count();

        return view('dashboards.executive', [
            'organization'         => $organization,
            'pendingRequests'      => $pendingRequests,
            'assetStats'           => $assetStats,
            'recentActivities'     => $recentActivities,
            'assignmentStats'      => $assignmentStats,
            'approvalTrend'        => $approvalTrend,
            'pendingUserApprovals' => $pendingUserApprovals,
            'requestStats'         => $requestStats,
            'unreviewedReports'    => $unreviewedReports,
        ]);
    }

    /**
     * Show approval queue
     */
    public function approvalQueue()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$user->isExecutive()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $requests = AssetRequest::query()
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->with('requestedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('dashboards.approval-queue', [
            'requests' => $requests,
        ]);
    }
}
