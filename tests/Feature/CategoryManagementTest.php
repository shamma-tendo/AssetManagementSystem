<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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
     * Create test data for category management.
     */
    private function createTestData(): void
    {
        // Create parent categories
        $parentCategories = Category::factory()->createMany([
            ['name' => 'IT Equipment'],
            ['name' => 'Office Equipment'],
            ['name' => 'Vehicles'],
        ]);
        
        // Create child categories
        Category::factory()->createMany([
            ['name' => 'Laptops', 'parent_category_id' => $parentCategories[0]->id],
            ['name' => 'Desktops', 'parent_category_id' => $parentCategories[0]->id],
            ['name' => 'Printers', 'parent_category_id' => $parentCategories[1]->id],
            ['name' => 'Furniture', 'parent_category_id' => $parentCategories[1]->id],
            ['name' => 'Cars', 'parent_category_id' => $parentCategories[2]->id],
            ['name' => 'Trucks', 'parent_category_id' => $parentCategories[2]->id],
        ]);
        
        // Create some assets for testing
        $categories = Category::all();
        foreach ($categories as $category) {
            Asset::factory()->count(rand(1, 3))->create(['category_id' => $category->id]);
        }
    }

    /**
     * Test enhanced category listing with filters.
     */
    public function test_enhanced_category_listing(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'pagination',
                 ]);

        // Verify asset counts are included
        $data = $response->json('data');
        $this->assertArrayHasKey('assets_count', $data[0]);
    }

    /**
     * Test category listing with search.
     */
    public function test_category_search(): void
    {
        $response = $this->getJson('/api/categories?search=Laptop');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        // Should find categories with "Laptop" in name or description
        $found = false;
        foreach ($data as $category) {
            if (str_contains(strtolower($category['name']), 'laptop')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test category listing with parent filter.
     */
    public function test_category_parent_filter(): void
    {
        // Test root categories
        $response = $this->getJson('/api/categories?parent_category_id=null');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        // All should be root categories
        foreach ($data as $category) {
            $this->assertNull($category['parent_category_id']);
        }

        // Test child categories
        $parentCategory = Category::whereNull('parent_category_id')->first();
        $response = $this->getJson("/api/categories?parent_category_id={$parentCategory->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $category) {
            $this->assertEquals($parentCategory->id, $category['parent_category_id']);
        }
    }

    /**
     * Test category tree endpoint.
     */
    public function test_category_tree(): void
    {
        $response = $this->getJson('/api/categories/tree');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'description',
                             'pm_frequency_months',
                             'useful_life_years',
                             'depreciation_method',
                             'is_active',
                             'assets_count',
                             'has_children',
                             'children',
                         ],
                     ],
                 ]);

        $tree = $response->json('data');
        
        // Verify tree structure
        $rootCategories = array_filter($tree, fn($cat) => !isset($cat['parent_category_id']));
        $this->assertNotEmpty($rootCategories);
        
        // Verify children are properly nested
        foreach ($rootCategories as $rootCategory) {
            if ($rootCategory['has_children']) {
                $this->assertArrayHasKey('children', $rootCategory);
                $this->assertNotEmpty($rootCategory['children']);
            }
        }
    }

    /**
     * Test category statistics.
     */
    public function test_category_statistics(): void
    {
        $response = $this->getJson('/api/categories/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'total_categories',
                         'active_categories',
                         'inactive_categories',
                         'root_categories',
                         'categories_with_assets',
                         'categories_without_assets',
                         'average_pm_frequency',
                         'average_useful_life',
                         'depreciation_methods',
                         'top_categories_by_assets',
                     ],
                 ]);

        $stats = $response->json('data');
        
        // Verify statistics are reasonable
        $this->assertGreaterThan(0, $stats['total_categories']);
        $this->assertGreaterThan(0, $stats['active_categories']);
        $this->assertGreaterThan(0, $stats['root_categories']);
        $this->assertGreaterThan(0, $stats['categories_with_assets']);
        $this->assertGreaterThan(0, $stats['average_pm_frequency']);
        $this->assertGreaterThan(0, $stats['average_useful_life']);
        
        // Verify top categories by assets
        $this->assertNotEmpty($stats['top_categories_by_assets']);
        $this->assertArrayHasKey('assets_count', $stats['top_categories_by_assets'][0]);
    }

    /**
     * Test category assets endpoint.
     */
    public function test_category_assets(): void
    {
        $category = Category::has('assets')->first();
        
        $response = $this->getJson("/api/categories/{$category->id}/assets");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'category',
                         'assets',
                         'pagination',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify category is returned
        $this->assertEquals($category->id, $data['category']['id']);
        
        // Verify assets are returned
        $this->assertNotEmpty($data['assets']);
        
        // All assets should belong to the category
        foreach ($data['assets'] as $asset) {
            $this->assertEquals($category->id, $asset['category_id']);
        }
    }

    /**
     * Test category assets with filters.
     */
    public function test_category_assets_with_filters(): void
    {
        $category = Category::has('assets')->first();
        
        // Test status filter
        $response = $this->getJson("/api/categories/{$category->id}/assets?status=active");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data['assets'] as $asset) {
            $this->assertEquals('active', $asset['status']);
        }

        // Test search filter
        $response = $this->getJson("/api/categories/{$category->id}/assets?search=test");
        $response->assertStatus(200);
    }

    /**
     * Test category maintenance schedule.
     */
    public function test_category_maintenance_schedule(): void
    {
        $category = Category::whereHas('assets', function ($query) {
            $query->where('status', 'active');
        })->first();
        
        $response = $this->getJson("/api/categories/{$category->id}/maintenance-schedule");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'category',
                         'maintenance_schedule',
                         'summary',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify category is returned
        $this->assertEquals($category->id, $data['category']['id']);
        
        // Verify maintenance schedule
        $this->assertArrayHasKey('maintenance_schedule', $data);
        $this->assertArrayHasKey('summary', $data);
        
        if (!empty($data['maintenance_schedule'])) {
            $schedule = $data['maintenance_schedule'][0];
            $this->assertArrayHasKey('asset_id', $schedule);
            $this->assertArrayHasKey('asset_name', $schedule);
            $this->assertArrayHasKey('next_maintenance_date', $schedule);
            $this->assertArrayHasKey('days_until_maintenance', $schedule);
            $this->assertArrayHasKey('is_overdue', $schedule);
            $this->assertArrayHasKey('priority', $schedule);
        }
    }

    /**
     * Test bulk update categories.
     */
    public function test_bulk_update_categories(): void
    {
        $categories = Category::take(2)->get();
        $categoryIds = $categories->pluck('id')->toArray();
        
        $response = $this->postJson('/api/categories/bulk-update', [
            'category_ids' => $categoryIds,
            'updates' => [
                'pm_frequency_months' => 6,
                'is_active' => false,
            ],
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Categories updated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'updated_count',
                         'category_ids',
                         'updates',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['updated_count']);
        
        // Verify updates were applied
        foreach ($categoryIds as $categoryId) {
            $category = Category::find($categoryId);
            $this->assertEquals(6, $category->pm_frequency_months);
            $this->assertFalse($category->is_active);
        }
    }

    /**
     * Test bulk update validation.
     */
    public function test_bulk_update_validation(): void
    {
        // Test missing category_ids
        $response = $this->postJson('/api/categories/bulk-update', [
            'updates' => ['pm_frequency_months' => 6],
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['category_ids']);

        // Test missing updates
        $response = $this->postJson('/api/categories/bulk-update', [
            'category_ids' => ['uuid'],
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['updates']);

        // Test invalid pm_frequency_months
        $response = $this->postJson('/api/categories/bulk-update', [
            'category_ids' => ['uuid'],
            'updates' => ['pm_frequency_months' => 0],
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['updates.pm_frequency_months']);
    }

    /**
     * Test category duplication.
     */
    public function test_category_duplication(): void
    {
        $category = Category::first();
        
        $response = $this->postJson("/api/categories/{$category->id}/duplicate", [
            'name' => 'Duplicated Category',
            'include_children' => true,
            'include_assets' => false,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Category duplicated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'new_category',
                         'duplicated_items',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify new category was created
        $this->assertEquals('Duplicated Category', $data['new_category']['name']);
        $this->assertNotEquals($category->id, $data['new_category']['id']);
        
        // Verify original properties were copied
        $this->assertEquals($category->pm_frequency_months, $data['new_category']['pm_frequency_months']);
        $this->assertEquals($category->useful_life_years, $data['new_category']['useful_life_years']);
        
        // Verify children were duplicated if requested
        if ($category->children->count() > 0) {
            $this->assertArrayHasKey('children_count', $data['duplicated_items']);
        }
    }

    /**
     * Test category duplication validation.
     */
    public function test_category_duplication_validation(): void
    {
        $category = Category::first();
        
        // Test missing name
        $response = $this->postJson("/api/categories/{$category->id}/duplicate", [
            'include_children' => true,
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);

        // Test duplicate name
        $response = $this->postJson("/api/categories/{$category->id}/duplicate", [
            'name' => $category->name,
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test category export.
     */
    public function test_category_export(): void
    {
        $response = $this->getJson('/api/categories/export');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Categories exported successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'filename',
                         'download_url',
                         'record_count',
                         'export_data',
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify export data structure
        $this->assertNotEmpty($data['export_data']);
        $this->assertEquals(count($data['export_data']), $data['record_count']);
        
        // Verify export data fields
        $exportRecord = $data['export_data'][0];
        $this->assertArrayHasKey('ID', $exportRecord);
        $this->assertArrayHasKey('Name', $exportRecord);
        $this->assertArrayHasKey('Description', $exportRecord);
        $this->assertArrayHasKey('Parent Category', $exportRecord);
        $this->assertArrayHasKey('PM Frequency (Months)', $exportRecord);
        $this->assertArrayHasKey('Useful Life (Years)', $exportRecord);
        $this->assertArrayHasKey('Depreciation Method', $exportRecord);
        $this->assertArrayHasKey('Is Active', $exportRecord);
        $this->assertArrayHasKey('Assets Count', $exportRecord);
    }

    /**
     * Test category export with filters.
     */
    public function test_category_export_with_filters(): void
    {
        // Test active only filter
        $response = $this->getJson('/api/categories/export?is_active=true');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data['export_data'] as $record) {
            $this->assertEquals('Yes', $record['Is Active']);
        }

        // Test root categories filter
        $response = $this->getJson('/api/categories/export?parent_category_id=null');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data['export_data'] as $record) {
            $this->assertEquals('Root', $record['Parent Category']);
        }
    }

    /**
     * Test enhanced category sorting.
     */
    public function test_category_sorting(): void
    {
        // Test sort by assets_count
        $response = $this->getJson('/api/categories?sort_by=assets_count&sort_order=desc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $data[$i]['assets_count'],
                    $data[$i + 1]['assets_count']
                );
            }
        }

        // Test sort by pm_frequency_months
        $response = $this->getJson('/api/categories?sort_by=pm_frequency_months&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    $data[$i]['pm_frequency_months'],
                    $data[$i + 1]['pm_frequency_months']
                );
            }
        }
    }

    /**
     * Test category management without authentication.
     */
    public function test_category_management_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/categories');
        $response->assertStatus(401);

        $response = $this->getJson('/api/categories/tree');
        $response->assertStatus(401);

        $response = $this->getJson('/api/categories/statistics');
        $response->assertStatus(401);
    }

    /**
     * Test category management with insufficient permissions.
     */
    public function test_category_management_with_insufficient_permissions(): void
    {
        // Create viewer user (limited permissions)
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read categories
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/categories/tree');
        $response->assertStatus(200);
        
        // But not be able to bulk update
        $response = $this->postJson('/api/categories/bulk-update', [
            'category_ids' => ['uuid'],
            'updates' => ['pm_frequency_months' => 6],
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test category management performance.
     */
    public function test_category_management_performance(): void
    {
        // Create additional categories for performance testing
        Category::factory()->count(50)->create();
        
        $startTime = microtime(true);

        $response = $this->getJson('/api/categories/tree');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(1.0, $executionTime, 'Category tree generation took too long');
    }
}
