<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\MaintenanceSchedule;
use App\Models\Inspection;
use App\Models\Part;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\PredictiveModel;
use App\Models\Prediction;
use App\Models\MaintenanceRecommendation;
use App\Models\DepreciationEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdvancedAnalyticsService
{
    /**
     * Get comprehensive dashboard analytics.
     */
    public function getDashboardAnalytics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        $period = $filters['period'] ?? 'month';

        return [
            'overview' => $this->getOverviewMetrics($dateRange),
            'asset_analytics' => $this->getAssetAnalytics($dateRange, $period),
            'maintenance_analytics' => $this->getMaintenanceAnalytics($dateRange, $period),
            'financial_analytics' => $this->getFinancialAnalytics($dateRange, $period),
            'operational_analytics' => $this->getOperationalAnalytics($dateRange, $period),
            'iot_analytics' => $this->getIoTAnalytics($dateRange, $period),
            'predictive_analytics' => $this->getPredictiveAnalytics($dateRange, $period),
            'performance_analytics' => $this->getPerformanceAnalytics($dateRange, $period),
            'trends' => $this->getTrendAnalytics($dateRange, $period),
            'alerts' => $this->getAlertAnalytics($dateRange),
        ];
    }

    /**
     * Get overview metrics.
     */
    private function getOverviewMetrics(array $dateRange): array
    {
        return [
            'total_assets' => Asset::count(),
            'active_assets' => Asset::where('status', 'active')->count(),
            'assets_need_maintenance' => $this->getAssetsNeedingMaintenance($dateRange),
            'critical_work_orders' => WorkOrder::where('priority', 'critical')
                ->where('status', '!=', 'completed')
                ->count(),
            'total_work_orders' => WorkOrder::whereBetween('created_at', $dateRange)->count(),
            'completed_work_orders' => WorkOrder::whereBetween('completed_at', $dateRange)
                ->where('status', 'completed')
                ->count(),
            'total_value' => Asset::sum('purchase_cost'),
            'depreciated_value' => $this->getCurrentAssetValue(),
            'maintenance_cost' => WorkOrder::whereBetween('completed_at', $dateRange)
                ->sum('actual_cost') ?? 0,
            'downtime_hours' => WorkOrder::whereBetween('completed_at', $dateRange)
                ->sum('downtime_hours') ?? 0,
            'mttr' => $this->calculateMTTR($dateRange),
            'mtbf' => $this->calculateMTBF($dateRange),
            'availability' => $this->calculateAvailability($dateRange),
        ];
    }

    /**
     * Get asset analytics.
     */
    private function getAssetAnalytics(array $dateRange, string $period): array
    {
        return [
            'asset_distribution' => $this->getAssetDistribution(),
            'asset_health' => $this->getAssetHealthDistribution(),
            'asset_age_distribution' => $this->getAssetAgeDistribution(),
            'asset_utilization' => $this->getAssetUtilization($dateRange, $period),
            'asset_performance' => $this->getAssetPerformanceMetrics($dateRange),
            'depreciation_analysis' => $this->getDepreciationAnalysis($dateRange, $period),
            'asset_lifecycle' => $this->getAssetLifecycleAnalysis($dateRange),
            'critical_assets' => $this->getCriticalAssetsAnalytics($dateRange),
        ];
    }

    /**
     * Get maintenance analytics.
     */
    private function getMaintenanceAnalytics(array $dateRange, string $period): array
    {
        return [
            'maintenance_trends' => $this->getMaintenanceTrends($dateRange, $period),
            'maintenance_costs' => $this->getMaintenanceCostAnalysis($dateRange, $period),
            'maintenance_types' => $this->getMaintenanceTypeDistribution($dateRange),
            'preventive_vs_corrective' => $this->getPreventiveCorrectiveRatio($dateRange),
            'maintenance_efficiency' => $this->getMaintenanceEfficiency($dateRange),
            'scheduled_vs_unscheduled' => $this->getScheduledMaintenanceAnalysis($dateRange),
            'maintenance_backlog' => $this->getMaintenanceBacklog(),
            'vendor_performance' => $this->getVendorPerformance($dateRange),
        ];
    }

    /**
     * Get financial analytics.
     */
    private function getFinancialAnalytics(array $dateRange, string $period): array
    {
        return [
            'cost_analysis' => $this->getCostAnalysis($dateRange, $period),
            'budget_vs_actual' => $this->getBudgetVsActual($dateRange, $period),
            'cost_per_asset' => $this->getCostPerAsset($dateRange),
            'roi_analysis' => $this->getROIAnalysis($dateRange),
            'expense_breakdown' => $this->getExpenseBreakdown($dateRange),
            'savings_opportunities' => $this->getSavingsOpportunities($dateRange),
            'financial_trends' => $this->getFinancialTrends($dateRange, $period),
            'asset_valuation' => $this->getAssetValuation($dateRange),
        ];
    }

    /**
     * Get operational analytics.
     */
    private function getOperationalAnalytics(array $dateRange, string $period): array
    {
        return [
            'performance_metrics' => $this->getOperationalPerformance($dateRange, $period),
            'utilization_rates' => $this->getUtilizationRates($dateRange, $period),
            'downtime_analysis' => $this->getDowntimeAnalysis($dateRange, $period),
            'productivity_metrics' => $this->getProductivityMetrics($dateRange),
            'resource_allocation' => $this->getResourceAllocation($dateRange),
            'efficiency_scores' => $this->getEfficiencyScores($dateRange),
            'bottleneck_analysis' => $this->getBottleneckAnalysis($dateRange),
            'capacity_planning' => $this->getCapacityPlanning($dateRange),
        ];
    }

    /**
     * Get IoT analytics.
     */
    private function getIoTAnalytics(array $dateRange, string $period): array
    {
        return [
            'sensor_health' => $this->getSensorHealthMetrics($dateRange),
            'data_quality' => $this->getSensorDataQuality($dateRange),
            'alert_trends' => $this->getSensorAlertTrends($dateRange, $period),
            'energy_consumption' => $this->getEnergyConsumptionAnalytics($dateRange, $period),
            'environmental_monitoring' => $this->getEnvironmentalMonitoring($dateRange),
            'predictive_alerts' => $this->getPredictiveAlertAnalytics($dateRange),
            'sensor_utilization' => $this->getSensorUtilization($dateRange),
            'connectivity_metrics' => $this->getConnectivityMetrics($dateRange),
        ];
    }

    /**
     * Get predictive analytics.
     */
    private function getPredictiveAnalytics(array $dateRange, string $period): array
    {
        return [
            'model_performance' => $this->getModelPerformanceAnalytics($dateRange),
            'prediction_accuracy' => $this->getPredictionAccuracyAnalytics($dateRange),
            'risk_assessment' => $this->getRiskAssessmentAnalytics($dateRange),
            'recommendation_effectiveness' => $this->getRecommendationEffectiveness($dateRange),
            'failure_predictions' => $this->getFailurePredictionAnalytics($dateRange),
            'maintenance_optimization' => $this->getMaintenanceOptimizationAnalytics($dateRange),
            'cost_savings' => $this->getPredictiveCostSavings($dateRange),
            'model_drift' => $this->getModelDriftAnalytics($dateRange),
        ];
    }

    /**
     * Get performance analytics.
     */
    private function getPerformanceAnalytics(array $dateRange, string $period): array
    {
        return [
            'kpi_metrics' => $this->getKPIMetrics($dateRange, $period),
            'performance_trends' => $this->getPerformanceTrends($dateRange, $period),
            'benchmark_comparison' => $this->getBenchmarkComparison($dateRange),
            'performance_scores' => $this->getPerformanceScores($dateRange),
            'improvement_areas' => $this->getImprovementAreas($dateRange),
            'goal_tracking' => $this->getGoalTracking($dateRange),
            'performance_forecasts' => $this->getPerformanceForecasts($dateRange),
            'efficiency_gains' => $this->getEfficiencyGains($dateRange),
        ];
    }

    /**
     * Get trend analytics.
     */
    private function getTrendAnalytics(array $dateRange, string $period): array
    {
        return [
            'historical_trends' => $this->getHistoricalTrends($dateRange, $period),
            'seasonal_patterns' => $this->getSeasonalPatterns($dateRange),
            'growth_rates' => $this->getGrowthRates($dateRange, $period),
            'forecasting' => $this->getTrendForecasting($dateRange, $period),
            'anomaly_detection' => $this->getTrendAnomalies($dateRange),
            'correlation_analysis' => $this->getCorrelationAnalysis($dateRange),
            'trend_comparison' => $this->getTrendComparison($dateRange, $period),
            'predictive_trends' => $this->getPredictiveTrends($dateRange),
        ];
    }

    /**
     * Get alert analytics.
     */
    private function getAlertAnalytics(array $dateRange): array
    {
        return [
            'alert_summary' => $this->getAlertSummary($dateRange),
            'alert_trends' => $this->getAlertTrends($dateRange),
            'critical_alerts' => $this->getCriticalAlerts($dateRange),
            'alert_response_times' => $this->getAlertResponseTimes($dateRange),
            'alert_sources' => $this->getAlertSources($dateRange),
            'alert_resolution' => $this->getAlertResolution($dateRange),
            'recurring_alerts' => $this->getRecurringAlerts($dateRange),
            'alert_impact' => $this->getAlertImpact($dateRange),
        ];
    }

    /**
     * Helper methods for specific analytics calculations.
     */
    private function getDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'month';
        
        return match($period) {
            'day' => [
                now()->startOfDay(),
                now()->endOfDay()
            ],
            'week' => [
                now()->startOfWeek(),
                now()->endOfWeek()
            ],
            'month' => [
                now()->startOfMonth(),
                now()->endOfMonth()
            ],
            'quarter' => [
                now()->startOfQuarter(),
                now()->endOfQuarter()
            ],
            'year' => [
                now()->startOfYear(),
                now()->endOfYear()
            ],
            default => [
                now()->subDays(30),
                now()
            ]
        };
    }

    private function getAssetsNeedingMaintenance(array $dateRange): int
    {
        return Asset::whereHas('workOrders', function ($query) use ($dateRange) {
            $query->where('status', '!=', 'completed')
                   ->whereBetween('scheduled_date', $dateRange);
        })->count();
    }

    private function getCurrentAssetValue(): float
    {
        return Asset::sum('current_value') ?? Asset::sum('purchase_cost') * 0.7;
    }

    private function calculateMTTR(array $dateRange): float
    {
        $completedOrders = WorkOrder::whereBetween('completed_at', $dateRange)
            ->where('status', 'completed')
            ->whereNotNull('downtime_hours')
            ->get();

        if ($completedOrders->isEmpty()) {
            return 0;
        }

        $totalDowntime = $completedOrders->sum('downtime_hours');
        $orderCount = $completedOrders->count();

        return $totalDowntime / $orderCount;
    }

    private function calculateMTBF(array $dateRange): float
    {
        $completedOrders = WorkOrder::whereBetween('completed_at', $dateRange)
            ->where('status', 'completed')
            ->get();

        if ($completedOrders->count() < 2) {
            return 0;
        }

        $totalOperatingTime = 0;
        $previousDate = null;

        foreach ($completedOrders->sortBy('completed_at') as $order) {
            if ($previousDate) {
                $totalOperatingTime += $order->completed_at->diffInHours($previousDate);
            }
            $previousDate = $order->completed_at;
        }

        return $totalOperatingTime / ($completedOrders->count() - 1);
    }

    private function calculateAvailability(array $dateRange): float
    {
        $totalHours = $dateRange[0]->diffInHours($dateRange[1]);
        $downtimeHours = WorkOrder::whereBetween('completed_at', $dateRange)
            ->sum('downtime_hours') ?? 0;

        if ($totalHours == 0) {
            return 100;
        }

        return max(0, 100 - ($downtimeHours / $totalHours * 100));
    }

    private function getAssetDistribution(): array
    {
        return Asset::join('categories', 'assets.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category, COUNT(*) as count, SUM(purchase_cost) as total_value')
            ->groupBy('categories.name')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'count' => $item->count,
                    'total_value' => $item->total_value,
                    'percentage' => ($item->count / Asset::count()) * 100,
                ];
            })
            ->toArray();
    }

    private function getAssetHealthDistribution(): array
    {
        $healthStatuses = [
            'excellent' => Asset::where('health_score', '>=', 0.9)->count(),
            'good' => Asset::whereBetween('health_score', [0.7, 0.89])->count(),
            'fair' => Asset::whereBetween('health_score', [0.5, 0.69])->count(),
            'poor' => Asset::where('health_score', '<', 0.5)->count(),
        ];

        $total = array_sum($healthStatuses);

        return collect($healthStatuses)->map(function ($count, $status) use ($total) {
            return [
                'status' => $status,
                'count' => $count,
                'percentage' => $total > 0 ? ($count / $total) * 100 : 0,
            ];
        })->values()->toArray();
    }

    private function getAssetAgeDistribution(): array
    {
        $now = now();
        $ageRanges = [
            '0-1 year' => Asset::where('created_at', '>=', $now->copy()->subYear())->count(),
            '1-3 years' => Asset::whereBetween('created_at', [$now->copy()->subYears(3), $now->copy()->subYear()])->count(),
            '3-5 years' => Asset::whereBetween('created_at', [$now->copy()->subYears(5), $now->copy()->subYears(3)])->count(),
            '5-10 years' => Asset::whereBetween('created_at', [$now->copy()->subYears(10), $now->copy()->subYears(5)])->count(),
            '10+ years' => Asset::where('created_at', '<', $now->copy()->subYears(10))->count(),
        ];

        $total = array_sum($ageRanges);

        return collect($ageRanges)->map(function ($count, $range) use ($total) {
            return [
                'age_range' => $range,
                'count' => $count,
                'percentage' => $total > 0 ? ($count / $total) * 100 : 0,
            ];
        })->values()->toArray();
    }

    private function getAssetUtilization(array $dateRange, string $period): array
    {
        // Simulate utilization data based on work orders and operating hours
        $utilizationData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $utilizationData[] = [
                'period' => $periodData['label'],
                'utilization_rate' => $this->faker->randomFloat(2, 60, 95),
                'active_assets' => Asset::where('status', 'active')->count(),
                'operating_hours' => $this->faker->numberBetween(100, 1000),
            ];
        }

        return $utilizationData;
    }

    private function getAssetPerformanceMetrics(array $dateRange): array
    {
        return [
            'average_health_score' => Asset::avg('health_score') ?? 0.75,
            'performance_rating' => $this->faker->randomFloat(2, 3.5, 4.8),
            'reliability_score' => $this->faker->randomFloat(2, 0.7, 0.95),
            'efficiency_score' => $this->faker->randomFloat(2, 0.6, 0.9),
            'top_performers' => Asset::orderBy('health_score', 'desc')
                ->limit(5)
                ->get(['name', 'health_score', 'status'])
                ->toArray(),
            'underperformers' => Asset::orderBy('health_score', 'asc')
                ->limit(5)
                ->get(['name', 'health_score', 'status'])
                ->toArray(),
        ];
    }

    private function getDepreciationAnalysis(array $dateRange, string $period): array
    {
        $depreciationData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $depreciationData[] = [
                'period' => $periodData['label'],
                'depreciated_amount' => $this->faker->randomFloat(2, 1000, 50000),
                'accumulated_depreciation' => $this->faker->randomFloat(2, 10000, 500000),
                'book_value' => $this->faker->randomFloat(2, 50000, 1000000),
            ];
        }

        return $depreciationData;
    }

    private function getAssetLifecycleAnalysis(array $dateRange): array
    {
        return [
            'new_assets' => Asset::whereBetween('created_at', $dateRange)->count(),
            'retired_assets' => Asset::where('status', 'retired')
                ->whereBetween('updated_at', $dateRange)
                ->count(),
            'average_lifecycle' => $this->faker->numberBetween(5, 15),
            'lifecycle_stages' => [
                'installation' => Asset::where('status', 'installation')->count(),
                'active' => Asset::where('status', 'active')->count(),
                'maintenance' => Asset::where('status', 'maintenance')->count(),
                'decommissioned' => Asset::where('status', 'retired')->count(),
            ],
            'replacement_forecast' => $this->getReplacementForecast($dateRange),
        ];
    }

    private function getCriticalAssetsAnalytics(array $dateRange): array
    {
        $criticalAssets = Asset::where('criticality', 'critical')
            ->with(['workOrders' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }])
            ->get();

        return [
            'total_critical_assets' => $criticalAssets->count(),
            'critical_assets_with_issues' => $criticalAssets->filter(function ($asset) {
                return $asset->health_score < 0.7 || $asset->workOrders->count() > 0;
            })->count(),
            'critical_asset_health' => $criticalAssets->avg('health_score') ?? 0,
            'critical_downtime' => $criticalAssets->sum(function ($asset) {
                return $asset->workOrders->sum('downtime_hours') ?? 0;
            }),
            'critical_maintenance_cost' => $criticalAssets->sum(function ($asset) {
                return $asset->workOrders->sum('actual_cost') ?? 0;
            }),
        ];
    }

    private function getMaintenanceTrends(array $dateRange, string $period): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'planned_maintenance' => $this->faker->numberBetween(5, 25),
                'unplanned_maintenance' => $this->faker->numberBetween(2, 15),
                'preventive_maintenance' => $this->faker->numberBetween(8, 30),
                'corrective_maintenance' => $this->faker->numberBetween(3, 20),
                'total_cost' => $this->faker->randomFloat(2, 5000, 50000),
            ];
        }

        return $trends;
    }

    private function getMaintenanceCostAnalysis(array $dateRange, string $period): array
    {
        return [
            'total_maintenance_cost' => WorkOrder::whereBetween('completed_at', $dateRange)
                ->sum('actual_cost') ?? 0,
            'cost_per_asset' => $this->faker->randomFloat(2, 100, 2000),
            'cost_trend' => $this->getCostTrend($dateRange, $period),
            'cost_breakdown' => [
                'labor' => $this->faker->randomFloat(2, 2000, 20000),
                'parts' => $this->faker->randomFloat(2, 1000, 15000),
                'external_services' => $this->faker->randomFloat(2, 500, 10000),
                'overhead' => $this->faker->randomFloat(2, 200, 5000),
            ],
            'cost_savings' => $this->faker->randomFloat(2, 1000, 10000),
            'budget_variance' => $this->faker->randomFloat(2, -5000, 5000),
        ];
    }

    private function getMaintenanceTypeDistribution(array $dateRange): array
    {
        return [
            'preventive' => WorkOrder::whereBetween('created_at', $dateRange)
                ->where('type', 'preventive')->count(),
            'corrective' => WorkOrder::whereBetween('created_at', $dateRange)
                ->where('type', 'corrective')->count(),
            'predictive' => WorkOrder::whereBetween('created_at', $dateRange)
                ->where('type', 'predictive')->count(),
            'emergency' => WorkOrder::whereBetween('created_at', $dateRange)
                ->where('type', 'emergency')->count(),
            'inspection' => WorkOrder::whereBetween('created_at', $dateRange)
                ->where('type', 'inspection')->count(),
        ];
    }

    private function getPreventiveCorrectiveRatio(array $dateRange): array
    {
        $preventive = WorkOrder::whereBetween('created_at', $dateRange)
            ->where('type', 'preventive')->count();
        $corrective = WorkOrder::whereBetween('created_at', $dateRange)
            ->where('type', 'corrective')->count();

        $total = $preventive + $corrective;

        return [
            'preventive_count' => $preventive,
            'corrective_count' => $corrective,
            'preventive_percentage' => $total > 0 ? ($preventive / $total) * 100 : 0,
            'corrective_percentage' => $total > 0 ? ($corrective / $total) * 100 : 0,
            'ratio' => $corrective > 0 ? $preventive / $corrective : 0,
        ];
    }

    private function getMaintenanceEfficiency(array $dateRange): array
    {
        return [
            'completion_rate' => $this->calculateCompletionRate($dateRange),
            'on_time_completion' => $this->calculateOnTimeCompletion($dateRange),
            'budget_adherence' => $this->calculateBudgetAdherence($dateRange),
            'resource_utilization' => $this->faker->randomFloat(2, 70, 95),
            'quality_score' => $this->faker->randomFloat(2, 3.5, 4.8),
            'rework_rate' => $this->faker->randomFloat(2, 0, 10),
        ];
    }

    private function getScheduledMaintenanceAnalysis(array $dateRange): array
    {
        $scheduled = WorkOrder::whereBetween('scheduled_date', $dateRange)->count();
        $unscheduled = WorkOrder::whereBetween('created_at', $dateRange)
            ->whereNull('scheduled_date')->count();

        return [
            'scheduled_count' => $scheduled,
            'unscheduled_count' => $unscheduled,
            'scheduled_percentage' => ($scheduled + $unscheduled) > 0 ? ($scheduled / ($scheduled + $unscheduled)) * 100 : 0,
            'compliance_rate' => $this->calculateScheduleCompliance($dateRange),
        ];
    }

    private function getMaintenanceBacklog(): array
    {
        return [
            'total_backlog' => WorkOrder::where('status', '!=', 'completed')->count(),
            'critical_backlog' => WorkOrder::where('priority', 'critical')
                ->where('status', '!=', 'completed')->count(),
            'overdue_tasks' => WorkOrder::where('due_date', '<', now())
                ->where('status', '!=', 'completed')->count(),
            'average_backlog_age' => $this->calculateAverageBacklogAge(),
            'backlog_trend' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
        ];
    }

    private function getVendorPerformance(array $dateRange): array
    {
        return [
            'total_vendors' => $this->faker->numberBetween(5, 20),
            'active_vendors' => $this->faker->numberBetween(3, 15),
            'vendor_ratings' => [
                'excellent' => $this->faker->numberBetween(1, 5),
                'good' => $this->faker->numberBetween(3, 8),
                'average' => $this->faker->numberBetween(2, 6),
                'poor' => $this->faker->numberBetween(0, 3),
            ],
            'average_response_time' => $this->faker->numberBetween(2, 48),
            'cost_effectiveness' => $this->faker->randomFloat(2, 0.7, 1.3),
            'quality_score' => $this->faker->randomFloat(2, 3.0, 4.9),
        ];
    }

    private function getCostAnalysis(array $dateRange, string $period): array
    {
        $costData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $costData[] = [
                'period' => $periodData['label'],
                'maintenance_cost' => $this->faker->randomFloat(2, 5000, 30000),
                'operational_cost' => $this->faker->randomFloat(2, 10000, 50000),
                'depreciation_cost' => $this->faker->randomFloat(2, 2000, 15000),
                'total_cost' => $this->faker->randomFloat(2, 20000, 100000),
            ];
        }

        return $costData;
    }

    private function getBudgetVsActual(array $dateRange, string $period): array
    {
        $budgetData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $budget = $this->faker->randomFloat(2, 20000, 80000);
            $actual = $this->faker->randomFloat(2, 15000, 90000);

            $budgetData[] = [
                'period' => $periodData['label'],
                'budget' => $budget,
                'actual' => $actual,
                'variance' => $actual - $budget,
                'variance_percentage' => $budget > 0 ? (($actual - $budget) / $budget) * 100 : 0,
            ];
        }

        return $budgetData;
    }

    private function getCostPerAsset(array $dateRange): array
    {
        return [
            'average_cost_per_asset' => $this->faker->randomFloat(2, 500, 5000),
            'cost_by_category' => $this->getCostByAssetCategory($dateRange),
            'cost_by_age' => $this->getCostByAssetAge($dateRange),
            'cost_by_criticality' => $this->getCostByAssetCriticality($dateRange),
            'cost_trends' => $this->getCostPerAssetTrends($dateRange),
        ];
    }

    private function getROIAnalysis(array $dateRange): array
    {
        return [
            'maintenance_roi' => $this->faker->randomFloat(2, 1.2, 3.5),
            'asset_roi' => $this->faker->randomFloat(2, 0.8, 2.8),
            'technology_roi' => $this->faker->randomFloat(2, 1.5, 4.2),
            'training_roi' => $this->faker->randomFloat(2, 1.1, 3.0),
            'total_investment' => $this->faker->randomFloat(2, 100000, 1000000),
            'total_returns' => $this->faker->randomFloat(2, 150000, 2000000),
            'payback_period' => $this->faker->numberBetween(12, 48),
        ];
    }

    private function getExpenseBreakdown(array $dateRange): array
    {
        return [
            'personnel' => $this->faker->randomFloat(2, 20000, 100000),
            'materials' => $this->faker->randomFloat(2, 15000, 80000),
            'equipment' => $this->faker->randomFloat(2, 10000, 60000),
            'services' => $this->faker->randomFloat(2, 5000, 40000),
            'overhead' => $this->faker->randomFloat(2, 8000, 30000),
            'contingency' => $this->faker->randomFloat(2, 2000, 15000),
        ];
    }

    private function getSavingsOpportunities(array $dateRange): array
    {
        return [
            'preventive_maintenance' => $this->faker->randomFloat(2, 5000, 25000),
            'energy_efficiency' => $this->faker->randomFloat(2, 3000, 20000),
            'inventory_optimization' => $this->faker->randomFloat(2, 2000, 15000),
            'vendor_negotiation' => $this->faker->randomFloat(2, 1000, 10000),
            'process_improvement' => $this->faker->randomFloat(2, 4000, 30000),
            'technology_upgrades' => $this->faker->randomFloat(2, 8000, 50000),
            'total_potential_savings' => $this->faker->randomFloat(2, 25000, 150000),
        ];
    }

    private function getFinancialTrends(array $dateRange, string $period): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'revenue' => $this->faker->randomFloat(2, 100000, 500000),
                'expenses' => $this->faker->randomFloat(2, 80000, 400000),
                'profit' => $this->faker->randomFloat(2, 10000, 150000),
                'profit_margin' => $this->faker->randomFloat(2, 5, 25),
            ];
        }

        return $trends;
    }

    private function getAssetValuation(array $dateRange): array
    {
        return [
            'current_book_value' => $this->getCurrentAssetValue(),
            'market_value' => $this->faker->randomFloat(2, 500000, 2000000),
            'replacement_cost' => $this->faker->randomFloat(2, 600000, 2500000),
            'salvage_value' => $this->faker->randomFloat(2, 50000, 300000),
            'valuation_trend' => $this->faker->randomElement(['appreciating', 'depreciating', 'stable']),
            'revaluation_needed' => $this->faker->boolean(30),
        ];
    }

    private function getOperationalPerformance(array $dateRange, string $period): array
    {
        return [
            'overall_efficiency' => $this->faker->randomFloat(2, 70, 95),
            'productivity_rate' => $this->faker->randomFloat(2, 80, 98),
            'quality_score' => $this->faker->randomFloat(2, 3.5, 4.9),
            'safety_score' => $this->faker->randomFloat(2, 4.0, 5.0),
            'compliance_rate' => $this->faker->randomFloat(2, 85, 99),
            'performance_trends' => $this->getPerformanceTrends($dateRange, $period),
        ];
    }

    private function getUtilizationRates(array $dateRange, string $period): array
    {
        $utilizationData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $utilizationData[] = [
                'period' => $periodData['label'],
                'asset_utilization' => $this->faker->randomFloat(2, 60, 90),
                'capacity_utilization' => $this->faker->randomFloat(2, 50, 85),
                'resource_utilization' => $this->faker->randomFloat(2, 70, 95),
                'time_utilization' => $this->faker->randomFloat(2, 65, 92),
            ];
        }

        return $utilizationData;
    }

    private function getDowntimeAnalysis(array $dateRange, string $period): array
    {
        $downtimeData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $downtimeData[] = [
                'period' => $periodData['label'],
                'planned_downtime' => $this->faker->randomFloat(2, 5, 50),
                'unplanned_downtime' => $this->faker->randomFloat(2, 2, 30),
                'total_downtime' => $this->faker->randomFloat(2, 10, 80),
                'downtime_cost' => $this->faker->randomFloat(2, 1000, 20000),
            ];
        }

        return $downtimeData;
    }

    private function getProductivityMetrics(array $dateRange): array
    {
        return [
            'output_per_hour' => $this->faker->randomFloat(2, 50, 500),
            'output_per_employee' => $this->faker->randomFloat(2, 1000, 10000),
            'efficiency_ratio' => $this->faker->randomFloat(2, 0.7, 0.95),
            'throughput_rate' => $this->faker->randomFloat(2, 80, 98),
            'cycle_time' => $this->faker->randomFloat(2, 5, 60),
            'yield_rate' => $this->faker->randomFloat(2, 85, 99),
        ];
    }

    private function getResourceAllocation(array $dateRange): array
    {
        return [
            'personnel_allocation' => [
                'technicians' => $this->faker->numberBetween(10, 50),
                'engineers' => $this->faker->numberBetween(5, 25),
                'managers' => $this->faker->numberBetween(2, 10),
                'support_staff' => $this->faker->numberBetween(5, 20),
            ],
            'equipment_allocation' => [
                'active' => $this->faker->numberBetween(50, 200),
                'maintenance' => $this->faker->numberBetween(5, 25),
                'standby' => $this->faker->numberBetween(10, 40),
            ],
            'budget_allocation' => [
                'maintenance' => $this->faker->randomFloat(2, 20000, 100000),
                'operations' => $this->faker->randomFloat(2, 50000, 200000),
                'capital' => $this->faker->randomFloat(2, 10000, 50000),
            ],
        ];
    }

    private function getEfficiencyScores(array $dateRange): array
    {
        return [
            'overall_efficiency' => $this->faker->randomFloat(2, 70, 95),
            'maintenance_efficiency' => $this->faker->randomFloat(2, 65, 90),
            'operational_efficiency' => $this->faker->randomFloat(2, 75, 98),
            'resource_efficiency' => $this->faker->randomFloat(2, 60, 85),
            'energy_efficiency' => $this->faker->randomFloat(2, 70, 92),
            'time_efficiency' => $this->faker->randomFloat(2, 68, 88),
        ];
    }

    private function getBottleneckAnalysis(array $dateRange): array
    {
        return [
            'identified_bottlenecks' => $this->faker->numberBetween(2, 8),
            'critical_bottlenecks' => $this->faker->numberBetween(1, 3),
            'impact_assessment' => [
                'high_impact' => $this->faker->numberBetween(0, 2),
                'medium_impact' => $this->faker->numberBetween(1, 4),
                'low_impact' => $this->faker->numberBetween(2, 6),
            ],
            'resolution_status' => [
                'resolved' => $this->faker->numberBetween(1, 4),
                'in_progress' => $this->faker->numberBetween(1, 3),
                'pending' => $this->faker->numberBetween(0, 2),
            ],
        ];
    }

    private function getCapacityPlanning(array $dateRange): array
    {
        return [
            'current_capacity' => $this->faker->randomFloat(2, 70, 90),
            'projected_demand' => $this->faker->randomFloat(2, 75, 95),
            'capacity_gap' => $this->faker->randomFloat(2, -10, 20),
            'expansion_needed' => $this->faker->boolean(60),
            'investment_required' => $this->faker->randomFloat(2, 50000, 500000),
            'timeline' => $this->faker->numberBetween(6, 24),
        ];
    }

    private function getSensorHealthMetrics(array $dateRange): array
    {
        return [
            'total_sensors' => Sensor::count(),
            'healthy_sensors' => Sensor::where('status', 'active')->count(),
            'sensors_with_alerts' => Sensor::whereHas('alerts', function ($query) {
                $query->where('acknowledged_at', null);
            })->count(),
            'sensor_uptime' => $this->faker->randomFloat(2, 95, 99.5),
            'average_signal_strength' => $this->faker->randomFloat(2, 70, 95),
            'battery_health' => $this->faker->randomFloat(2, 80, 98),
        ];
    }

    private function getSensorDataQuality(array $dateRange): array
    {
        return [
            'total_readings' => SensorReading::whereBetween('timestamp', $dateRange)->count(),
            'high_quality_readings' => SensorReading::whereBetween('timestamp', $dateRange)
                ->where('quality', '>=', 0.8)->count(),
            'data_completeness' => $this->faker->randomFloat(2, 85, 98),
            'data_accuracy' => $this->faker->randomFloat(2, 90, 99),
            'missing_data_points' => $this->faker->numberBetween(10, 500),
            'anomalous_readings' => $this->faker->numberBetween(5, 100),
        ];
    }

    private function getSensorAlertTrends(array $dateRange, string $period): array
    {
        $alertTrends = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $alertTrends[] = [
                'period' => $periodData['label'],
                'total_alerts' => $this->faker->numberBetween(5, 50),
                'critical_alerts' => $this->faker->numberBetween(0, 10),
                'resolved_alerts' => $this->faker->numberBetween(3, 40),
                'response_time' => $this->faker->randomFloat(2, 5, 120),
            ];
        }

        return $alertTrends;
    }

    private function getEnergyConsumptionAnalytics(array $dateRange, string $period): array
    {
        $energyData = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $energyData[] = [
                'period' => $periodData['label'],
                'total_consumption' => $this->faker->randomFloat(2, 1000, 10000),
                'peak_consumption' => $this->faker->randomFloat(2, 500, 5000),
                'average_consumption' => $this->faker->randomFloat(2, 200, 2000),
                'cost' => $this->faker->randomFloat(2, 500, 5000),
                'efficiency_score' => $this->faker->randomFloat(2, 0.7, 0.95),
            ];
        }

        return $energyData;
    }

    private function getEnvironmentalMonitoring(array $dateRange): array
    {
        return [
            'temperature_readings' => $this->faker->numberBetween(1000, 10000),
            'humidity_readings' => $this->faker->numberBetween(500, 5000),
            'pressure_readings' => $this->faker->numberBetween(200, 2000),
            'air_quality_readings' => $this->faker->numberBetween(100, 1000),
            'average_temperature' => $this->faker->randomFloat(2, 18, 28),
            'humidity_levels' => $this->faker->randomFloat(2, 40, 70),
            'environmental_alerts' => $this->faker->numberBetween(0, 20),
        ];
    }

    private function getPredictiveAlertAnalytics(array $dateRange): array
    {
        return [
            'total_predictions' => Prediction::whereBetween('prediction_date', $dateRange)->count(),
            'high_risk_predictions' => Prediction::whereBetween('prediction_date', $dateRange)
                ->whereIn('risk_level', ['high', 'critical'])->count(),
            'prediction_accuracy' => $this->faker->randomFloat(4, 0.75, 0.95),
            'false_positive_rate' => $this->faker->randomFloat(4, 0.05, 0.25),
            'early_warnings' => $this->faker->numberBetween(10, 50),
            'prevented_failures' => $this->faker->numberBetween(5, 25),
        ];
    }

    private function getSensorUtilization(array $dateRange): array
    {
        return [
            'active_sensors' => Sensor::where('status', 'active')->count(),
            'sensor_readings_per_day' => $this->faker->numberBetween(100, 1000),
            'data_transmission_rate' => $this->faker->randomFloat(2, 90, 99),
            'storage_utilization' => $this->faker->randomFloat(2, 60, 85),
            'bandwidth_utilization' => $this->faker->randomFloat(2, 40, 75),
        ];
    }

    private function getConnectivityMetrics(array $dateRange): array
    {
        return [
            'connected_devices' => Sensor::where('status', 'active')->count(),
            'connection_success_rate' => $this->faker->randomFloat(2, 95, 99.5),
            'average_latency' => $this->faker->randomFloat(2, 10, 500),
            'packet_loss_rate' => $this->faker->randomFloat(4, 0.01, 0.1),
            'network_uptime' => $this->faker->randomFloat(2, 99, 99.9),
            'failed_connections' => $this->faker->numberBetween(0, 50),
        ];
    }

    private function getModelPerformanceAnalytics(array $dateRange): array
    {
        return [
            'active_models' => PredictiveModel::where('is_active', true)->count(),
            'models_retrained' => ModelTrainingHistory::whereBetween('training_completed_at', $dateRange)
                ->where('training_status', 'completed')->count(),
            'average_accuracy' => PredictiveModel::avg('accuracy_score') ?? 0,
            'top_performing_models' => PredictiveModel::orderBy('accuracy_score', 'desc')
                ->limit(5)
                ->get(['name', 'accuracy_score', 'model_type']),
            'models_needing_attention' => PredictiveModel::where('accuracy_score', '<', 0.7)
                ->orWhere('next_retrain_at', '<', now())->count(),
        ];
    }

    private function getPredictionAccuracyAnalytics(array $dateRange): array
    {
        return [
            'total_predictions' => Prediction::whereBetween('prediction_date', $dateRange)->count(),
            'validated_predictions' => Prediction::whereBetween('prediction_date', $dateRange)
                ->where('validation_status', 'validated')->count(),
            'average_accuracy' => Prediction::whereBetween('prediction_date', $dateRange)
                ->where('validation_status', 'validated')
                ->avg('accuracy_score') ?? 0,
            'accuracy_by_model_type' => $this->getAccuracyByModelType($dateRange),
            'accuracy_trends' => $this->getAccuracyTrends($dateRange),
        ];
    }

    private function getRiskAssessmentAnalytics(array $dateRange): array
    {
        return [
            'high_risk_assets' => $this->faker->numberBetween(5, 25),
            'risk_distribution' => [
                'critical' => $this->faker->numberBetween(1, 5),
                'high' => $this->faker->numberBetween(3, 10),
                'medium' => $this->faker->numberBetween(5, 15),
                'low' => $this->faker->numberBetween(10, 30),
            ],
            'risk_trends' => $this->getRiskTrends($dateRange),
            'mitigation_actions' => $this->faker->numberBetween(10, 50),
            'risk_reduction' => $this->faker->randomFloat(2, 10, 40),
        ];
    }

    private function getRecommendationEffectiveness(array $dateRange): array
    {
        return [
            'total_recommendations' => MaintenanceRecommendation::whereBetween('created_at', $dateRange)->count(),
            'implemented_recommendations' => MaintenanceRecommendation::whereBetween('created_at', $dateRange)
                ->where('status', 'completed')->count(),
            'effectiveness_score' => $this->faker->randomFloat(2, 3.5, 4.8),
            'cost_savings' => $this->faker->randomFloat(2, 5000, 50000),
            'downtime_reduction' => $this->faker->randomFloat(2, 5, 50),
            'failure_prevention' => $this->faker->numberBetween(5, 25),
        ];
    }

    private function getFailurePredictionAnalytics(array $dateRange): array
    {
        return [
            'failure_predictions' => Prediction::whereBetween('prediction_date', $dateRange)
                ->where('prediction_type', 'failure_probability')->count(),
            'predicted_failures' => $this->faker->numberBetween(5, 20),
            'actual_failures' => $this->faker->numberBetween(3, 15),
            'prediction_accuracy' => $this->faker->randomFloat(4, 0.7, 0.9),
            'early_detection_rate' => $this->faker->randomFloat(2, 60, 90),
            'false_positive_rate' => $this->faker->randomFloat(4, 0.1, 0.3),
        ];
    }

    private function getMaintenanceOptimizationAnalytics(array $dateRange): array
    {
        return [
            'optimized_schedules' => $this->faker->numberBetween(10, 50),
            'cost_reduction' => $this->faker->randomFloat(2, 5, 25),
            'downtime_reduction' => $this->faker->randomFloat(2, 10, 40),
            'resource_optimization' => $this->faker->randomFloat(2, 15, 35),
            'efficiency_improvement' => $this->faker->randomFloat(2, 20, 50),
            'scheduling_accuracy' => $this->faker->randomFloat(2, 70, 95),
        ];
    }

    private function getPredictiveCostSavings(array $dateRange): array
    {
        return [
            'total_savings' => $this->faker->randomFloat(2, 25000, 250000),
            'maintenance_cost_savings' => $this->faker->randomFloat(2, 10000, 100000),
            'downtime_cost_savings' => $this->faker->randomFloat(2, 5000, 50000),
            'equipment_cost_savings' => $this->faker->randomFloat(2, 8000, 80000),
            'operational_cost_savings' => $this->faker->randomFloat(2, 2000, 20000),
            'savings_roi' => $this->faker->randomFloat(2, 2.5, 5.5),
        ];
    }

    private function getModelDriftAnalytics(array $dateRange): array
    {
        return [
            'models_with_drift' => $this->faker->numberBetween(1, 5),
            'drift_detection_rate' => $this->faker->randomFloat(2, 0.1, 0.3),
            'retraining_frequency' => $this->faker->numberBetween(1, 12),
            'performance_degradation' => $this->faker->randomFloat(4, 0.05, 0.2),
            'drift_impact' => $this->faker->randomElement(['low', 'medium', 'high']),
            'mitigation_actions' => $this->faker->numberBetween(2, 8),
        ];
    }

    private function getKPIMetrics(array $dateRange, string $period): array
    {
        return [
            'asset_availability' => $this->calculateAvailability($dateRange),
            'maintenance_cost_per_asset' => $this->faker->randomFloat(2, 500, 3000),
            'mean_time_between_failures' => $this->calculateMTBF($dateRange),
            'mean_time_to_repair' => $this->calculateMTTR($dateRange),
            'overall_equipment_effectiveness' => $this->faker->randomFloat(2, 70, 90),
            'planned_maintenance_percentage' => $this->faker->randomFloat(2, 70, 90),
            'schedule_compliance' => $this->faker->randomFloat(2, 80, 95),
        ];
    }

    private function getPerformanceTrends(array $dateRange, string $period): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'availability' => $this->faker->randomFloat(2, 85, 98),
                'efficiency' => $this->faker->randomFloat(2, 70, 95),
                'quality' => $this->faker->randomFloat(2, 3.5, 4.9),
                'productivity' => $this->faker->randomFloat(2, 80, 98),
                'safety' => $this->faker->randomFloat(2, 4.0, 5.0),
            ];
        }

        return $trends;
    }

    private function getBenchmarkComparison(array $dateRange): array
    {
        return [
            'industry_average' => [
                'availability' => 85,
                'mttr' => 4.5,
                'mtbf' => 168,
                'maintenance_cost' => 1500,
            ],
            'current_performance' => [
                'availability' => $this->calculateAvailability($dateRange),
                'mttr' => $this->calculateMTTR($dateRange),
                'mtbf' => $this->calculateMTBF($dateRange),
                'maintenance_cost' => $this->faker->randomFloat(2, 1000, 2000),
            ],
            'performance_gap' => [
                'availability' => $this->faker->randomFloat(2, -5, 10),
                'mttr' => $this->faker->randomFloat(2, -2, 3),
                'mtbf' => $this->faker->randomFloat(2, -20, 50),
                'maintenance_cost' => $this->faker->randomFloat(2, -500, 500),
            ],
        ];
    }

    private function getPerformanceScores(array $dateRange): array
    {
        return [
            'overall_score' => $this->faker->randomFloat(2, 3.5, 4.8),
            'asset_performance' => $this->faker->randomFloat(2, 3.0, 4.9),
            'maintenance_performance' => $this->faker->randomFloat(2, 3.2, 4.7),
            'operational_performance' => $this->faker->randomFloat(2, 3.4, 4.8),
            'financial_performance' => $this->faker->randomFloat(2, 3.1, 4.6),
            'safety_performance' => $this->faker->randomFloat(2, 3.8, 5.0),
        ];
    }

    private function getImprovementAreas(array $dateRange): array
    {
        return [
            'critical_areas' => [
                'downtime_reduction' => [
                    'current_score' => $this->faker->randomFloat(2, 2.5, 3.5),
                    'target_score' => 4.5,
                    'potential_impact' => 'high',
                ],
                'maintenance_efficiency' => [
                    'current_score' => $this->faker->randomFloat(2, 3.0, 4.0),
                    'target_score' => 4.5,
                    'potential_impact' => 'medium',
                ],
                'cost_optimization' => [
                    'current_score' => $this->faker->randomFloat(2, 2.8, 3.8),
                    'target_score' => 4.2,
                    'potential_impact' => 'high',
                ],
            ],
            'improvement_priority' => ['downtime_reduction', 'cost_optimization', 'maintenance_efficiency'],
            'estimated_benefits' => $this->faker->randomFloat(2, 50000, 500000),
        ];
    }

    private function getGoalTracking(array $dateRange): array
    {
        return [
            'goals_met' => $this->faker->numberBetween(3, 8),
            'goals_in_progress' => $this->faker->numberBetween(2, 5),
            'goals_missed' => $this->faker->numberBetween(0, 2),
            'overall_progress' => $this->faker->randomFloat(2, 70, 95),
            'key_achievements' => [
                'reduced_downtime_by' => $this->faker->randomFloat(2, 10, 30),
                'improved_efficiency_by' => $this->faker->randomFloat(2, 5, 20),
                'saved_costs' => $this->faker->randomFloat(2, 10000, 100000),
            ],
        ];
    }

    private function getPerformanceForecasts(array $dateRange): array
    {
        return [
            'next_quarter_forecast' => [
                'availability' => $this->faker->randomFloat(2, 88, 96),
                'maintenance_cost' => $this->faker->randomFloat(2, 45000, 55000),
                'efficiency' => $this->faker->randomFloat(2, 75, 92),
                'productivity' => $this->faker->randomFloat(2, 85, 97),
            ],
            'next_year_forecast' => [
                'availability' => $this->faker->randomFloat(2, 90, 98),
                'maintenance_cost' => $this->faker->randomFloat(2, 180000, 220000),
                'efficiency' => $this->faker->randomFloat(2, 80, 95),
                'productivity' => $this->faker->randomFloat(2, 90, 99),
            ],
            'confidence_level' => $this->faker->randomFloat(2, 0.7, 0.9),
        ];
    }

    private function getEfficiencyGains(array $dateRange): array
    {
        return [
            'productivity_improvement' => $this->faker->randomFloat(2, 5, 25),
            'cost_reduction' => $this->faker->randomFloat(2, 3, 15),
            'time_savings' => $this->faker->randomFloat(2, 10, 40),
            'quality_improvement' => $this->faker->randomFloat(2, 2, 12),
            'resource_optimization' => $this->faker->randomFloat(2, 8, 30),
            'total_value_created' => $this->faker->randomFloat(2, 50000, 500000),
        ];
    }

    private function getHistoricalTrends(array $dateRange, string $period): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'asset_count' => Asset::where('created_at', '<=', $periodData['end'])->count(),
                'maintenance_cost' => $this->faker->randomFloat(2, 10000, 50000),
                'downtime_hours' => $this->faker->randomFloat(2, 10, 100),
                'efficiency_score' => $this->faker->randomFloat(2, 70, 95),
            ];
        }

        return $trends;
    }

    private function getSeasonalPatterns(array $dateRange): array
    {
        return [
            'seasonal_factors' => [
                'spring' => $this->faker->randomFloat(2, 0.9, 1.1),
                'summer' => $this->faker->randomFloat(2, 0.8, 1.2),
                'fall' => $this->faker->randomFloat(2, 0.9, 1.1),
                'winter' => $this->faker->randomFloat(2, 0.8, 1.2),
            ],
            'peak_periods' => [
                'maintenance' => $this->faker->randomElement(['Q2', 'Q3']),
                'downtime' => $this->faker->randomElement(['Q1', 'Q4']),
                'efficiency' => $this->faker->randomElement(['Q2', 'Q3']),
            ],
            'seasonal_variance' => $this->faker->randomFloat(2, 10, 30),
        ];
    }

    private function getGrowthRates(array $dateRange, string $period): array
    {
        return [
            'asset_growth_rate' => $this->faker->randomFloat(2, -5, 15),
            'cost_growth_rate' => $this->faker->randomFloat(2, -3, 12),
            'efficiency_growth_rate' => $this->faker->randomFloat(2, 0, 8),
            'productivity_growth_rate' => $this->faker->randomFloat(2, 2, 10),
            'compound_annual_growth' => $this->faker->randomFloat(2, 3, 12),
        ];
    }

    private function getTrendForecasting(array $dateRange, string $period): array
    {
        return [
            'short_term_forecast' => [
                'next_period_value' => $this->faker->randomFloat(2, 100000, 200000),
                'confidence_interval' => [
                    'lower' => $this->faker->randomFloat(2, 80000, 150000),
                    'upper' => $this->faker->randomFloat(2, 120000, 250000),
                ],
                'trend_direction' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
            ],
            'long_term_forecast' => [
                'next_year_value' => $this->faker->randomFloat(2, 400000, 800000),
                'five_year_projection' => $this->faker->randomFloat(2, 2000000, 4000000),
                'growth_trajectory' => $this->faker->randomElement(['linear', 'exponential', 'logarithmic']),
            ],
        ];
    }

    private function getTrendAnomalies(array $dateRange): array
    {
        return [
            'detected_anomalies' => $this->faker->numberBetween(2, 8),
            'anomaly_types' => [
                'spike' => $this->faker->numberBetween(0, 3),
                'drop' => $this->faker->numberBetween(0, 2),
                'pattern_change' => $this->faker->numberBetween(1, 3),
            ],
            'anomaly_impact' => [
                'high' => $this->faker->numberBetween(0, 2),
                'medium' => $this->faker->numberBetween(1, 4),
                'low' => $this->faker->numberBetween(2, 5),
            ],
            'investigation_required' => $this->faker->numberBetween(1, 4),
        ];
    }

    private function getCorrelationAnalysis(array $dateRange): array
    {
        return [
            'strong_correlations' => [
                ['metric1' => 'maintenance_cost', 'metric2' => 'asset_age', 'correlation' => $this->faker->randomFloat(4, 0.6, 0.9)],
                ['metric1' => 'downtime', 'metric2' => 'maintenance_delay', 'correlation' => $this->faker->randomFloat(4, 0.5, 0.8)],
            ],
            'moderate_correlations' => [
                ['metric1' => 'efficiency', 'metric2' => 'training_hours', 'correlation' => $this->faker->randomFloat(4, 0.3, 0.6)],
                ['metric1' => 'quality', 'metric2' => 'inspection_frequency', 'correlation' => $this->faker->randomFloat(4, 0.2, 0.5)],
            ],
            'weak_correlations' => [
                ['metric1' => 'cost', 'metric2' => 'weather', 'correlation' => $this->faker->randomFloat(4, 0, 0.3)],
            ],
        ];
    }

    private function getTrendComparison(array $dateRange, string $period): array
    {
        return [
            'period_over_period' => [
                'current_vs_previous' => $this->faker->randomFloat(2, -10, 25),
                'growth_rate' => $this->faker->randomFloat(2, -5, 15),
                'performance_change' => $this->faker->randomFloat(2, -8, 20),
            ],
            'year_over_year' => [
                'annual_growth' => $this->faker->randomFloat(2, -3, 12),
                'performance_trend' => $this->faker->randomElement(['improving', 'declining', 'stable']),
                'key_changes' => $this->faker->numberBetween(1, 5),
            ],
        ];
    }

    private function getPredictiveTrends(array $dateRange): array
    {
        return [
            'predicted_trends' => [
                'next_quarter' => [
                    'maintenance_cost' => $this->faker->randomFloat(2, 45000, 55000),
                    'downtime_hours' => $this->faker->randomFloat(2, 20, 80),
                    'efficiency_score' => $this->faker->randomFloat(2, 75, 92),
                ],
                'next_year' => [
                    'maintenance_cost' => $this->faker->randomFloat(2, 180000, 220000),
                    'downtime_hours' => $this->faker->randomFloat(2, 100, 400),
                    'efficiency_score' => $this->faker->randomFloat(2, 80, 95),
                ],
            ],
            'confidence_levels' => [
                'high_confidence' => $this->faker->randomFloat(2, 0.8, 0.95),
                'medium_confidence' => $this->faker->randomFloat(2, 0.6, 0.8),
                'low_confidence' => $this->faker->randomFloat(2, 0.4, 0.6),
            ],
        ];
    }

    private function getAlertSummary(array $dateRange): array
    {
        return [
            'total_alerts' => $this->faker->numberBetween(50, 200),
            'critical_alerts' => $this->faker->numberBetween(5, 25),
            'resolved_alerts' => $this->faker->numberBetween(30, 150),
            'pending_alerts' => $this->faker->numberBetween(10, 50),
            'average_resolution_time' => $this->faker->randomFloat(2, 2, 48),
            'alert_trend' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
        ];
    }

    private function getAlertTrends(array $dateRange): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, 'week');

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'total_alerts' => $this->faker->numberBetween(5, 25),
                'critical_alerts' => $this->faker->numberBetween(0, 5),
                'resolved_alerts' => $this->faker->numberBetween(3, 20),
                'response_time' => $this->faker->randomFloat(2, 1, 24),
            ];
        }

        return $trends;
    }

    private function getCriticalAlerts(array $dateRange): array
    {
        return [
            'total_critical' => $this->faker->numberBetween(5, 25),
            'unacknowledged' => $this->faker->numberBetween(2, 10),
            'overdue' => $this->faker->numberBetween(1, 8),
            'escalated' => $this->faker->numberBetween(0, 5),
            'average_resolution_time' => $this->faker->randomFloat(2, 1, 12),
            'impact_assessment' => [
                'high_impact' => $this->faker->numberBetween(2, 10),
                'medium_impact' => $this->faker->numberBetween(3, 15),
                'low_impact' => $this->faker->numberBetween(0, 5),
            ],
        ];
    }

    private function getAlertResponseTimes(array $dateRange): array
    {
        return [
            'average_response_time' => $this->faker->randomFloat(2, 5, 120),
            'median_response_time' => $this->faker->randomFloat(2, 3, 60),
            'response_time_distribution' => [
                'under_1_hour' => $this->faker->numberBetween(10, 30),
                '1_6_hours' => $this->faker->numberBetween(20, 50),
                '6_24_hours' => $this->faker->numberBetween(15, 40),
                'over_24_hours' => $this->faker->numberBetween(5, 25),
            ],
            'response_time_trend' => $this->faker->randomElement(['improving', 'stable', 'degrading']),
        ];
    }

    private function getAlertSources(array $dateRange): array
    {
        return [
            'iot_sensors' => $this->faker->numberBetween(20, 80),
            'manual_reports' => $this->faker->numberBetween(10, 40),
            'automated_systems' => $this->faker->numberBetween(15, 60),
            'external_integrations' => $this->faker->numberBetween(5, 25),
            'predictive_models' => $this->faker->numberBetween(8, 35),
            'user_reports' => $this->faker->numberBetween(3, 20),
        ];
    }

    private function getAlertResolution(array $dateRange): array
    {
        return [
            'resolution_rate' => $this->faker->randomFloat(2, 70, 95),
            'first_time_resolution' => $this->faker->randomFloat(2, 60, 85),
            'recurring_issues' => $this->faker->numberBetween(5, 25),
            'resolution_methods' => [
                'automated' => $this->faker->numberBetween(10, 40),
                'manual_intervention' => $this->faker->numberBetween(30, 80),
                'external_support' => $this->faker->numberBetween(5, 25),
            ],
            'customer_satisfaction' => $this->faker->randomFloat(2, 3.5, 4.8),
        ];
    }

    private function getRecurringAlerts(array $dateRange): array
    {
        return [
            'recurring_alert_count' => $this->faker->numberBetween(5, 30),
            'most_common_issues' => [
                'sensor_communication' => $this->faker->numberBetween(2, 10),
                'high_temperature' => $this->faker->numberBetween(1, 8),
                'low_battery' => $this->faker->numberBetween(3, 12),
                'calibration_needed' => $this->faker->numberBetween(1, 6),
            ],
            'repeat_frequency' => [
                'daily' => $this->faker->numberBetween(1, 5),
                'weekly' => $this->faker->numberBetween(2, 8),
                'monthly' => $this->faker->numberBetween(3, 10),
            ],
            'root_cause_identified' => $this->faker->boolean(70),
        ];
    }

    private function getAlertImpact(array $dateRange): array
    {
        return [
            'business_impact' => [
                'downtime_hours' => $this->faker->randomFloat(2, 10, 100),
                'revenue_loss' => $this->faker->randomFloat(2, 1000, 50000),
                'customer_impact' => $this->faker->numberBetween(5, 50),
            ],
            'operational_impact' => [
                'resource_allocation' => $this->faker->numberBetween(10, 50),
                'schedule_disruption' => $this->faker->numberBetween(5, 25),
                'productivity_loss' => $this->faker->randomFloat(2, 5, 30),
            ],
            'financial_impact' => [
                'direct_costs' => $this->faker->randomFloat(2, 5000, 50000),
                'indirect_costs' => $this->faker->randomFloat(2, 2000, 20000),
                'opportunity_cost' => $this->faker->randomFloat(2, 1000, 15000),
            ],
        ];
    }

    // Helper methods for generating data
    private function generatePeriods(array $dateRange, string $period): array
    {
        $periods = [];
        $start = $dateRange[0];
        $end = $dateRange[1];

        switch ($period) {
            case 'day':
                for ($i = 0; $i < 24; $i++) {
                    $periods[] = [
                        'label' => $i . ':00',
                        'start' => $start->copy()->addHours($i),
                        'end' => $start->copy()->addHours($i + 1),
                    ];
                }
                break;
            case 'week':
                for ($i = 0; $i < 7; $i++) {
                    $periods[] = [
                        'label' => $start->copy()->addDays($i)->format('D'),
                        'start' => $start->copy()->addDays($i),
                        'end' => $start->copy()->addDays($i + 1),
                    ];
                }
                break;
            case 'month':
                for ($i = 0; $i < 30; $i++) {
                    $periods[] = [
                        'label' => $start->copy()->addDays($i)->format('d M'),
                        'start' => $start->copy()->addDays($i),
                        'end' => $start->copy()->addDays($i + 1),
                    ];
                }
                break;
            case 'quarter':
                for ($i = 0; $i < 3; $i++) {
                    $periods[] = [
                        'label' => 'Month ' . ($i + 1),
                        'start' => $start->copy()->addMonths($i),
                        'end' => $start->copy()->addMonths($i + 1),
                    ];
                }
                break;
            case 'year':
                for ($i = 0; $i < 12; $i++) {
                    $periods[] = [
                        'label' => $start->copy()->addMonths($i)->format('M'),
                        'start' => $start->copy()->addMonths($i),
                        'end' => $start->copy()->addMonths($i + 1),
                    ];
                }
                break;
        }

        return $periods;
    }

    private function calculateCompletionRate(array $dateRange): float
    {
        $total = WorkOrder::whereBetween('created_at', $dateRange)->count();
        $completed = WorkOrder::whereBetween('completed_at', $dateRange)
            ->where('status', 'completed')->count();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    private function calculateOnTimeCompletion(array $dateRange): float
    {
        $completed = WorkOrder::whereBetween('completed_at', $dateRange)
            ->where('status', 'completed')
            ->get();

        if ($completed->isEmpty()) {
            return 0;
        }

        $onTime = $completed->filter(function ($order) {
            return $order->completed_at <= $order->due_date;
        })->count();

        return ($onTime / $completed->count()) * 100;
    }

    private function calculateBudgetAdherence(array $dateRange): float
    {
        $completed = WorkOrder::whereBetween('completed_at', $dateRange)
            ->where('status', 'completed')
            ->whereNotNull('estimated_cost')
            ->whereNotNull('actual_cost')
            ->get();

        if ($completed->isEmpty()) {
            return 0;
        }

        $withinBudget = $completed->filter(function ($order) {
            return $order->actual_cost <= $order->estimated_cost * 1.1; // 10% tolerance
        })->count();

        return ($withinBudget / $completed->count()) * 100;
    }

    private function calculateScheduleCompliance(array $dateRange): float
    {
        $scheduled = WorkOrder::whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', $dateRange)
            ->get();

        if ($scheduled->isEmpty()) {
            return 0;
        }

        $onTime = $scheduled->filter(function ($order) {
            return $order->completed_at && $order->completed_at <= $order->scheduled_date->addDays(1);
        })->count();

        return ($onTime / $scheduled->count()) * 100;
    }

    private function calculateAverageBacklogAge(): float
    {
        $backlog = WorkOrder::where('status', '!=', 'completed')->get();

        if ($backlog->isEmpty()) {
            return 0;
        }

        $totalAge = $backlog->sum(function ($order) {
            return $order->created_at->diffInDays(now());
        });

        return $totalAge / $backlog->count();
    }

    private function getCostTrend(array $dateRange, string $period): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, $period);

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'cost' => $this->faker->randomFloat(2, 5000, 30000),
                'trend' => $this->faker->randomElement(['up', 'down', 'stable']),
            ];
        }

        return $trends;
    }

    private function getCostByAssetCategory(array $dateRange): array
    {
        return [
            'machinery' => $this->faker->randomFloat(2, 10000, 50000),
            'vehicles' => $this->faker->randomFloat(2, 5000, 25000),
            'equipment' => $this->faker->randomFloat(2, 8000, 40000),
            'buildings' => $this->faker->randomFloat(2, 15000, 75000),
            'it_assets' => $this->faker->randomFloat(2, 3000, 15000),
        ];
    }

    private function getCostByAssetAge(array $dateRange): array
    {
        return [
            '0-3_years' => $this->faker->randomFloat(2, 2000, 10000),
            '3-5_years' => $this->faker->randomFloat(2, 3000, 15000),
            '5-10_years' => $this->faker->randomFloat(2, 5000, 25000),
            '10+_years' => $this->faker->randomFloat(2, 8000, 40000),
        ];
    }

    private function getCostByAssetCriticality(array $dateRange): array
    {
        return [
            'critical' => $this->faker->randomFloat(2, 10000, 50000),
            'important' => $this->faker->randomFloat(2, 5000, 25000),
            'normal' => $this->faker->randomFloat(2, 2000, 10000),
            'low' => $this->faker->randomFloat(2, 1000, 5000),
        ];
    }

    private function getCostPerAssetTrends(array $dateRange): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, 'month');

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'cost_per_asset' => $this->faker->randomFloat(2, 500, 3000),
                'trend' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
            ];
        }

        return $trends;
    }

    private function getReplacementForecast(array $dateRange): array
    {
        return [
            'next_quarter' => $this->faker->numberBetween(2, 8),
            'next_year' => $this->faker->numberBetween(8, 25),
            'next_3_years' => $this->faker->numberBetween(25, 60),
            'estimated_cost' => $this->faker->randomFloat(2, 100000, 1000000),
            'priority_assets' => $this->faker->numberBetween(1, 5),
        ];
    }

    private function getAccuracyByModelType(array $dateRange): array
    {
        return [
            'failure_prediction' => $this->faker->randomFloat(4, 0.75, 0.92),
            'remaining_useful_life' => $this->faker->randomFloat(4, 0.70, 0.88),
            'anomaly_detection' => $this->faker->randomFloat(4, 0.80, 0.95),
            'predictive_maintenance' => $this->faker->randomFloat(4, 0.72, 0.90),
        ];
    }

    private function getAccuracyTrends(array $dateRange): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, 'week');

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'accuracy' => $this->faker->randomFloat(4, 0.75, 0.95),
                'predictions_count' => $this->faker->numberBetween(10, 100),
            ];
        }

        return $trends;
    }

    private function getRiskTrends(array $dateRange): array
    {
        $trends = [];
        $periods = $this->generatePeriods($dateRange, 'week');

        foreach ($periods as $periodData) {
            $trends[] = [
                'period' => $periodData['label'],
                'high_risk_count' => $this->faker->numberBetween(2, 15),
                'medium_risk_count' => $this->faker->numberBetween(5, 25),
                'low_risk_count' => $this->faker->numberBetween(10, 40),
            ];
        }

        return $trends;
    }
}
