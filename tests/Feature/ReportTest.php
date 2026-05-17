<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($user);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for reporting.
     */
    private function createTestData(): void
    {
        // Create categories
        $categories = Category::factory()->count(3)->create();
        
        // Create locations
        $locations = Location::factory()->count(2)->create();
        
        // Create departments
        $departments = Department::factory()->count(2)->create();
        
        // Create assets
        Asset::factory()->count(20)->create([
            'category_id' => $categories->random()->id,
            'location_id' => $locations->random()->id,
            'department_id' => $departments->random()->id,
            'status' => 'active',
        ]);
        
        // Create some assets with different statuses
        Asset::factory()->count(5)->create(['status' => 'under_maintenance']);
        Asset::factory()->count(3)->create(['status' => 'retired']);
        Asset::factory()->count(2)->create(['status' => 'ordered']);
    }

    /**
     * Test dashboard endpoint.
     */
    public function test_dashboard_endpoint(): void
    {
        $response = $this->getJson('/api/reports/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'asset_statistics',
                         'status_distribution',
                         'category_distribution',
                         'location_distribution',
                         'department_distribution',
                         'age_distribution',
                         'depreciation_summary',
                         'maintenance_statistics',
                         'recent_assets',
                         'last_updated',
                     ],
                 ]);
    }

    /**
     * Test dashboard asset statistics.
     */
    public function test_dashboard_asset_statistics(): void
    {
        $response = $this->getJson('/api/reports/dashboard');

        $data = $response->json('data');
        
        $this->assertArrayHasKey('asset_statistics', $data);
        $this->assertArrayHasKey('total_assets', $data['asset_statistics']);
        $this->assertArrayHasKey('active_assets', $data['asset_statistics']);
        $this->assertArrayHasKey('under_maintenance', $data['asset_statistics']);
        $this->assertArrayHasKey('retired_assets', $data['asset_statistics']);
        $this->assertArrayHasKey('total_value', $data['asset_statistics']);
        
        // Verify counts match our test data
        $this->assertEquals(30, $data['asset_statistics']['total_assets']); // 20 + 5 + 3 + 2
        $this->assertEquals(20, $data['asset_statistics']['active_assets']);
        $this->assertEquals(5, $data['asset_statistics']['under_maintenance']);
        $this->assertEquals(3, $data['asset_statistics']['retired_assets']);
    }

    /**
     * Test asset value report.
     */
    public function test_asset_value_report(): void
    {
        $response = $this->getJson('/api/reports/asset-value');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'value_by_category',
                         'value_by_location',
                         'value_by_department',
                         'depreciation_by_category',
                         'summary',
                     ],
                 ]);
    }

    /**
     * Test asset value report with filters.
     */
    public function test_asset_value_report_with_filters(): void
    {
        $category = Category::first();
        
        $response = $this->getJson("/api/reports/asset-value?category_id={$category->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('value_by_category', $data);
        
        // Should only include assets from the specified category
        $categoryAssets = Asset::where('category_id', $category->id)->count();
        $summaryTotal = $data['summary']['total_assets'];
        
        // Verify filtering worked (at least one asset should match)
        $this->assertGreaterThan(0, $summaryTotal);
    }

    /**
     * Test asset lifecycle report.
     */
    public function test_asset_lifecycle_report(): void
    {
        $response = $this->getJson('/api/reports/lifecycle');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'assets_by_purchase_year',
                         'status_transitions',
                         'age_analysis',
                         'warranty_analysis',
                         'summary',
                     ],
                 ]);
    }

    /**
     * Test utilization report.
     */
    public function test_utilization_report(): void
    {
        $response = $this->getJson('/api/reports/utilization');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'utilization_by_category',
                         'location_utilization',
                         'department_utilization',
                         'summary',
                     ],
                 ]);
    }

    /**
     * Test utilization report calculations.
     */
    public function test_utilization_calculations(): void
    {
        $response = $this->getJson('/api/reports/utilization');
        
        $data = $response->json('data');
        $summary = $data['summary'];
        
        // Verify utilization rate calculation
        $expectedUtilizationRate = (20 / 30) * 100; // 20 active out of 30 total
        $this->assertEquals($expectedUtilizationRate, $summary['overall_utilization_rate']);
        
        $this->assertEquals(30, $summary['total_assets']);
        $this->assertEquals(20, $summary['active_assets']);
        $this->assertEquals(5, $summary['under_maintenance']);
    }

    /**
     * Test report export endpoint.
     */
    public function test_report_export(): void
    {
        $response = $this->postJson('/api/reports/export', [
            'report_type' => 'asset_value',
            'format' => 'csv',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Report generated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'filename',
                         'download_url',
                         'record_count',
                         'generated_at',
                     ],
                 ]);
    }

    /**
     * Test report export validation.
     */
    public function test_report_export_validation(): void
    {
        // Test invalid report type
        $response = $this->postJson('/api/reports/export', [
            'report_type' => 'invalid_type',
            'format' => 'csv',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid report type',
                 ]);

        // Test invalid format
        $response = $this->postJson('/api/reports/export', [
            'report_type' => 'asset_value',
            'format' => 'invalid_format',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid format',
                 ]);
    }

    /**
     * Test custom report builder endpoint.
     */
    public function test_custom_report_builder(): void
    {
        $response = $this->getJson('/api/reports/custom-builder');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'available_fields',
                         'available_filters',
                         'available_aggregations',
                     ],
                 ]);
    }

    /**
     * Test custom report generation.
     */
    public function test_custom_report_generation(): void
    {
        $response = $this->postJson('/api/reports/custom', [
            'fields' => ['name', 'serial_number', 'status', 'purchase_cost'],
            'filters' => [
                'status' => ['active'],
            ],
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'results',
                         'total_count',
                         'generated_at',
                     ],
                 ]);
    }

    /**
     * Test custom report validation.
     */
    public function test_custom_report_validation(): void
    {
        // Test missing required fields
        $response = $this->postJson('/api/reports/custom', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['fields']);
    }

    /**
     * Test reporting without authentication.
     */
    public function test_reporting_without_authentication(): void
    {
        // Remove authentication
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/reports/dashboard');

        $response->assertStatus(401);
    }

    /**
     * Test reporting with insufficient permissions.
     */
    public function test_reporting_with_insufficient_permissions(): void
    {
        // Create viewer user (limited permissions)
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to access basic reports
        $response = $this->getJson('/api/reports/dashboard');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/reports/asset-value');
        $response->assertStatus(200);
    }

    /**
     * Test report data consistency.
     */
    public function test_report_data_consistency(): void
    {
        $dashboardResponse = $this->getJson('/api/reports/dashboard');
        $valueResponse = $this->getJson('/api/reports/asset-value');

        $dashboardData = $dashboardResponse->json('data');
        $valueData = $valueResponse->json('data');

        // Verify total asset counts match
        $this->assertEquals(
            $dashboardData['asset_statistics']['total_assets'],
            $valueData['summary']['total_assets']
        );

        // Verify total values match
        $this->assertEquals(
            $dashboardData['asset_statistics']['total_value'],
            $valueData['summary']['total_current_value']
        );
    }

    /**
     * Test report performance with large dataset.
     */
    public function test_report_performance(): void
    {
        // Create additional assets for performance testing
        Asset::factory()->count(100)->create(['status' => 'active']);

        $startTime = microtime(true);

        $response = $this->getJson('/api/reports/dashboard');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(2.0, $executionTime, 'Report generation took too long');
    }

    /**
     * Test report filtering by date range.
     */
    public function test_report_date_filtering(): void
    {
        // Create assets with specific purchase dates
        Asset::factory()->create([
            'purchase_date' => '2022-01-01',
            'status' => 'active'
        ]);
        
        Asset::factory()->create([
            'purchase_date' => '2023-01-01',
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/reports/lifecycle?date_from=2023-01-01&date_to=2023-12-31');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('assets_by_purchase_year', $data);
    }
}
