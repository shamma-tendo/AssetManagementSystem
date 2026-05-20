<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
use App\Models\AssetRequest;
use App\Models\ActivityLog;
use Illuminate\View\View;

/**
 * Staff Dashboard Controller
 * 
 * For Staff/Employee users - allows them to:
 * - View their assigned assets
 * - Report asset status (in-use, broken, stolen, ineffective)
 * - View assignment history
 * - Receive notifications about assets
 */
class StaffDashboardController extends Controller
{
    /**
     * Show the staff dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Redirect to appropriate dashboard if not staff
        if (!$user->isStaff()) {
            return redirect()->route($user->getDashboardRoute());
        }

        // My assigned assets
        $myAssets = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_use'])
            ->with('asset', 'asset.category')
            ->orderBy('assigned_at', 'desc')
            ->paginate(10);

        // Asset statistics for me
        $myAssetStats = [
            'total_assigned' => AssetAssignment::where('assigned_to', $user->id)->count(),
            'active' => AssetAssignment::where('assigned_to', $user->id)
                ->whereIn('status', ['assigned', 'in_use'])
                ->count(),
            'reported_issues' => AssetConditionReport::where('reported_by', $user->id)
                ->whereNull('reviewed_at')
                ->count(),
        ];

        // My condition reports (issues I've reported)
        $myReports = AssetConditionReport::query()
            ->where('reported_by', $user->id)
            ->with('asset')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Assignment history
        $assignmentHistory = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['returned', 'lost', 'damaged'])
            ->with('asset')
            ->orderBy('returned_at', 'desc')
            ->limit(10)
            ->get();

        // All active assets in the org (CEO's inventory — what staff can see/request)
        $availableAssets = Asset::where('organization_id', $organization->id)
            ->where('status', 'Active')
            ->with('category', 'location')
            ->orderBy('name')
            ->get();

        // My asset requests
        $myRequests = AssetRequest::where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboards.staff', [
            'organization'      => $organization,
            'myAssets'          => $myAssets,
            'myAssetStats'      => $myAssetStats,
            'myReports'         => $myReports,
            'assignmentHistory' => $assignmentHistory,
            'availableAssets'   => $availableAssets,
            'myRequests'        => $myRequests,
        ]);
    }

    /**
     * Show asset detail for staff to report issues
     */
    public function viewAsset($assetId)
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$user->isStaff()) {
            return redirect()->route($user->getDashboardRoute());
        }

        // Verify this asset is assigned to the user
        $assignment = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->where('asset_id', $assetId)
            ->whereIn('status', ['assigned', 'in_use'])
            ->firstOrFail();

        $asset = $assignment->asset;
        $conditionReports = AssetConditionReport::query()
            ->where('asset_id', $assetId)
            ->where('reported_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboards.staff-asset-detail', [
            'asset' => $asset,
            'assignment' => $assignment,
            'conditionReports' => $conditionReports,
        ]);
    }

    /**
     * Report asset status/condition
     */
    public function reportAssetStatus($assetId)
    {
        $user = auth()->user();

        if (!$user->isStaff()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $assignment = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->where('asset_id', $assetId)
            ->whereIn('status', ['assigned', 'in_use'])
            ->firstOrFail();

        $asset = $assignment->asset;

        // Status options for staff to report
        $statusOptions = [
            'in_use' => [
                'label' => 'In Use & Working',
                'description' => 'Asset is functioning normally',
                'icon' => '✅',
                'color' => 'green'
            ],
            'needs_repair' => [
                'label' => 'Needs Repair',
                'description' => 'Asset is broken or malfunctioning',
                'icon' => '🔧',
                'color' => 'yellow'
            ],
            'stolen' => [
                'label' => 'Stolen',
                'description' => 'Asset has been stolen or is missing',
                'icon' => '⚠️',
                'color' => 'red'
            ],
            'not_effective' => [
                'label' => 'Not Effective',
                'description' => 'Asset is not suitable for my work',
                'icon' => '❌',
                'color' => 'orange'
            ],
            'damaged' => [
                'label' => 'Damaged',
                'description' => 'Asset has physical damage',
                'icon' => '💔',
                'color' => 'red'
            ]
        ];

        return view('dashboards.staff-report-status', [
            'asset' => $asset,
            'assignment' => $assignment,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Submit an asset condition report
     */
    public function submitReport(\Illuminate\Http\Request $request, $assetId)
    {
        $user = auth()->user();

        if (!$user->isStaff()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $request->validate([
            'condition' => 'required|in:in_use,broken,needs_repair,stolen,lost,not_effective,ready_for_return',
            'description' => 'nullable|string|max:1000',
        ]);

        $assignment = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->where('asset_id', $assetId)
            ->whereIn('status', ['assigned', 'in_use'])
            ->firstOrFail();

        AssetConditionReport::create([
            'asset_assignment_id' => $assignment->id,
            'asset_id'            => $assetId,
            'organization_id'     => $user->organization_id,
            'reported_by'         => $user->id,
            'condition'           => $request->condition,
            'description'         => $request->description,
            'status'              => 'pending',
            'reported_at'         => now(),
        ]);

        return redirect()->route('staff.asset.view', $assetId)
            ->with('success', 'Condition report submitted successfully.');
    }
}
