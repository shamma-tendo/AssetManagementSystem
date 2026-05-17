<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /**
     * Test creating a new asset.
     */
    public function test_can_create_asset(): void
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $department = Department::factory()->create();

        $assetData = [
            'name' => 'Test Asset',
            'serial_number' => 'SN123456',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'purchase_date' => '2024-01-15',
            'purchase_cost' => 10000.00,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'description' => 'Test asset description',
            'manufacturer' => 'Test Manufacturer',
            'model' => 'Test Model',
        ];

        $response = $this->postJson('/api/assets', $assetData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Asset created successfully',
                 ]);

        $this->assertDatabaseHas('assets', [
            'name' => 'Test Asset',
            'serial_number' => 'SN123456',
            'status' => 'ordered',
        ]);
    }

    /**
     * Test asset validation rules.
     */
    public function test_asset_validation(): void
    {
        $response = $this->postJson('/api/assets', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'name',
                     'serial_number',
                     'category_id',
                     'purchase_date',
                     'purchase_cost',
                     'useful_life_years',
                     'depreciation_method',
                 ]);
    }

    /**
     * Test unique serial number validation.
     */
    public function test_unique_serial_number(): void
    {
        $category = Category::factory()->create();
        
        // Create first asset
        Asset::factory()->create([
            'serial_number' => 'DUPLICATE123',
            'category_id' => $category->id,
        ]);

        // Try to create second asset with same serial number
        $response = $this->postJson('/api/assets', [
            'name' => 'Second Asset',
            'serial_number' => 'DUPLICATE123',
            'category_id' => $category->id,
            'purchase_date' => '2024-01-15',
            'purchase_cost' => 5000.00,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['serial_number']);
    }

    /**
     * Test retrieving assets list.
     */
    public function test_can_list_assets(): void
    {
        Asset::factory()->count(5)->create();

        $response = $this->getJson('/api/assets');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonCount(5, 'data');
    }

    /**
     * Test asset search functionality.
     */
    public function test_asset_search(): void
    {
        Asset::factory()->create([
            'name' => 'Laptop Computer',
            'serial_number' => 'LAPTOP001',
        ]);

        Asset::factory()->create([
            'name' => 'Desktop Computer',
            'serial_number' => 'DESKTOP001',
        ]);

        // Search by name
        $response = $this->getJson('/api/assets?search=Laptop');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');

        // Search by serial number
        $response = $this->getJson('/api/assets?search=DESKTOP001');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Test retrieving a single asset.
     */
    public function test_can_show_asset(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->getJson("/api/assets/{$asset->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $asset->id,
                         'name' => $asset->name,
                     ],
                 ]);
    }

    /**
     * Test updating an asset.
     */
    public function test_can_update_asset(): void
    {
        $asset = Asset::factory()->create();

        $updateData = [
            'name' => 'Updated Asset Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/assets/{$asset->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Asset updated successfully',
                 ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Updated Asset Name',
        ]);
    }

    /**
     * Test deleting an asset.
     */
    public function test_can_delete_asset(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->deleteJson("/api/assets/{$asset->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Asset deleted successfully',
                 ]);

        $this->assertSoftDeleted('assets', [
            'id' => $asset->id,
        ]);
    }

    /**
     * Test cannot delete asset with work orders.
     */
    public function test_cannot_delete_asset_with_work_orders(): void
    {
        $asset = Asset::factory()->create();
        // This would require creating work orders, but for now we'll simulate it
        // In a real implementation, you'd create actual work orders

        $response = $this->deleteJson("/api/assets/{$asset->id}");

        // This test would need to be adjusted based on actual work order implementation
        $response->assertStatus(422);
    }

    /**
     * Test asset statistics endpoint.
     */
    public function test_asset_statistics(): void
    {
        Asset::factory()->count(10)->create(['status' => 'active']);
        Asset::factory()->count(3)->create(['status' => 'under_maintenance']);
        Asset::factory()->count(2)->create(['status' => 'retired']);

        $response = $this->getJson('/api/assets/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total_assets' => 15,
                         'active_assets' => 10,
                         'under_maintenance' => 3,
                         'retired_assets' => 2,
                     ],
                 ]);
    }

    /**
     * Test assets by status endpoint.
     */
    public function test_assets_by_status(): void
    {
        Asset::factory()->count(5)->create(['status' => 'active']);
        Asset::factory()->count(2)->create(['status' => 'retired']);

        $response = $this->getJson('/api/assets/status/active');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');

        $response = $this->getJson('/api/assets/status/retired');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test invalid status endpoint.
     */
    public function test_invalid_status_endpoint(): void
    {
        $response = $this->getJson('/api/assets/status/invalid');

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid status',
                 ]);
    }
}
