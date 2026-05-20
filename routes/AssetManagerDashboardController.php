<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
use Illuminate\View\View;

class AssetManagerDashboardController extends Controller
{
    /**
     * Show the Asset Manager Dashboard
     */
    public function index(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Asset Manager KPIs - Focus on inventory health and staff feedback
        $stats = [
            'my_pending_requests' => AssetRequest::where('requested_by', $user->id)
                ->where('status', 'pending')
                ->count(),
            'unused_inventory' => Asset::where('organization_id', $organization->id)
                ->where('status', 'available')
                ->count(),
            'active_assignments' => AssetAssignment::where('organization_id', $organization->id)
                ->where('status', 'active')
                ->count(),
            'staff_reports_to_review' => AssetConditionReport::where('organization_id', $organization->id)
                ->where('status', 'pending')
                ->count(),
        ];

        // Track status of my procurement requests to the CEO/CFO
        $myRequests = AssetRequest::where('requested_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Inbox: Reviewing what staff members are saying about their assets
        $staffReports = AssetConditionReport::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->with(['asset', 'reportedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboards.asset-manager', [
            'organization' => $organization,
            'stats' => $stats,
            'myRequests' => $myRequests,
            'staffReports' => $staffReports,
        ]);
    }
}