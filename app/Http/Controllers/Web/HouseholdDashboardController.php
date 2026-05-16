<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\MaintenanceSchedule;
use App\Models\InsurancePolicy;
use App\Models\AssetLoan;
use Illuminate\View\View;

/**
 * Household Dashboard Controller
 * 
 * For individual/household users - provides:
 * - Personal asset inventory
 * - Insurance policy tracking
 * - Warranty management
 * - Maintenance reminders
 * - Loan/rental history
 */
class HouseholdDashboardController extends Controller
{
    /**
     * Show the household dashboard
     */
    public function index(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Ensure this is a household account
        if (!$organization || !$organization->isHousehold()) {
            abort(403, 'Unauthorized access');
        }

        // All personal assets
        $assets = Asset::where('organization_id', $organization->id)
            ->with('category', 'location')
            ->paginate(15);

        // Assets statistics
        $assetStats = [
            'total' => Asset::where('organization_id', $organization->id)->count(),
            'valuable' => Asset::where('organization_id', $organization->id)
                ->where('estimated_value', '>', 1000)
                ->count(),
            'with_warranty' => Asset::where('organization_id', $organization->id)
                ->whereHas('warranties')
                ->count(),
            'with_insurance' => Asset::where('organization_id', $organization->id)
                ->whereHas('insurancePolicies')
                ->count(),
        ];

        // Insurance policies
        $insurancePolicies = InsurancePolicy::where('organization_id', $organization->id)
            ->where('status', 'active')
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        // Warranties - expiring soon
        $expiringWarranties = AssetWarranty::query()
            ->where('organization_id', $organization->id)
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->with('asset')
            ->orderBy('expiry_date')
            ->get();

        // Maintenance schedules
        $upcomingMaintenance = MaintenanceSchedule::query()
            ->where('organization_id', $organization->id)
            ->where('scheduled_date', '>', now())
            ->where('scheduled_date', '<=', now()->addDays(60))
            ->with('asset')
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        // Loans - active
        $activeLoans = AssetLoan::where('organization_id', $organization->id)
            ->where('status', 'active')
            ->with('asset')
            ->get();

        // Portfolio value
        $totalValue = Asset::where('organization_id', $organization->id)
            ->sum('estimated_value');

        return view('dashboards.household', [
            'organization' => $organization,
            'assets' => $assets,
            'assetStats' => $assetStats,
            'insurancePolicies' => $insurancePolicies,
            'expiringWarranties' => $expiringWarranties,
            'upcomingMaintenance' => $upcomingMaintenance,
            'activeLoans' => $activeLoans,
            'totalValue' => $totalValue,
        ]);
    }

    /**
     * Add a new personal asset
     */
    public function createAsset(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            abort(403);
        }

        $categories = \App\Models\Category::all();
        $locations = \App\Models\Location::where('organization_id', $organization->id)
            ->orWhereNull('organization_id')
            ->get();

        return view('dashboards.household-asset-create', [
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }

    /**
     * View asset details
     */
    public function viewAsset($assetId): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            abort(403);
        }

        $asset = Asset::where('organization_id', $organization->id)
            ->with('category', 'location', 'warranties', 'insurancePolicies', 'documents')
            ->findOrFail($assetId);

        $warranties = $asset->warranties;
        $insurancePolicies = $asset->insurancePolicies;
        $documents = $asset->documents;
        $maintenanceRecords = \App\Models\MaintenanceRecord::where('asset_id', $assetId)
            ->orderBy('date', 'desc')
            ->get();

        return view('dashboards.household-asset-detail', [
            'asset' => $asset,
            'warranties' => $warranties,
            'insurancePolicies' => $insurancePolicies,
            'documents' => $documents,
            'maintenanceRecords' => $maintenanceRecords,
        ]);
    }

    /**
     * Insurance & warranty management
     */
    public function insurance(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            abort(403);
        }

        $insurancePolicies = InsurancePolicy::where('organization_id', $organization->id)
            ->with('asset')
            ->orderBy('expiry_date')
            ->paginate(15);

        return view('dashboards.household-insurance', [
            'insurancePolicies' => $insurancePolicies,
        ]);
    }
}
