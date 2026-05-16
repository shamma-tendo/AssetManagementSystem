<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
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
    public function index(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Ensure user is staff
        if (!$user->isStaff()) {
            abort(403, 'Unauthorized access');
        }

        // My assigned assets
        $myAssets = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'active')
            ->with('asset', 'asset.category')
            ->orderBy('assigned_at', 'desc')
            ->paginate(10);

        // Asset statistics for me
        $myAssetStats = [
            'total_assigned' => AssetAssignment::where('assigned_to', $user->id)->count(),
            'active' => AssetAssignment::where('assigned_to', $user->id)
                ->where('status', 'active')
                ->count(),
            'reported_issues' => AssetConditionReport::where('reported_by', $user->id)
                ->where('status', 'pending')
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
            ->where('status', 'returned')
            ->with('asset')
            ->orderBy('returned_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboards.staff', [
            'organization' => $organization,
            'myAssets' => $myAssets,
            'myAssetStats' => $myAssetStats,
            'myReports' => $myReports,
            'assignmentHistory' => $assignmentHistory,
        ]);
    }

    /**
     * Show asset detail for staff to report issues
     */
    public function viewAsset($assetId): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$user->isStaff()) {
            abort(403);
        }

        // Verify this asset is assigned to the user
        $assignment = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->where('asset_id', $assetId)
            ->where('status', 'active')
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
    public function reportAssetStatus($assetId): View
    {
        $user = auth()->user();

        if (!$user->isStaff()) {
            abort(403);
        }

        $assignment = AssetAssignment::query()
            ->where('assigned_to', $user->id)
            ->where('asset_id', $assetId)
            ->where('status', 'active')
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
}
