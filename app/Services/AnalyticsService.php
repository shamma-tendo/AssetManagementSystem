<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Calculate asset depreciation trends.
     */
    public function calculateDepreciationTrends($period = 'monthly')
    {
        $query = Asset::select(
            DB::raw('DATE_FORMAT(purchase_date, "%Y-%m") as period'),
            DB::raw('SUM(purchase_cost) as total_purchase_cost'),
            DB::raw('SUM(current_value) as total_current_value'),
            DB::raw('COUNT(*) as asset_count')
        );

        if ($period === 'yearly') {
            $query->select(DB::raw('YEAR(purchase_date) as period'));
        }

        return $query->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                $depreciation = $item->total_purchase_cost - $item->total_current_value;
                return [
                    'period' => $item->period,
                    'total_purchase_cost' => $item->total_purchase_cost,
                    'total_current_value' => $item->total_current_value,
                    'total_depreciation' => $depreciation,
                    'depreciation_percentage' => $item->total_purchase_cost > 0 ? 
                        ($depreciation / $item->total_purchase_cost) * 100 : 0,
                    'asset_count' => $item->asset_count,
                ];
            });
    }

    /**
     * Calculate asset performance metrics.
     */
    public function calculatePerformanceMetrics()
    {
        $totalAssets = Asset::count();
        $activeAssets = Asset::where('status', 'active')->count();
        $underMaintenance = Asset::where('status', 'under_maintenance')->count();
        $retiredAssets = Asset::where('status', 'retired')->count();

        return [
            'utilization_rate' => $totalAssets > 0 ? ($activeAssets / $totalAssets) * 100 : 0,
            'maintenance_rate' => $totalAssets > 0 ? ($underMaintenance / $totalAssets) * 100 : 0,
            'retirement_rate' => $totalAssets > 0 ? ($retiredAssets / $totalAssets) * 100 : 0,
            'asset_turnover' => $retiredAssets / max($totalAssets - $retiredAssets, 1) * 100,
        ];
    }

    /**
     * Calculate ROI metrics for assets.
     */
    public function calculateROIMetrics()
    {
        return Asset::select(
            'category_id',
            DB::raw('AVG(purchase_cost) as avg_purchase_cost'),
            DB::raw('AVG(current_value) as avg_current_value'),
            DB::raw('AVG((purchase_cost - current_value) / purchase_cost * 100) as avg_depreciation_rate'),
            DB::raw('COUNT(*) as asset_count')
        )
        ->with('category')
        ->groupBy('category_id')
        ->get()
        ->map(function ($item) {
            return [
                'category' => $item->category->name,
                'avg_purchase_cost' => $item->avg_purchase_cost,
                'avg_current_value' => $item->avg_current_value,
                'avg_depreciation_rate' => $item->avg_depreciation_rate,
                'asset_count' => $item->asset_count,
                'value_retention_rate' => $item->avg_purchase_cost > 0 ? 
                    ($item->avg_current_value / $item->avg_purchase_cost) * 100 : 0,
            ];
        });
    }

    /**
     * Predict maintenance needs based on asset age and usage.
     */
    public function predictMaintenanceNeeds()
    {
        return Asset::where('status', 'active')
            ->select(
                'name',
                'serial_number',
                'purchase_date',
                'current_value',
                'category_id'
            )
            ->with('category')
            ->get()
            ->map(function ($asset) {
                $ageInYears = $asset->purchase_date->diffInYears(now());
                $usefulLife = $asset->category->useful_life_years ?? 5;
                $agePercentage = ($ageInYears / $usefulLife) * 100;

                $riskLevel = 'Low';
                if ($agePercentage > 80) {
                    $riskLevel = 'High';
                } elseif ($agePercentage > 60) {
                    $riskLevel = 'Medium';
                }

                return [
                    'asset_name' => $asset->name,
                    'serial_number' => $asset->serial_number,
                    'age_years' => $ageInYears,
                    'useful_life_years' => $usefulLife,
                    'age_percentage' => round($agePercentage, 2),
                    'risk_level' => $riskLevel,
                    'predicted_maintenance_date' => now()->addMonths($usefulLife * 12 - $ageInYears * 12),
                    'current_value' => $asset->current_value,
                ];
            })
            ->sortBy('risk_level')
            ->values();
    }

    /**
     * Calculate asset distribution metrics.
     */
    public function calculateDistributionMetrics()
    {
        return [
            'by_category' => $this->getDistributionByModel(Category::class, 'category_id'),
            'by_location' => $this->getDistributionByModel(Location::class, 'location_id'),
            'by_department' => $this->getDistributionByModel(Department::class, 'department_id'),
        ];
    }

    /**
     * Get distribution by model.
     */
    private function getDistributionByModel($model, $foreignKey)
    {
        return $model::withCount('assets')
            ->whereHas('assets')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'count' => $item->assets_count,
                    'percentage' => ($item->assets_count / Asset::count()) * 100,
                ];
            });
    }

    /**
     * Calculate asset age distribution.
     */
    public function calculateAgeDistribution()
    {
        return Asset::select(
            DB::raw('CASE 
                WHEN TIMESTAMPDIFF(YEAR, purchase_date, CURDATE()) = 0 THEN "0-1 years"
                WHEN TIMESTAMPDIFF(YEAR, purchase_date, CURDATE()) <= 3 THEN "1-3 years"
                WHEN TIMESTAMPDIFF(YEAR, purchase_date, CURDATE()) <= 5 THEN "3-5 years"
                ELSE "5+ years"
            END as age_group'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(purchase_cost) as total_cost'),
            DB::raw('SUM(current_value) as total_value')
        )
        ->groupBy('age_group')
        ->orderBy('age_group')
        ->get()
        ->map(function ($item) {
            return [
                'age_group' => $item->age_group,
                'count' => $item->count,
                'total_cost' => $item->total_cost,
                'total_value' => $item->total_value,
                'depreciation' => $item->total_cost - $item->total_value,
                'percentage' => ($item->count / Asset::count()) * 100,
            ];
        });
    }

    /**
     * Calculate warranty status distribution.
     */
    public function calculateWarrantyStatus()
    {
        return Asset::whereNotNull('warranty_expiry')
            ->select(
                DB::raw('CASE 
                    WHEN warranty_expiry < CURDATE() THEN "Expired"
                    WHEN warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN "Expiring Soon"
                    WHEN warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN "Expiring This Quarter"
                    ELSE "Valid"
                END as warranty_status'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(purchase_cost) as total_cost')
            )
            ->groupBy('warranty_status')
            ->orderBy('warranty_status')
            ->get()
            ->map(function ($item) {
                return [
                    'warranty_status' => $item->warranty_status,
                    'count' => $item->count,
                    'total_cost' => $item->total_cost,
                    'percentage' => ($item->count / Asset::whereNotNull('warranty_expiry')->count()) * 100,
                ];
            });
    }

    /**
     * Generate summary statistics.
     */
    public function generateSummaryStatistics()
    {
        return [
            'total_assets' => Asset::count(),
            'total_purchase_value' => Asset::sum('purchase_cost'),
            'total_current_value' => Asset::sum('current_value'),
            'total_depreciation' => Asset::sum('purchase_cost') - Asset::sum('current_value'),
            'average_asset_age' => Asset::avg(DB::raw('TIMESTAMPDIFF(YEAR, purchase_date, CURDATE())')),
            'categories_count' => Category::count(),
            'locations_count' => Location::count(),
            'departments_count' => Department::count(),
            'active_assets' => Asset::where('status', 'active')->count(),
            'under_maintenance' => Asset::where('status', 'under_maintenance')->count(),
            'retired_assets' => Asset::where('status', 'retired')->count(),
        ];
    }
}
