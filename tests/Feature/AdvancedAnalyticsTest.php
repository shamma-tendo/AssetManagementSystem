<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\PredictiveModel;
use App\Models\Prediction;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdvancedAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class AdvancedAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected AdvancedAnalyticsService $analyticsService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = app(AdvancedAnalyticsService::class);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for analytics.
     */
    private function createTestData(): void
    {
        // Create assets
        Asset::factory()->count(50)->create();
        
        // Create work orders
        WorkOrder::factory()->count(100)->create();
        
        // Create sensors
        Sensor::factory()->count(30)->create();
        
        // Create sensor readings
        SensorReading::factory()->count(500)->create();
        
        // Create predictive models
        PredictiveModel::factory()->count(10)->create();
        
        // Create predictions
        Prediction::factory()->count(200)->create();
    }

    /**
     * Test comprehensive dashboard analytics.
     */
    public function test_dashboard_analytics(): void
    {
        $response = $this->getJson('/api/analytics/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'overview',
                         'asset_analytics',
                         'maintenance_analytics',
                         'financial_analytics',
                         'operational_analytics',
                         'iot_analytics',
                         'predictive_analytics',
                         'performance_analytics',
                         'trends',
                         'alerts',
                     ],
                     'filters_applied',
                     'generated_at',
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('total_assets', $data['overview']);
        $this->assertArrayHasKey('active_assets', $data['overview']);
        $this->assertArrayHasKey('critical_work_orders', $data['overview']);
        $this->assertArrayHasKey('mttr', $data['overview']);
        $this->assertArrayHasKey('availability', $data['overview']);
    }

    /**
     * Test dashboard analytics with filters.
     */
    public function test_dashboard_analytics_with_filters(): void
    {
        $filters = [
            'period' => 'week',
            'date_from' => now()->subWeek()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'category' => 'machinery',
        ];

        $response = $this->getJson('/api/analytics/dashboard?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'filters_applied',
                     'generated_at',
                 ]);

        $this->assertEquals($filters, $response->json('filters_applied'));
    }

    /**
     * Test overview metrics.
     */
    public function test_overview_metrics(): void
    {
        $response = $this->getJson('/api/analytics/overview');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'total_assets',
                         'active_assets',
                         'assets_need_maintenance',
                         'critical_work_orders',
                         'total_work_orders',
                         'completed_work_orders',
                         'total_value',
                         'depreciated_value',
                         'maintenance_cost',
                         'downtime_hours',
                         'mttr',
                         'mtbf',
                         'availability',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsInt($data['total_assets']);
        $this->assertIsInt($data['active_assets']);
        $this->assertIsFloat($data['total_value']);
        $this->assertIsFloat($data['mttr']);
        $this->assertIsFloat($data['availability']);
    }

    /**
     * Test asset analytics.
     */
    public function test_asset_analytics(): void
    {
        $response = $this->getJson('/api/analytics/assets');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'asset_distribution',
                         'asset_health',
                         'asset_age_distribution',
                         'asset_utilization',
                         'asset_performance',
                         'depreciation_analysis',
                         'asset_lifecycle',
                         'critical_assets',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['asset_distribution']);
        $this->assertIsArray($data['asset_health']);
        $this->assertIsArray($data['asset_age_distribution']);
        $this->assertIsArray($data['asset_utilization']);
    }

    /**
     * Test asset analytics with filters.
     */
    public function test_asset_analytics_with_filters(): void
    {
        $filters = [
            'period' => 'month',
            'category_id' => Asset::factory()->create()->category_id,
            'criticality' => 'critical',
        ];

        $response = $this->getJson('/api/analytics/assets?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertEquals($filters, $response->json('filters_applied'));
    }

    /**
     * Test maintenance analytics.
     */
    public function test_maintenance_analytics(): void
    {
        $response = $this->getJson('/api/analytics/maintenance');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'maintenance_trends',
                         'maintenance_costs',
                         'maintenance_types',
                         'preventive_vs_corrective',
                         'maintenance_efficiency',
                         'scheduled_vs_unscheduled',
                         'maintenance_backlog',
                         'vendor_performance',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['maintenance_trends']);
        $this->assertIsArray($data['maintenance_types']);
        $this->assertIsArray($data['preventive_vs_corrective']);
        $this->assertIsArray($data['maintenance_efficiency']);
    }

    /**
     * Test financial analytics.
     */
    public function test_financial_analytics(): void
    {
        $response = $this->getJson('/api/analytics/financial');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'cost_analysis',
                         'budget_vs_actual',
                         'cost_per_asset',
                         'roi_analysis',
                         'expense_breakdown',
                         'savings_opportunities',
                         'financial_trends',
                         'asset_valuation',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['cost_analysis']);
        $this->assertIsArray($data['budget_vs_actual']);
        $this->assertIsArray($data['roi_analysis']);
        $this->assertIsArray($data['expense_breakdown']);
    }

    /**
     * Test operational analytics.
     */
    public function test_operational_analytics(): void
    {
        $response = $this->getJson('/api/analytics/operational');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'performance_metrics',
                         'utilization_rates',
                         'downtime_analysis',
                         'productivity_metrics',
                         'resource_allocation',
                         'efficiency_scores',
                         'bottleneck_analysis',
                         'capacity_planning',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['performance_metrics']);
        $this->assertIsArray($data['utilization_rates']);
        $this->assertIsArray($data['downtime_analysis']);
        $this->assertIsArray($data['productivity_metrics']);
    }

    /**
     * Test IoT analytics.
     */
    public function test_iot_analytics(): void
    {
        $response = $this->getJson('/api/analytics/iot');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'sensor_health',
                         'data_quality',
                         'alert_trends',
                         'energy_consumption',
                         'environmental_monitoring',
                         'predictive_alerts',
                         'sensor_utilization',
                         'connectivity_metrics',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['sensor_health']);
        $this->assertIsArray($data['data_quality']);
        $this->assertIsArray($data['alert_trends']);
        $this->assertIsArray($data['energy_consumption']);
    }

    /**
     * Test predictive analytics.
     */
    public function test_predictive_analytics(): void
    {
        $response = $this->getJson('/api/analytics/predictive');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'model_performance',
                         'prediction_accuracy',
                         'risk_assessment',
                         'recommendation_effectiveness',
                         'failure_predictions',
                         'maintenance_optimization',
                         'cost_savings',
                         'model_drift',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['model_performance']);
        $this->assertIsArray($data['prediction_accuracy']);
        $this->assertIsArray($data['risk_assessment']);
        $this->assertIsArray($data['recommendation_effectiveness']);
    }

    /**
     * Test performance analytics.
     */
    public function test_performance_analytics(): void
    {
        $response = $this->getJson('/api/analytics/performance');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'kpi_metrics',
                         'performance_trends',
                         'benchmark_comparison',
                         'performance_scores',
                         'improvement_areas',
                         'goal_tracking',
                         'performance_forecasts',
                         'efficiency_gains',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['kpi_metrics']);
        $this->assertIsArray($data['performance_trends']);
        $this->assertIsArray($data['benchmark_comparison']);
        $this->assertIsArray($data['performance_scores']);
    }

    /**
     * Test trend analytics.
     */
    public function test_trend_analytics(): void
    {
        $response = $this->getJson('/api/analytics/trends');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'historical_trends',
                         'seasonal_patterns',
                         'growth_rates',
                         'forecasting',
                         'anomaly_detection',
                         'correlation_analysis',
                         'trend_comparison',
                         'predictive_trends',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['historical_trends']);
        $this->assertIsArray($data['seasonal_patterns']);
        $this->assertIsArray($data['growth_rates']);
        $this->assertIsArray($data['forecasting']);
    }

    /**
     * Test alert analytics.
     */
    public function test_alert_analytics(): void
    {
        $response = $this->getJson('/api/analytics/alerts');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'alert_summary',
                         'alert_trends',
                         'critical_alerts',
                         'alert_response_times',
                         'alert_sources',
                         'alert_resolution',
                         'recurring_alerts',
                         'alert_impact',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['alert_summary']);
        $this->assertIsArray($data['alert_trends']);
        $this->assertIsArray($data['critical_alerts']);
        $this->assertIsArray($data['alert_response_times']);
    }

    /**
     * Test real-time analytics.
     */
    public function test_real_time_analytics(): void
    {
        $response = $this->getJson('/api/analytics/real-time');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'current_status',
                         'real_time_metrics',
                         'live_stream',
                         'timestamp',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('active_assets', $data['current_status']);
        $this->assertArrayHasKey('ongoing_maintenance', $data['current_status']);
        $this->assertArrayHasKey('critical_alerts', $data['current_status']);
        $this->assertArrayHasKey('system_health', $data['current_status']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    /**
     * Test comparative analytics.
     */
    public function test_comparative_analytics(): void
    {
        $filters = [
            'comparison_type' => 'period_over_period',
            'period' => 'month',
            'baseline_period' => now()->subMonth()->format('Y-m-d'),
            'target_period' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/analytics/comparative', $filters);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'filters_applied',
                 ]);

        $this->assertEquals($filters, $response->json('filters_applied'));
    }

    /**
     * Test comparative analytics validation.
     */
    public function test_comparative_analytics_validation(): void
    {
        $response = $this->postJson('/api/analytics/comparative', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'comparison_type',
                 ]);
    }

    /**
     * Test year over year comparison.
     */
    public function test_year_over_year_comparison(): void
    {
        $filters = [
            'comparison_type' => 'year_over_year',
            'period' => 'year',
        ];

        $response = $this->postJson('/api/analytics/comparative', $filters);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('current_year', $data);
        $this->assertArrayHasKey('previous_year', $data);
        $this->assertArrayHasKey('growth', $data);
    }

    /**
     * Test benchmark comparison.
     */
    public function test_benchmark_comparison(): void
    {
        $filters = [
            'comparison_type' => 'benchmark',
            'period' => 'month',
        ];

        $response = $this->postJson('/api/analytics/comparative', $filters);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('industry_average', $data);
        $this->assertArrayHasKey('current_performance', $data);
        $this->assertArrayHasKey('performance_gap', $data);
    }

    /**
     * Test custom report generation.
     */
    public function test_custom_report_generation(): void
    {
        $reportData = [
            'report_name' => 'Custom Maintenance Report',
            'metrics' => ['maintenance_cost', 'downtime_hours', 'completion_rate'],
            'period' => 'month',
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'format' => 'json',
        ];

        $response = $this->postJson('/api/analytics/custom-report', $reportData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Report data generated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'name',
                         'generated_at',
                         'period',
                         'date_range',
                         'data',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertEquals($reportData['report_name'], $data['name']);
        $this->assertArrayHasKey('maintenance_cost', $data['data']);
        $this->assertArrayHasKey('downtime_hours', $data['data']);
        $this->assertArrayHasKey('completion_rate', $data['data']);
    }

    /**
     * Test custom report validation.
     */
    public function test_custom_report_validation(): void
    {
        $response = $this->postJson('/api/analytics/custom-report', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'report_name',
                     'metrics',
                 ]);
    }

    /**
     * Test data export.
     */
    public function test_data_export(): void
    {
        $exportData = [
            'data_type' => 'dashboard',
            'format' => 'json',
            'period' => 'month',
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/analytics/export', $exportData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Export data generated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'export_id',
                         'data_type',
                         'format',
                         'status',
                         'file_size',
                         'download_url',
                         'expires_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals($exportData['data_type'], $data['data_type']);
        $this->assertEquals($exportData['format'], $data['format']);
        $this->assertEquals('ready', $data['status']);
    }

    /**
     * Test data export validation.
     */
    public function test_data_export_validation(): void
    {
        $response = $this->postJson('/api/analytics/export', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'data_type',
                     'format',
                 ]);
    }

    /**
     * Test analytics insights.
     */
    public function test_analytics_insights(): void
    {
        $response = $this->getJson('/api/analytics/insights');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'key_insights',
                         'recommendations',
                         'action_items',
                     ],
                     'filters_applied',
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['key_insights']);
        $this->assertIsArray($data['recommendations']);
        $this->assertIsArray($data['action_items']);
    }

    /**
     * Test performance insights.
     */
    public function test_performance_insights(): void
    {
        $filters = [
            'insight_type' => 'performance',
            'period' => 'month',
        ];

        $response = $this->getJson('/api/analytics/insights?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('key_insights', $data);
        $this->assertArrayHasKey('recommendations', $data);
        $this->assertArrayHasKey('action_items', $data);
    }

    /**
     * Test cost insights.
     */
    public function test_cost_insights(): void
    {
        $filters = [
            'insight_type' => 'cost',
            'period' => 'quarter',
        ];

        $response = $this->getJson('/api/analytics/insights?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('key_insights', $data);
        $this->assertArrayHasKey('recommendations', $data);
        $this->assertArrayHasKey('potential_savings', $data);
    }

    /**
     * Test efficiency insights.
     */
    public function test_efficiency_insights(): void
    {
        $filters = [
            'insight_type' => 'efficiency',
            'period' => 'month',
        ];

        $response = $this->getJson('/api/analytics/insights?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('key_insights', $data);
        $this->assertArrayHasKey('recommendations', $data);
        $this->assertArrayHasKey('efficiency_gains', $data);
    }

    /**
     * Test risk insights.
     */
    public function test_risk_insights(): void
    {
        $filters = [
            'insight_type' => 'risk',
            'period' => 'week',
        ];

        $response = $this->getJson('/api/analytics/insights?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('key_insights', $data);
        $this->assertArrayHasKey('recommendations', $data);
        $this->assertArrayHasKey('risk_assessment', $data);
    }

    /**
     * Test opportunity insights.
     */
    public function test_opportunity_insights(): void
    {
        $filters = [
            'insight_type' => 'opportunity',
            'period' => 'year',
        ];

        $response = $this->getJson('/api/analytics/insights?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('key_insights', $data);
        $this->assertArrayHasKey('recommendations', $data);
        $this->assertArrayHasKey('opportunities', $data);
    }

    /**
     * Test analytics settings.
     */
    public function test_analytics_settings(): void
    {
        $response = $this->getJson('/api/analytics/settings');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'available_periods',
                         'available_metrics',
                         'available_filters',
                         'cache_settings',
                         'export_formats',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data['available_periods']);
        $this->assertIsArray($data['available_metrics']);
        $this->assertIsArray($data['available_filters']);
        $this->assertIsArray($data['export_formats']);
    }

    /**
     * Test cache clearing.
     */
    public function test_cache_clearing(): void
    {
        $cacheData = [
            'cache_type' => 'dashboard',
        ];

        $response = $this->postJson('/api/analytics/clear-cache', $cacheData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Cache cleared successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'cache_type',
                         'cleared_keys',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals($cacheData['cache_type'], $data['cache_type']);
    }

    /**
     * Test full cache clearing.
     */
    public function test_full_cache_clearing(): void
    {
        $cacheData = [
            'cache_type' => 'all',
        ];

        $response = $this->postJson('/api/analytics/clear-cache', $cacheData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Cache cleared successfully',
                 ]);

        $data = $response->json('data');
        $this->assertEquals($cacheData['cache_type'], $data['cache_type']);
    }

    /**
     * Test analytics validation.
     */
    public function test_analytics_validation(): void
    {
        // Test invalid period
        $response = $this->getJson('/api/analytics/dashboard?period=invalid');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'period',
                 ]);

        // Test invalid date range
        $response = $this->getJson('/api/analytics/dashboard?date_from=invalid-date');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'date_from',
                 ]);

        // Test date_to before date_from
        $response = $this->getJson('/api/analytics/dashboard?date_from=2024-01-01&date_to=2023-12-31');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'date_to',
                 ]);
    }

    /**
     * Test analytics with different periods.
     */
    public function test_analytics_with_different_periods(): void
    {
        $periods = ['day', 'week', 'month', 'quarter', 'year'];

        foreach ($periods as $period) {
            $response = $this->getJson("/api/analytics/overview?period={$period}");

            $response->assertStatus(200)
                     ->assertJson([
                         'success' => true,
                     ]);

            $filters = $response->json('filters_applied');
            $this->assertEquals($period, $filters['period']);
        }
    }

    /**
     * Test analytics with date range.
     */
    public function test_analytics_with_date_range(): void
    {
        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->getJson("/api/analytics/overview?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $filters = $response->json('filters_applied');
        $this->assertEquals($dateFrom, $filters['date_from']);
        $this->assertEquals($dateTo, $filters['date_to']);
    }

    /**
     * Test analytics service methods.
     */
    public function test_analytics_service_methods(): void
    {
        $dateRange = [
            now()->subMonth(),
            now()
        ];

        // Test dashboard analytics
        $dashboard = $this->analyticsService->getDashboardAnalytics(['period' => 'month']);
        $this->assertArrayHasKey('overview', $dashboard);
        $this->assertArrayHasKey('asset_analytics', $dashboard);
        $this->assertArrayHasKey('maintenance_analytics', $dashboard);

        // Test overview metrics
        $overview = $this->analyticsService->getOverviewMetrics($dateRange);
        $this->assertArrayHasKey('total_assets', $overview);
        $this->assertArrayHasKey('mttr', $overview);
        $this->assertArrayHasKey('availability', $overview);

        // Test asset analytics
        $assetAnalytics = $this->analyticsService->getAssetAnalytics($dateRange, 'month');
        $this->assertArrayHasKey('asset_distribution', $assetAnalytics);
        $this->assertArrayHasKey('asset_health', $assetAnalytics);

        // Test maintenance analytics
        $maintenanceAnalytics = $this->analyticsService->getMaintenanceAnalytics($dateRange, 'month');
        $this->assertArrayHasKey('maintenance_trends', $maintenanceAnalytics);
        $this->assertArrayHasKey('maintenance_costs', $maintenanceAnalytics);
    }

    /**
     * Test analytics without authentication.
     */
    public function test_analytics_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/analytics/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/analytics/overview');
        $response->assertStatus(401);
    }

    /**
     * Test analytics with insufficient permissions.
     */
    public function test_analytics_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read analytics data
        $response = $this->getJson('/api/analytics/dashboard');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/analytics/overview');
        $response->assertStatus(200);
        
        // But not be able to clear cache
        $response = $this->postJson('/api/analytics/clear-cache', ['cache_type' => 'all']);
        $response->assertStatus(403);
    }

    /**
     * Test analytics caching.
     */
    public function test_analytics_caching(): void
    {
        // First call should generate and cache data
        $response1 = $this->getJson('/api/analytics/overview');
        $response1->assertStatus(200);

        // Second call should return cached data
        $response2 = $this->getJson('/api/analytics/overview');
        $response2->assertStatus(200);

        // Both responses should have the same data
        $this->assertEquals($response1->json('data'), $response2->json('data'));
    }

    /**
     * Test analytics performance.
     */
    public function test_analytics_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/analytics/dashboard');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Analytics should respond within reasonable time (less than 5 seconds)
        $this->assertLessThan(5000, $responseTime, 'Analytics response time should be less than 5 seconds');
    }

    /**
     * Test analytics data consistency.
     */
    public function test_analytics_data_consistency(): void
    {
        // Get overview metrics
        $overviewResponse = $this->getJson('/api/analytics/overview');
        $overviewData = $overviewResponse->json('data');

        // Get dashboard analytics
        $dashboardResponse = $this->getJson('/api/analytics/dashboard');
        $dashboardData = $dashboardResponse->json('data');

        // Overview metrics should match dashboard overview
        $this->assertEquals($overviewData['total_assets'], $dashboardData['overview']['total_assets']);
        $this->assertEquals($overviewData['active_assets'], $dashboardData['overview']['active_assets']);
        $this->assertEquals($overviewData['mttr'], $dashboardData['overview']['mttr']);
        $this->assertEquals($overviewData['availability'], $dashboardData['overview']['availability']);
    }

    /**
     * Test analytics error handling.
     */
    public function test_analytics_error_handling(): void
    {
        // Test with invalid filters that might cause errors
        $response = $this->getJson('/api/analytics/dashboard?category=nonexistent');

        $response->assertStatus(200); // Should handle gracefully
        $this->assertJson([
            'success' => true,
        ]);
    }

    /**
     * Test analytics data types.
     */
    public function test_analytics_data_types(): void
    {
        $response = $this->getJson('/api/analytics/overview');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Test data types
        $this->assertIsInt($data['total_assets']);
        $this->assertIsInt($data['active_assets']);
        $this->assertIsInt($data['critical_work_orders']);
        $this->assertIsInt($data['total_work_orders']);
        $this->assertIsInt($data['completed_work_orders']);
        $this->assertIsFloat($data['total_value']);
        $this->assertIsFloat($data['depreciated_value']);
        $this->assertIsFloat($data['maintenance_cost']);
        $this->assertIsFloat($data['downtime_hours']);
        $this->assertIsFloat($data['mttr']);
        $this->assertIsFloat($data['mtbf']);
        $this->assertIsFloat($data['availability']);
    }

    /**
     * Test analytics with large date ranges.
     */
    public function test_analytics_with_large_date_ranges(): void
    {
        $dateFrom = now()->subYear()->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->getJson("/api/analytics/overview?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('total_assets', $data);
        $this->assertArrayHasKey('maintenance_cost', $data);
    }

    /**
     * Test analytics with multiple filters.
     */
    public function test_analytics_with_multiple_filters(): void
    {
        $filters = [
            'period' => 'month',
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'category' => 'machinery',
            'location' => 'factory_a',
            'asset_type' => 'equipment',
        ];

        $response = $this->getJson('/api/analytics/overview?' . http_build_query($filters));

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertEquals($filters, $response->json('filters_applied'));
    }
}
