<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $orgId = auth()->user()?->organization_id;

        $assets = Asset::query()->when($orgId, fn ($q) => $q->where('organization_id', $orgId));

        $totalAssets = (clone $assets)->count();
        $activeAssets = (clone $assets)->where('status', 'Active')->count();
        $underMaintenance = (clone $assets)->where('status', 'Under Maintenance')->count();
        $retiredAssets = (clone $assets)->where('status', 'Retired')->count();
        $totalAssetValue = (clone $assets)->sum('current_value');

        $categories = Category::count();
        $locations = Location::count();
        $departments = Department::count();

        $assetsByStatus = (clone $assets)->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $assetCountConstraint = function ($query) use ($orgId) {
            if ($orgId) {
                $query->where('assets.organization_id', $orgId);
            }
        };

        $assetsByCategory = Category::withCount(['assets' => $assetCountConstraint])->get()->map(fn ($c) => [
            'category' => $c->name,
            'count' => $c->assets_count,
        ]);

        $assetsByLocation = Location::withCount(['assets' => $assetCountConstraint])->get()->map(fn ($l) => [
            'location' => $l->name,
            'count' => $l->assets_count,
        ]);

        $org = auth()->user()?->organization;

        return response()->json([
            'success' => true,
            'data' => [
                'lens' => session('aems_context'),
                'organization' => $org ? [
                    'name' => $org->name,
                    'type' => $org->type,
                ] : null,
                'summary' => [
                    'total_assets' => $totalAssets,
                    'active_assets' => $activeAssets,
                    'under_maintenance' => $underMaintenance,
                    'retired_assets' => $retiredAssets,
                    'total_asset_value' => $totalAssetValue,
                ],
                'counts' => [
                    'categories' => $categories,
                    'locations' => $locations,
                    'departments' => $departments,
                ],
                'charts' => [
                    'by_status' => $assetsByStatus,
                    'by_category' => $assetsByCategory,
                    'by_location' => $assetsByLocation,
                ],
            ],
        ]);
    }
}
