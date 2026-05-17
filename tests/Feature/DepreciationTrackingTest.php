<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\DepreciationMethod;
use App\Models\DepreciationEntry;
use App\Models\User;
use App\Models\UserRole;
use App\Services\DepreciationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class DepreciationTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected DepreciationService $depreciationService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->depreciationService = app(DepreciationService);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for depreciation tracking.
     */
    private function createTestData(): void
    {
        // Create depreciation methods
        DepreciationMethod::factory()->create([
            'name' => 'Straight Line',
            'code' => 'straight_line',
            'description' => 'Equal depreciation over useful life',
            'formula' => '(Cost - Salvage) / Useful Life',
        ]);
        
        DepreciationMethod::factory()->create([
            'name' => 'Declining Balance',
            'code' => 'declining_balance',
            'description' => 'Accelerated depreciation method',
            'formula' => 'Book Value × Rate',
        ]);
        
        DepreciationMethod::factory()->create([
            'name' => 'Sum of Years',
            'code' => 'sum_of_years',
            'description' => 'Sum-of-the-years digits method',
            'formula' => 'Remaining Life / Sum of Years × (Cost - Salvage)',
        ]);
        
        // Create assets
        Asset::factory()->count(10)->create(['purchase_date' => now()->subYears(2)]);
        
        // Create depreciation schedules
        AssetDepreciation::factory()->count(5)->create();
        
        // Create depreciation entries
        DepreciationEntry::factory()->count(20)->create();
    }

    /**
     * Test depreciation schedules listing.
     */
    public function test_depreciation_schedules_listing(): void
    {
        $response = $this->getJson('/api/depreciation');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'pagination',
                 ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /**
     * Test depreciation schedule creation.
     */
    public function test_depreciation_schedule_creation(): void
    {
        $asset = Asset::factory()->create(['purchase_cost' => 10000]);
        $method = DepreciationMethod::where('code', 'straight_line')->first();

        $depreciationData = [
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 5,
            'depreciation_start_date' => now()->format('Y-m-d'),
            'notes' => 'Test depreciation schedule',
        ];

        $response = $this->postJson('/api/depreciation', $depreciationData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Depreciation schedule created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'asset',
                         'depreciationMethod',
                         'purchase_cost',
                         'salvage_value',
                         'useful_life_years',
                         'annual_depreciation',
                         'monthly_depreciation',
                     ],
                 ]);

        $this->assertDatabaseHas('asset_depreciations', [
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 5,
        ]);
    }

    /**
     * Test depreciation schedule creation validation.
     */
    public function test_depreciation_schedule_creation_validation(): void
    {
        $response = $this->postJson('/api/depreciation', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'asset_id',
                     'depreciation_method_id',
                     'purchase_cost',
                     'useful_life_years',
                     'depreciation_start_date',
                 ]);
    }

    /**
     * Test depreciation schedule creation with duplicate asset.
     */
    public function test_depreciation_schedule_creation_duplicate_asset(): void
    {
        $asset = Asset::factory()->create();
        $method = DepreciationMethod::first();
        
        // Create first depreciation schedule
        AssetDepreciation::factory()->create([
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
        ]);

        // Try to create second depreciation schedule for same asset
        $depreciationData = [
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
            'purchase_cost' => 5000,
            'useful_life_years' => 3,
            'depreciation_start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/depreciation', $depreciationData);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Asset already has a depreciation schedule',
                 ]);
    }

    /**
     * Test depreciation schedule show.
     */
    public function test_depreciation_schedule_show(): void
    {
        $depreciation = AssetDepreciation::first();

        $response = $this->getJson("/api/depreciation/{$depreciation->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'asset',
                         'depreciationMethod',
                         'depreciationEntries',
                         'creator',
                         'updater',
                     ],
                 ]);
    }

    /**
     * Test depreciation schedule update.
     */
    public function test_depreciation_schedule_update(): void
    {
        $depreciation = AssetDepreciation::factory()->create();
        $method = DepreciationMethod::where('code', 'declining_balance')->first();

        $updateData = [
            'salvage_value' => 2000,
            'useful_life_years' => 7,
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/depreciation/{$depreciation->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Depreciation schedule updated successfully',
                 ]);

        $this->assertDatabaseHas('asset_depreciations', [
            'id' => $depreciation->id,
            'salvage_value' => 2000,
            'useful_life_years' => 7,
            'notes' => 'Updated notes',
        ]);
    }

    /**
     * Test depreciation schedule deletion.
     */
    public function test_depreciation_schedule_deletion(): void
    {
        $depreciation = AssetDepreciation::factory()->create();

        $response = $this->deleteJson("/api/depreciation/{$depreciation->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Depreciation schedule deleted successfully',
                 ]);

        $this->assertSoftDeleted('asset_depreciations', ['id' => $depreciation->id]);
    }

    /**
     * Test depreciation schedule deletion with entries.
     */
    public function test_depreciation_schedule_deletion_with_entries(): void
    {
        $depreciation = AssetDepreciation::factory()->create();
        DepreciationEntry::factory()->create(['asset_depreciation_id' => $depreciation->id]);

        $response = $this->deleteJson("/api/depreciation/{$depreciation->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete depreciation schedule with entries',
                 ]);
    }

    /**
     * Test depreciation entries listing.
     */
    public function test_depreciation_entries_listing(): void
    {
        $response = $this->getJson('/api/depreciation/entries');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'pagination',
                 ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /**
     * Test depreciation entry creation.
     */
    public function test_depreciation_entry_creation(): void
    {
        $depreciation = AssetDepreciation::factory()->create([
            'current_book_value' => 8000,
            'accumulated_depreciation' => 2000,
        ]);

        $entryData = [
            'asset_depreciation_id' => $depreciation->id,
            'period_date' => now()->format('Y-m-d'),
            'depreciation_amount' => 500,
            'description' => 'Monthly depreciation entry',
        ];

        $response = $this->postJson('/api/depreciation/entries', $entryData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Depreciation entry created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'period_date',
                         'depreciation_amount',
                         'book_value_before',
                         'book_value_after',
                         'assetDepreciation',
                         'creator',
                     ],
                 ]);

        $this->assertDatabaseHas('depreciation_entries', [
            'asset_depreciation_id' => $depreciation->id,
            'depreciation_amount' => 500,
        ]);

        // Check that depreciation schedule was updated
        $depreciation->refresh();
        $this->assertEquals(2500, $depreciation->accumulated_depreciation);
        $this->assertEquals(7500, $depreciation->current_book_value);
    }

    /**
     * Test depreciation entry creation validation.
     */
    public function test_depreciation_entry_creation_validation(): void
    {
        $response = $this->postJson('/api/depreciation/entries', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'asset_depreciation_id',
                     'period_date',
                     'depreciation_amount',
                 ]);
    }

    /**
     * Test depreciation entry creation duplicate period.
     */
    public function test_depreciation_entry_creation_duplicate_period(): void
    {
        $depreciation = AssetDepreciation::factory()->create();
        $periodDate = now()->format('Y-m-d');
        
        // Create first entry
        DepreciationEntry::factory()->create([
            'asset_depreciation_id' => $depreciation->id,
            'period_date' => $periodDate,
        ]);

        // Try to create second entry for same period
        $entryData = [
            'asset_depreciation_id' => $depreciation->id,
            'period_date' => $periodDate,
            'depreciation_amount' => 500,
        ];

        $response = $this->postJson('/api/depreciation/entries', $entryData);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Depreciation entry already exists for this period',
                 ]);
    }

    /**
     * Test depreciation methods listing.
     */
    public function test_depreciation_methods_listing(): void
    {
        $response = $this->getJson('/api/depreciation/methods');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'code',
                             'description',
                             'formula',
                         ],
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    /**
     * Test depreciation statistics.
     */
    public function test_depreciation_statistics(): void
    {
        $response = $this->getJson('/api/depreciation/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'total_assets',
                         'assets_with_depreciation',
                         'depreciation_schedules',
                         'active_schedules',
                         'by_status',
                         'by_method',
                         'financial_summary',
                         'depreciation_entries',
                         'age_analysis',
                         'value_analysis',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertArrayHasKey('total_assets', $stats);
        $this->assertArrayHasKey('assets_with_depreciation', $stats);
        $this->assertArrayHasKey('depreciation_schedules', $stats);
        $this->assertArrayHasKey('financial_summary', $stats);
    }

    /**
     * Test monthly depreciation processing.
     */
    public function test_monthly_depreciation_processing(): void
    {
        // Create an asset with depreciation schedule
        $asset = Asset::factory()->create(['purchase_cost' => 10000]);
        $method = DepreciationMethod::where('code', 'straight_line')->first();
        
        $depreciation = AssetDepreciation::factory()->create([
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 5,
            'depreciation_start_date' => now()->subMonths(1),
            'current_book_value' => 9000,
            'accumulated_depreciation' => 1000,
        ]);

        $response = $this->postJson('/api/depreciation/process-monthly');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Monthly depreciation processing completed',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'processed',
                         'skipped',
                         'entries_created',
                         'errors',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('processed', $data);
        $this->assertArrayHasKey('entries_created', $data);
        $this->assertIsArray($data['errors']);
    }

    /**
     * Test asset depreciation processing.
     */
    public function test_asset_depreciation_processing(): void
    {
        $asset = Asset::factory()->create(['purchase_cost' => 10000]);
        $method = DepreciationMethod::where('code', 'straight_line')->first();
        
        $depreciation = AssetDepreciation::factory()->create([
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 5,
            'depreciation_start_date' => now()->subMonths(1),
            'current_book_value' => 9000,
            'accumulated_depreciation' => 1000,
        ]);

        $response = $this->postJson("/api/depreciation/assets/{$asset->id}/process");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Depreciation processed successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'period_date',
                         'depreciation_amount',
                         'assetDepreciation',
                     ],
                 ]);
    }

    /**
     * Test asset depreciation processing without schedule.
     */
    public function test_asset_depreciation_processing_without_schedule(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->postJson("/api/depreciation/assets/{$asset->id}/process");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Asset does not have a depreciation schedule',
                 ]);
    }

    /**
     * Test depreciation report generation.
     */
    public function test_depreciation_report_generation(): void
    {
        // Test summary report
        $response = $this->getJson('/api/depreciation/report?report_type=summary');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'report_type',
                         'period',
                         'generated_at',
                         'summary',
                         'by_method',
                         'by_status',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals('summary', $data['report_type']);
        $this->assertArrayHasKey('summary', $data);

        // Test detailed report
        $response = $this->getJson('/api/depreciation/report?report_type=detailed');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'report_type',
                         'assets',
                     ],
                 ]);

        // Test forecast report
        $response = $this->getJson('/api/depreciation/report?report_type=forecast');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'report_type',
                         'forecast_years',
                         'forecast',
                     ],
                 ]);

        // Test comparison report
        $response = $this->getJson('/api/depreciation/report?report_type=comparison');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'report_type',
                         'comparison',
                     ],
                 ]);
    }

    /**
     * Test depreciation report validation.
     */
    public function test_depreciation_report_validation(): void
    {
        $response = $this->getJson('/api/depreciation/report?report_type=invalid');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'report_type',
                 ]);
    }

    /**
     * Test depreciation filtering.
     */
    public function test_depreciation_filtering(): void
    {
        $asset = Asset::factory()->create();
        $method = DepreciationMethod::first();

        // Test asset filter
        $response = $this->getJson("/api/depreciation?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $depreciation) {
            $this->assertEquals($asset->id, $depreciation['asset_id']);
        }

        // Test method filter
        $response = $this->getJson("/api/depreciation?depreciation_method_id={$method->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $depreciation) {
            $this->assertEquals($method->id, $depreciation['depreciation_method_id']);
        }

        // Test status filter
        $response = $this->getJson('/api/depreciation?status=fully_depreciated');
        $response->assertStatus(200);
    }

    /**
     * Test depreciation search.
     */
    public function test_depreciation_search(): void
    {
        // Create an asset with specific name
        $asset = Asset::factory()->create(['name' => 'Special Depreciation Asset']);
        AssetDepreciation::factory()->create(['asset_id' => $asset->id]);

        $response = $this->getJson('/api/depreciation?search=Special');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $found = false;
        foreach ($data as $depreciation) {
            if (str_contains(strtolower($depreciation['asset']['name']), 'special')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test depreciation sorting.
     */
    public function test_depreciation_sorting(): void
    {
        // Test sort by purchase_cost
        $response = $this->getJson('/api/depreciation?sort_by=purchase_cost&sort_order=desc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $data[$i]['purchase_cost'],
                    $data[$i + 1]['purchase_cost']
                );
            }
        }

        // Test sort by created_at
        $response = $this->getJson('/api/depreciation?sort_by=created_at&sort_order=asc');
        $response->assertStatus(200);
    }

    /**
     * Test depreciation model relationships.
     */
    public function test_depreciation_model_relationships(): void
    {
        $depreciation = AssetDepreciation::factory()->create();
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $depreciation->asset);
        
        // Test depreciation method relationship
        $this->assertInstanceOf(DepreciationMethod::class, $depreciation->depreciationMethod);
        
        // Test entries relationship
        $this->assertEmpty($depreciation->depreciationEntries);
        
        // Test creator relationship
        $this->assertInstanceOf(User::class, $depreciation->creator);
    }

    /**
     * Test depreciation model methods.
     */
    public function test_depreciation_model_methods(): void
    {
        $depreciation = AssetDepreciation::factory()->create([
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'accumulated_depreciation' => 3000,
            'current_book_value' => 7000,
            'useful_life_years' => 5,
            'depreciation_start_date' => now()->subYears(2),
        ]);
        
        // Test status methods
        $this->assertFalse($depreciation->isFullyDepreciated());
        $this->assertTrue($depreciation->hasDepreciationStarted());
        $this->assertFalse($depreciation->hasDepreciationEnded());
        
        // Test calculations
        $this->assertEquals(30, $depreciation->depreciation_percentage);
        $this->assertEquals(6000, $depreciation->remaining_depreciation);
        $this->assertEquals(60, $depreciation->remaining_depreciation_percentage);
        $this->assertGreaterThan(1, $depreciation->years_elapsed);
        $this->assertGreaterThan(0, $depreciation->remaining_years);
        
        // Test display methods
        $this->assertIsString($depreciation->depreciation_status_display);
        $this->assertIsString($depreciation->depreciation_status_color);
        $this->assertIsArray($depreciation->depreciation_summary);
    }

    /**
     * Test depreciation entry model.
     */
    public function test_depreciation_entry_model(): void
    {
        $depreciation = AssetDepreciation::factory()->create([
            'current_book_value' => 8000,
            'accumulated_depreciation' => 2000,
        ]);
        
        $entry = DepreciationEntry::factory()->create([
            'asset_depreciation_id' => $depreciation->id,
            'depreciation_amount' => 500,
            'book_value_before' => 8000,
            'book_value_after' => 7500,
            'accumulated_depreciation_before' => 2000,
            'accumulated_depreciation_after' => 2500,
        ]);
        
        // Test relationships
        $this->assertInstanceOf(AssetDepreciation::class, $entry->assetDepreciation);
        $this->assertInstanceOf(Asset::class, $entry->asset);
        $this->assertInstanceOf(User::class, $entry->creator);
        
        // Test properties
        $this->assertEquals('500.00', $entry->formatted_depreciation_amount);
        $this->assertEquals('8000.00', $entry->formatted_book_value_before);
        $this->assertEquals('7500.00', $entry->formatted_book_value_after);
        $this->assertIsString($entry->period_display);
        $this->assertIsString($entry->year_month);
        $this->assertIsArray($entry->entry_summary);
    }

    /**
     * Test depreciation method model.
     */
    public function test_depreciation_method_model(): void
    {
        $method = DepreciationMethod::factory()->create([
            'name' => 'Test Method',
            'code' => 'test_method',
            'description' => 'Test description',
            'formula' => 'Test formula',
        ]);
        
        // Test relationships
        $this->assertInstanceOf(User::class, $method->creator);
        
        // Test properties
        $this->assertEquals('Test Method', $method->display_name);
        $this->assertEquals('Test Method: Test description (Formula: Test formula)', $method->full_description);
    }

    /**
     * Test depreciation service projection calculation.
     */
    public function test_depreciation_service_projection(): void
    {
        $depreciation = AssetDepreciation::factory()->create([
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'current_book_value' => 8000,
            'accumulated_depreciation' => 2000,
            'useful_life_years' => 5,
            'annual_depreciation' => 1800,
        ]);
        
        $projection = $this->depreciationService->calculateDepreciationProjection($depreciation, 3);
        
        $this->assertCount(3, $projection);
        
        foreach ($projection as $year) {
            $this->assertArrayHasKey('year', $year);
            $this->assertArrayHasKey('projected_depreciation', $year);
            $this->assertArrayHasKey('projected_book_value', $year);
            $this->assertArrayHasKey('projected_accumulated_depreciation', $year);
            $this->assertArrayHasKey('projected_depreciation_percentage', $year);
            $this->assertArrayHasKey('is_fully_depreciated', $year);
        }
    }

    /**
     * Test depreciation service schedule generation.
     */
    public function test_depreciation_service_schedule_generation(): void
    {
        $depreciation = AssetDepreciation::factory()->create([
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 5,
            'depreciation_start_date' => now()->subYears(2),
        ]);
        
        $schedule = $this->depreciationService->generateDepreciationSchedule($depreciation);
        
        $this->assertNotEmpty($schedule);
        $this->assertLessThanOrEqual(5, count($schedule));
        
        foreach ($schedule as $year) {
            $this->assertArrayHasKey('year', $year);
            $this->assertArrayHasKey('beginning_book_value', $year);
            $this->assertArrayHasKey('depreciation_expense', $year);
            $this->assertArrayHasKey('accumulated_depreciation', $year);
            $this->assertArrayHasKey('ending_book_value', $year);
        }
    }

    /**
     * Test depreciation service efficiency analysis.
     */
    public function test_depreciation_service_efficiency_analysis(): void
    {
        $analysis = $this->depreciationService->analyzeDepreciationEfficiency();
        
        $this->assertArrayHasKey('total_assets', $analysis);
        $this->assertArrayHasKey('average_depreciation_rate', $analysis);
        $this->assertArrayHasKey('average_useful_life', $analysis);
        $this->assertArrayHasKey('fully_depreciated_percentage', $analysis);
        $this->assertArrayHasKey('by_method', $analysis);
        $this->assertArrayHasKey('by_age', $analysis);
        $this->assertArrayHasKey('by_value', $analysis);
        
        $this->assertIsArray($analysis['by_method']);
        $this->assertIsArray($analysis['by_age']);
        $this->assertIsArray($analysis['by_value']);
    }

    /**
     * Test depreciation service recommendations.
     */
    public function test_depreciation_service_recommendations(): void
    {
        $recommendations = $this->depreciationService->generateDepreciationRecommendations();
        
        $this->assertArrayHasKey('recommendations', $recommendations);
        $this->assertArrayHasKey('summary', $recommendations);
        $this->assertArrayHasKey('generated_at', $recommendations);
        
        $this->assertIsArray($recommendations['recommendations']);
        $this->assertArrayHasKey('total_recommendations', $recommendations['summary']);
        $this->assertArrayHasKey('by_priority', $recommendations['summary']);
        $this->assertArrayHasKey('by_type', $recommendations['summary']);
    }

    /**
     * Test depreciation without authentication.
     */
    public function test_depreciation_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/depreciation');
        $response->assertStatus(401);

        $response = $this->postJson('/api/depreciation');
        $response->assertStatus(401);
    }

    /**
     * Test depreciation with insufficient permissions.
     */
    public function test_depreciation_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read depreciation data
        $response = $this->getJson('/api/depreciation');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/depreciation/statistics');
        $response->assertStatus(200);
        
        // But not be able to create depreciation schedules
        $response = $this->postJson('/api/depreciation', [
            'asset_id' => Asset::factory()->create()->id,
            'depreciation_method_id' => DepreciationMethod::first()->id,
            'purchase_cost' => 1000,
            'useful_life_years' => 5,
            'depreciation_start_date' => now()->format('Y-m-d'),
        ]);
        $response->assertStatus(403);
    }
}
