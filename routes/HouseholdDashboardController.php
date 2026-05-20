<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\InsurancePolicy;
use App\Models\AssetWarranty;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class HouseholdDashboardController extends Controller
{
    /**
     * Show the Personal Household Dashboard
     */
    public function index(): View
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Household Wealth & Asset Stats
        $stats = [
            'total_assets' => Asset::where('organization_id', $organization->id)->count(),
            'portfolio_value' => Asset::where('organization_id', $organization->id)->sum('purchase_cost'),
            'insured_items' => InsurancePolicy::where('organization_id', $organization->id)->count(),
            'warranty_alerts' => AssetWarranty::where('organization_id', $organization->id)
                ->whereDate('end_date', '<=', now()->addDays(30))
                ->count(),
        ];

        // Retrieve Next of Kin from organization metadata
        $metadata = $organization->industry_metadata ? json_decode($organization->industry_metadata, true) : [];
        $nextOfKin = $metadata['next_of_kin'] ?? 'Not configured';

        // Personal Asset Inventory list
        $assets = Asset::where('organization_id', $organization->id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboards.household', [
            'stats' => $stats,
            'nextOfKin' => $nextOfKin,
            'assets' => $assets,
        ]);
    }

    /**
     * Export Asset Declaration for bank loans
     */
    public function exportDeclaration(): Response
    {
        $user = auth()->user();
        // Implementation for generating the loan application document
        return response()->streamDownload(function () {
            echo "AssetFlow - Official Asset Declaration\n";
            echo "Prepared for: " . auth()->user()->name . "\n";
            echo "Date: " . now()->toFormattedDateString();
        }, 'asset_declaration_' . now()->format('Y_m_d') . '.txt');
    }
}
