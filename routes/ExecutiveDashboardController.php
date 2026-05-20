<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetConditionReport;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    /**
     * Show the Executive (CEO/CFO) Dashboard
     */
    public function index(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Real-time KPI cards for High-Level Oversight
        $stats = [
            'total_assets' => Asset::where('organization_id', $organization->id)->count(),
            'active_utilization' => Asset::where('organization_id', $organization->id)->where('status', 'active')->count(),
            'pending_approvals' => AssetRequest::where('organization_id', $organization->id)->where('status', 'pending')->count(),
            'critical_issues' => AssetConditionReport::where('organization_id', $organization->id)
                ->where('requires_urgent_attention', true)
                ->where('status', 'pending')
                ->count(),
        ];

        // Status distribution for Asset Status Distribution Chart
        $statusDistribution = Asset::where('organization_id', $organization->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent activities: Procurement requests from Managers awaiting decision
        $pendingRequests = AssetRequest::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent urgent reports from Staff (e.g. Hospital equipment needing repair)
        $urgentReports = AssetConditionReport::where('organization_id', $organization->id)
            ->where('requires_urgent_attention', true)
            ->with(['asset', 'reportedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboards.executive', [
            'stats' => $stats,
            'statusDistribution' => $statusDistribution,
            'pendingRequests' => $pendingRequests,
            'urgentReports' => $urgentReports,
        ]);
    }
}