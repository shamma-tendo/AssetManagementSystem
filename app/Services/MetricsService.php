<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
use App\Models\AssetMetrics;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for calculating and managing asset metrics/KPIs
 */
class MetricsService
{
    /**
     * Calculate metrics for organization
     */
    public function calculateMetrics(string $organizationId): AssetMetrics
    {
        $today = now()->toDateString();

        // Get or create today's metrics
        $metrics = AssetMetrics::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'metric_date' => $today,
            ],
            [
                'organization_id' => $organizationId,
                'metric_date' => $today,
            ]
        );

        // Calculate utilization
        $totalAssets = Asset::where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->count();

        $assetsInUse = AssetAssignment::where('organization_id', $organizationId)
            ->where('status', 'in_use')
            ->count();

        $unusedAssets = $totalAssets - $assetsInUse;
        $utilizationRate = $totalAssets > 0 ? ($assetsInUse / $totalAssets) * 100 : 0;

        // Calculate losses
        $damagedAssets = AssetAssignment::where('organization_id', $organizationId)
            ->where('status', 'damaged')
            ->count();

        $stolenAssets = AssetAssignment::where('organization_id', $organizationId)
            ->where('status', 'lost')
            ->count();

        $lossCount = $damagedAssets + $stolenAssets;
        $lossRate = $totalAssets > 0 ? ($lossCount / $totalAssets) * 100 : 0;

        // Calculate financial metrics
        $totalAssetValue = Asset::where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->sum('purchase_cost');

        $currentValue = Asset::where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->sum('current_value');

        $depreciation = $totalAssetValue - $currentValue;

        // Calculate maintenance backlog
        $assetsNeedingRepair = AssetConditionReport::where('organization_id', $organizationId)
            ->where('reviewed_at', null)
            ->where('condition', 'broken')
            ->count();

        // Update metrics
        $metrics->update([
            'total_assets' => $totalAssets,
            'assets_in_use' => $assetsInUse,
            'utilization_rate' => round($utilizationRate, 2),
            'unused_assets' => $unusedAssets,
            'damaged_assets' => $damagedAssets,
            'stolen_assets' => $stolenAssets,
            'loss_rate' => round($lossRate, 2),
            'total_loss_value' => $lossCount * ($totalAssetValue / $totalAssets),
            'total_asset_value' => $totalAssetValue,
            'total_depreciation_value' => $depreciation,
            'net_asset_value' => $currentValue,
            'assets_needing_repair' => $assetsNeedingRepair,
            'cost_per_asset' => $totalAssets > 0 ? $totalAssetValue / $totalAssets : 0,
        ]);

        return $metrics;
    }

    /**
     * Get latest metrics
     */
    public function getLatestMetrics(string $organizationId): ?AssetMetrics
    {
        return AssetMetrics::where('organization_id', $organizationId)
            ->orderBy('metric_date', 'desc')
            ->first();
    }

    /**
     * Get metrics for date range
     */
    public function getMetricsForDateRange(
        string $organizationId,
        \DateTime $startDate,
        \DateTime $endDate
    ): Collection {
        return AssetMetrics::where('organization_id', $organizationId)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->orderBy('metric_date', 'asc')
            ->get();
    }

    /**
     * Calculate trend (comparing this period to previous period)
     */
    public function calculateTrend(string $organizationId, int $daysBack = 30): array
    {
        $currentStart = now()->subDays($daysBack);
        $previousStart = now()->subDays($daysBack * 2);

        $currentMetrics = AssetMetrics::where('organization_id', $organizationId)
            ->where('metric_date', '>=', $currentStart)
            ->avg('utilization_rate');

        $previousMetrics = AssetMetrics::where('organization_id', $organizationId)
            ->where('metric_date', '>=', $previousStart)
            ->where('metric_date', '<', $currentStart)
            ->avg('utilization_rate');

        $trend = $previousMetrics > 0 
            ? (($currentMetrics - $previousMetrics) / $previousMetrics) * 100
            : 0;

        return [
            'current_utilization' => round($currentMetrics, 2),
            'previous_utilization' => round($previousMetrics, 2),
            'trend_percentage' => round($trend, 2),
            'is_improving' => $trend > 0,
        ];
    }

    /**
     * Get health score (0-100)
     */
    public function getHealthScore(string $organizationId): float
    {
        $metrics = $this->getLatestMetrics($organizationId);

        if (!$metrics) {
            return 0;
        }

        return round($metrics->health_score, 2);
    }

    /**
     * Get metrics summary for dashboard
     */
    public function getSummary(string $organizationId): array
    {
        $metrics = $this->getLatestMetrics($organizationId);

        if (!$metrics) {
            $metrics = $this->calculateMetrics($organizationId);
        }

        return [
            'health_score' => round($metrics->health_score, 0),
            'utilization_rate' => $metrics->utilization_rate,
            'loss_rate' => $metrics->loss_rate,
            'total_assets' => $metrics->total_assets,
            'assets_in_use' => $metrics->assets_in_use,
            'unused_assets' => $metrics->unused_assets,
            'damaged_assets' => $metrics->damaged_assets,
            'stolen_assets' => $metrics->stolen_assets,
            'total_asset_value' => $metrics->total_asset_value,
            'net_asset_value' => $metrics->net_asset_value,
            'depreciation' => $metrics->total_depreciation_value,
            'cost_per_asset' => round($metrics->cost_per_asset, 2),
            'assets_needing_repair' => $metrics->assets_needing_repair,
            'overdue_maintenance' => $metrics->overdue_maintenance,
        ];
    }

    /**
     * Get comparative analysis
     */
    public function getComparison(string $organizationId): array
    {
        $latestMetrics = $this->getLatestMetrics($organizationId);

        if (!$latestMetrics) {
            return [];
        }

        $thirtyDaysAgo = AssetMetrics::where('organization_id', $organizationId)
            ->where('metric_date', '<=', now()->subDays(30))
            ->orderBy('metric_date', 'desc')
            ->first();

        if (!$thirtyDaysAgo) {
            return [];
        }

        return [
            'utilization_change' => $latestMetrics->utilization_rate - $thirtyDaysAgo->utilization_rate,
            'loss_rate_change' => $latestMetrics->loss_rate - $thirtyDaysAgo->loss_rate,
            'asset_count_change' => $latestMetrics->total_assets - $thirtyDaysAgo->total_assets,
            'value_change' => $latestMetrics->net_asset_value - $thirtyDaysAgo->net_asset_value,
        ];
    }
}
