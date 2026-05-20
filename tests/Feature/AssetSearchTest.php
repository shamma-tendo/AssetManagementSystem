<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Models\UserRole;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetSearchTest extends TestCase
{
    use RefreshDatabase;

    protected SearchService $searchService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->searchService = app(SearchService::class);
        
        // Create test user
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($user);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for search functionality.
     */
    private function createTestData(): void
    {
        // Create categories
        $categories = Category::factory()->createMany([
            ['name' => 'Laptops'],
            ['name' => 'Desktops'],
            ['name' => 'Servers'],
            ['name' => 'Printers'],
        ]);
        
        // Create locations
        $locations = Location::factory()->createMany([
            ['name' => 'Head Office'],
            ['name' => 'Branch Office'],
            ['name' => 'Warehouse'],
        ]);
        
        // Create departments
        $departments = Department::factory()->createMany([
            ['name' => 'IT'],
            ['name' => 'HR'],
            ['name' => 'Operations'],
        ]);
        
        // Create assets with different characteristics
        Asset::factory()->createMany([
            [
                'name' => 'Dell Latitude Laptop',
                'serial_number' => 'DL-LAPTOP-001',
                'category_id' => $categories[0]->id, // Laptops
                'location_id' => $locations[0]->id, // Head Office
                'department_id' => $departments[0]->id, // IT
                'status' => 'active',
                'purchase_cost' => 1500.00,
                'manufacturer' => 'Dell',
                'model' => 'Latitude 7420',
            ],
            [
                'name' => 'HP Desktop Computer',
                'serial_number' => 'HP-DESKTOP-001',
                'category_id' => $categories[1]->id, // Desktops
                'location_id' => $locations[1]->id, // Branch Office
                'department_id' => $departments[1]->id, // HR
                'status' => 'active',
                'purchase_cost' => 1200.00,
                'manufacturer' => 'HP',
                'model' => 'EliteDesk 800',
            ],
            [
                'name' => 'Dell PowerEdge Server',
                'serial_number' => 'DELL-SERVER-001',
                'category_id' => $categories[2]->id, // Servers
                'location_id' => $locations[0]->id, // Head Office
                'department_id' => $departments[0]->id, // IT
                'status' => 'under_maintenance',
                'purchase_cost' => 8000.00,
                'manufacturer' => 'Dell',
                'model' => 'PowerEdge R740',
            ],
            [
                'name' => 'Canon Printer',
                'serial_number' => 'CANON-PRINTER-001',
                'category_id' => $categories[3]->id, // Printers
                'location_id' => $locations[2]->id, // Warehouse
                'department_id' => $departments[2]->id, // Operations
                'status' => 'active',
                'purchase_cost' => 500.00,
                'manufacturer' => 'Canon',
                'model' => 'ImageRunner 2545',
            ],
            [
                'name' => 'Lenovo ThinkPad',
                'serial_number' => 'LENOVO-LAPTOP-001',
                'category_id' => $categories[0]->id, // Laptops
                'location_id' => $locations[1]->id, // Branch Office
                'department_id' => $departments[2]->id, // Operations
                'status' => 'retired',
                'purchase_cost' => 1800.00,
                'manufacturer' => 'Lenovo',
                'model' => 'ThinkPad X1',
            ],
        ]);
    }

    /**
     * Test basic asset search.
     */
    public function test_basic_asset_search(): void
    {
        $response = $this->getJson('/api/assets/search?search=Dell');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'pagination',
                     'filters_applied',
                     'search_time',
                 ]);

        // Should find 2 Dell assets
        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        // Verify both Dell assets are found
        $assetNames = collect($data)->pluck('name');
        $this->assertContains('Dell Latitude Laptop', $assetNames);
        $this->assertContains('Dell PowerEdge Server', $assetNames);
    }

    /**
     * Test search by serial number.
     */
    public function test_search_by_serial_number(): void
    {
        $response = $this->getJson('/api/assets/search?search=DL-LAPTOP-001');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Dell Latitude Laptop', $data[0]['name']);
        $this->assertEquals('DL-LAPTOP-001', $data[0]['serial_number']);
    }

    /**
     * Test search by manufacturer.
     */
    public function test_search_by_manufacturer(): void
    {
        $response = $this->getJson('/api/assets/search?search=HP');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('HP Desktop Computer', $data[0]['name']);
        $this->assertEquals('HP', $data[0]['manufacturer']);
    }

    /**
     * Test search by category name.
     */
    public function test_search_by_category_name(): void
    {
        $response = $this->getJson('/api/assets/search?search=Laptops');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data); // 2 laptops
        
        $assetNames = collect($data)->pluck('name');
        $this->assertContains('Dell Latitude Laptop', $assetNames);
        $this->assertContains('Lenovo ThinkPad', $assetNames);
    }

    /**
     * Test search by location name.
     */
    public function test_search_by_location_name(): void
    {
        $response = $this->getJson('/api/assets/search?search=Head Office');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data); // 2 assets at Head Office
    }

    /**
     * Test search with status filter.
     */
    public function test_search_with_status_filter(): void
    {
        $response = $this->getJson('/api/assets/search?status=active');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data); // 3 active assets
        
        // Verify all returned assets are active
        foreach ($data as $asset) {
            $this->assertEquals('active', $asset['status']);
        }
    }

    /**
     * Test search with multiple status filters.
     */
    public function test_search_with_multiple_status_filters(): void
    {
        $response = $this->getJson('/api/assets/search?status[]=active&status[]=retired');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(4, $data); // 3 active + 1 retired
        
        // Verify all returned assets have correct status
        foreach ($data as $asset) {
            $this->assertTrue(in_array($asset['status'], ['active', 'retired']));
        }
    }

    /**
     * Test search with category filter.
     */
    public function test_search_with_category_filter(): void
    {
        $laptopCategory = Category::where('name', 'Laptops')->first();
        
        $response = $this->getJson("/api/assets/search?category_id={$laptopCategory->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(2, $data); // 2 laptops
        
        foreach ($data as $asset) {
            $this->assertEquals($laptopCategory->id, $asset['category_id']);
        }
    }

    /**
     * Test search with purchase cost range filter.
     */
    public function test_search_with_purchase_cost_range(): void
    {
        $response = $this->getJson('/api/assets/search?purchase_cost_min=1000&purchase_cost_max=2000');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data); // Assets between $1000-$2000
        
        foreach ($data as $asset) {
            $this->assertGreaterThanOrEqual(1000, $asset['purchase_cost']);
            $this->assertLessThanOrEqual(2000, $asset['purchase_cost']);
        }
    }

    /**
     * Test search with date range filter.
     */
    public function test_search_with_date_range(): void
    {
        $today = now()->format('Y-m-d');
        $lastYear = now()->subYear()->format('Y-m-d');
        
        $response = $this->getJson("/api/assets/search?purchase_date_from={$lastYear}&purchase_date_to={$today}");

        $response->assertStatus(200);
        
        // All assets should be returned since they were created recently
        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    /**
     * Test search with sorting.
     */
    public function test_search_with_sorting(): void
    {
        $response = $this->getJson('/api/assets/search?sort_by=purchase_cost&sort_order=desc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(5, $data);
        
        // Verify sorting by purchase_cost descending
        $costs = collect($data)->pluck('purchase_cost');
        $this->assertEquals($costs->sortDesc()->values()->toArray(), $costs->toArray());
    }

    /**
     * Test search with pagination.
     */
    public function test_search_with_pagination(): void
    {
        $response = $this->getJson('/api/assets/search?per_page=2&page=1');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $pagination = $response->json('pagination');
        
        $this->assertCount(2, $data);
        $this->assertEquals(2, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(3, $pagination['last_page']); // 5 total / 2 per page
        $this->assertEquals(5, $pagination['total']);
    }

    /**
     * Test search suggestions.
     */
    public function test_search_suggestions(): void
    {
        $response = $this->getJson('/api/assets/suggestions?query=Del');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['type', 'value', 'label'],
                     ],
                 ]);

        $suggestions = $response->json('data');
        $this->assertNotEmpty($suggestions);
        
        // Should include Dell-related suggestions
        $labels = collect($suggestions)->pluck('label');
        $this->assertContains('Dell Latitude Laptop', $labels);
    }

    /**
     * Test search suggestions validation.
     */
    public function test_search_suggestions_validation(): void
    {
        // Test query too short
        $response = $this->getJson('/api/assets/suggestions?query=D');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['query']);

        // Test query too long
        $response = $this->getJson('/api/assets/suggestions?query=' . str_repeat('a', 51));
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['query']);
    }

    /**
     * Test popular searches.
     */
    public function test_popular_searches(): void
    {
        $response = $this->getJson('/api/assets/popular-searches');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['term', 'count'],
                     ],
                 ]);

        $popularTerms = $response->json('data');
        $this->assertNotEmpty($popularTerms);
        $this->assertLessThanOrEqual(10, count($popularTerms));
    }

    /**
     * Test search filters metadata.
     */
    public function test_search_filters_metadata(): void
    {
        $response = $this->getJson('/api/assets/search-filters');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'filters',
                         'sort_options',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify filter structure
        $this->assertArrayHasKey('status', $data['filters']);
        $this->assertArrayHasKey('category_id', $data['filters']);
        $this->assertArrayHasKey('location_id', $data['filters']);
        $this->assertArrayHasKey('department_id', $data['filters']);
        
        // Verify sort options
        $this->assertNotEmpty($data['sort_options']);
        $this->assertArrayHasKey('name', $data['sort_options']);
        $this->assertArrayHasKey('purchase_cost', $data['sort_options']);
    }

    /**
     * Test advanced search with multiple filters.
     */
    public function test_advanced_search_with_multiple_filters(): void
    {
        $laptopCategory = Category::where('name', 'Laptops')->first();
        $headOffice = Location::where('name', 'Head Office')->first();
        
        $response = $this->getJson("/api/assets/search?" . http_build_query([
            'search' => 'Dell',
            'category_id' => $laptopCategory->id,
            'location_id' => $headOffice->id,
            'status' => 'active',
            'purchase_cost_min' => 1000,
            'purchase_cost_max' => 2000,
            'sort_by' => 'name',
            'sort_order' => 'asc',
        ]));

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data); // Should find exactly 1 Dell laptop at Head Office
        
        $asset = $data[0];
        $this->assertEquals('Dell Latitude Laptop', $asset['name']);
        $this->assertEquals('Dell', $asset['manufacturer']);
        $this->assertEquals($laptopCategory->id, $asset['category_id']);
        $this->assertEquals($headOffice->id, $asset['location_id']);
        $this->assertEquals('active', $asset['status']);
        $this->assertGreaterThanOrEqual(1000, $asset['purchase_cost']);
        $this->assertLessThanOrEqual(2000, $asset['purchase_cost']);
    }

    /**
     * Test search validation.
     */
    public function test_search_validation(): void
    {
        // Test invalid sort field
        $response = $this->getJson('/api/assets/search?sort_by=invalid_field');
        $response->assertStatus(200); // Should still work, just default to created_at

        // Test invalid sort order
        $response = $this->getJson('/api/assets/search?sort_order=invalid_order');
        $response->assertStatus(200); // Should still work, just default to desc

        // Test invalid per_page value
        $response = $this->getJson('/api/assets/search?per_page=0');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['per_page']);

        // Test invalid per_page value (too high)
        $response = $this->getJson('/api/assets/search?per_page=101');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['per_page']);

        // Test invalid date range
        $response = $this->getJson('/api/assets/search?purchase_date_from=2023-01-01&purchase_date_to=2022-01-01');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['purchase_date_to']);
    }

    /**
     * Test search without authentication.
     */
    public function test_search_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/assets/search');
        $response->assertStatus(401);
    }

    /**
     * Test search performance.
     */
    public function test_search_performance(): void
    {
        // Create additional assets for performance testing
        Asset::factory()->count(100)->create(['status' => 'active']);

        $startTime = microtime(true);

        $response = $this->getJson('/api/assets/search?search=test');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(1.0, $executionTime, 'Search took too long');
    }

    /**
     * Test search with no results.
     */
    public function test_search_with_no_results(): void
    {
        $response = $this->getJson('/api/assets/search?search=NonExistentAsset');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(0, $data);
        
        $pagination = $response->json('pagination');
        $this->assertEquals(0, $pagination['total']);
        $this->assertEquals(0, $pagination['from']);
        $this->assertEquals(0, $pagination['to']);
    }

    /**
     * Test search with empty query.
     */
    public function test_search_with_empty_query(): void
    {
        $response = $this->getJson('/api/assets/search?search=');

        $response->assertStatus(200);
        
        // Should return all assets when search is empty
        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    /**
     * Test search service directly.
     */
    public function test_search_service_directly(): void
    {
        $params = [
            'search' => 'Dell',
            'status' => 'active',
            'sort_by' => 'name',
            'sort_order' => 'asc',
            'per_page' => 10,
        ];

        $result = $this->searchService->searchAssets($params);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('filters_applied', $result);
        $this->assertArrayHasKey('search_time', $result);

        $this->assertCount(1, $result['data']); // 1 active Dell asset
        $this->assertEquals('Dell Latitude Laptop', $result['data'][0]['name']);
    }
}
