<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\UserRole;
use App\Models\TransactionType;
use App\Models\TransactionStatus;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = app(InventoryService);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for inventory management.
     */
    private function createTestData(): void
    {
        // Create part categories
        PartCategory::factory()->count(5)->create();
        
        // Create suppliers
        Supplier::factory()->count(10)->create();
        
        // Create parts
        Part::factory()->count(20)->create();
        
        // Create some inventory transactions
        InventoryTransaction::factory()->count(30)->create();
        
        // Create purchase orders
        PurchaseOrder::factory()->count(5)->create();
    }

    /**
     * Test parts listing.
     */
    public function test_parts_listing(): void
    {
        $response = $this->getJson('/api/inventory/parts');

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
     * Test part creation.
     */
    public function test_part_creation(): void
    {
        $category = PartCategory::first();
        $supplier = Supplier::first();
        $manufacturer = Supplier::factory()->create();

        $partData = [
            'name' => 'Test Part',
            'description' => 'Test part description',
            'part_number' => 'TP-001',
            'manufacturer_part_number' => 'MP-TP-001',
            'supplier_part_number' => 'SP-TP-001',
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
            'supplier_id' => $supplier->id,
            'unit_of_measure' => 'PCS',
            'current_stock' => 100,
            'minimum_stock' => 20,
            'maximum_stock' => 500,
            'reorder_point' => 25,
            'reorder_quantity' => 100,
            'unit_cost' => 15.50,
            'selling_price' => 25.00,
            'lead_time_days' => 14,
            'storage_location' => 'Warehouse A',
            'bin_location' => 'A1-01',
            'serial_number_required' => false,
            'batch_number_required' => false,
            'expiry_date_required' => false,
            'hazardous_material' => false,
            'specifications' => [
                'material' => 'Steel',
                'color' => 'Blue',
            ],
            'dimensions' => [
                'length' => 10,
                'width' => 5,
                'height' => 2,
                'unit' => 'cm',
            ],
            'weight_kg' => 0.5,
        ];

        $response = $this->postJson('/api/inventory/parts', $partData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Part created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'part_number',
                         'category',
                         'supplier',
                         'manufacturer',
                     ],
                 ]);

        $this->assertDatabaseHas('parts', [
            'name' => 'Test Part',
            'part_number' => 'TP-001',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'manufacturer_id' => $manufacturer->id,
            'current_stock' => 100,
            'minimum_stock' => 20,
        ]);
    }

    /**
     * Test part creation validation.
     */
    public function test_part_creation_validation(): void
    {
        $response = $this->postJson('/api/inventory/parts', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'name',
                     'description',
                     'part_number',
                     'unit_of_measure',
                 ]);
    }

    /**
     * Test part show.
     */
    public function test_part_show(): void
    {
        $part = Part::first();

        $response = $this->getJson("/api/inventory/parts/{$part->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'description',
                         'part_number',
                         'category',
                         'supplier',
                         'manufacturer',
                         'stockLocations',
                         'inventoryTransactions',
                         'purchaseOrders',
                         'workOrders',
                     ],
                 ]);
    }

    /**
     * Test part update.
     */
    public function test_part_update(): void
    {
        $part = Part::first();
        $category = PartCategory::factory()->create();

        $updateData = [
            'name' => 'Updated Part Name',
            'description' => 'Updated description',
            'minimum_stock' => 30,
            'maximum_stock' => 600,
            'category_id' => $category->id,
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/inventory/parts/{$part->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Part updated successfully',
                 ]);

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'name' => 'Updated Part Name',
            'minimum_stock' => 30,
            'maximum_stock' => 600,
            'category_id' => $category->id,
        ]);
    }

    /**
     * Test part deletion.
     */
    public function test_part_deletion(): void
    {
        $part = Part::factory()->create();

        $response = $this->deleteJson("/api/inventory/parts/{$part->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Part deleted successfully',
                 ]);

        $this->assertSoftDeleted('parts', ['id' => $part->id]);
    }

    /**
     * Test part deletion restrictions.
     */
    public function test_part_deletion_restrictions(): void
    {
        $part = Part::factory()->create();
        InventoryTransaction::factory()->create(['part_id' => $part->id]);

        $response = $this->deleteJson("/api/inventory/parts/{$part->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete part with inventory transactions',
                 ]);
    }

    /**
     * Test inventory transactions listing.
     */
    public function test_inventory_transactions_listing(): void
    {
        $response = $this->getJson('/api/inventory/transactions');

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
     * Test inventory transaction creation.
     */
    public function test_inventory_transaction_creation(): void
    {
        $part = Part::factory()->create(['current_stock' => 100]);

        $transactionData = [
            'part_id' => $part->id,
            'quantity' => 50,
            'transaction_type' => 'receive',
            'reference' => 'TEST-REF-001',
            'unit_cost' => 20.00,
            'notes' => 'Test transaction',
        ];

        $response = $this->postJson('/api/inventory/transactions', $transactionData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Transaction created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'quantity',
                         'transaction_type',
                         'part',
                         'performer',
                     ],
                 ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'part_id' => $part->id,
            'quantity' => 50,
            'transaction_type' => 'receive',
            'reference' => 'TEST-REF-001',
        ]);

        // Check that part stock was updated
        $part->refresh();
        $this->assertEquals(150, $part->current_stock);
    }

    /**
     * Test purchase orders listing.
     */
    public function test_purchase_orders_listing(): void
    {
        $response = $this->getJson('/api/inventory/purchase-orders');

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
     * Test purchase order creation.
     */
    public function test_purchase_order_creation(): void
    {
        $supplier = Supplier::first();
        $part1 = Part::factory()->create();
        $part2 = Part::factory()->create();

        $orderData = [
            'supplier_id' => $supplier->id,
            'priority' => 'normal',
            'expected_delivery_date' => now()->addDays(14)->format('Y-m-d'),
            'payment_terms' => 'Net 30',
            'notes' => 'Test purchase order',
            'items' => [
                [
                    'part_id' => $part1->id,
                    'quantity' => 10,
                    'unit_cost' => 25.00,
                    'notes' => 'First item',
                ],
                [
                    'part_id' => $part2->id,
                    'quantity' => 5,
                    'unit_cost' => 50.00,
                    'notes' => 'Second item',
                ],
            ],
        ];

        $response = $this->postJson('/api/inventory/purchase-orders', $orderData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Purchase order created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'order_number',
                         'supplier',
                         'items',
                         'total_amount',
                     ],
                 ]);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'priority' => 'normal',
            'total_amount' => 550.00, // (10 * 25) + (5 * 50) + tax
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'part_id' => $part1->id,
            'quantity' => 10,
            'unit_cost' => 25.00,
            'total_cost' => 250.00,
        ]);
    }

    /**
     * Test purchase order creation validation.
     */
    public function test_purchase_order_creation_validation(): void
    {
        $response = $this->postJson('/api/inventory/purchase-orders', [
            'supplier_id' => 'invalid-uuid',
            'priority' => 'invalid-priority',
            'items' => [],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                    'supplier_id',
                    'priority',
                    'items',
                ]);
    }

    /**
     * Test inventory statistics.
     */
    public function test_inventory_statistics(): void
    {
        $response = $this->getJson('/api/inventory/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'parts',
                         'inventory_value',
                         'purchase_orders',
                         'transactions',
                         'suppliers',
                         'categories',
                         'stock_locations',
                         'recent_activity',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertArrayHasKey('total', $stats['parts']);
        $this->assertArrayHasKey('low_stock', $stats['parts']);
        $this->assertArrayHasKey('total_value', $stats['inventory_value']);
    }

    /**
     * Test stock forecast.
     */
    public function test_stock_forecast(): void
    {
        $part = Part::factory()->create([
            'current_stock' => 100,
            'minimum_stock' => 20,
            'reorder_point' => 25,
        ]);

        // Create some usage transactions
        InventoryTransaction::factory()->count(10)->create([
            'part_id' => $part->id,
            'transaction_type' => 'issue',
            'quantity' => -5,
            'performed_at' => now()->subDays(rand(1, 30)),
        ]);

        $response = $this->getJson("/api/inventory/stock-forecast?part_id={$part->id}&days=90");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'part',
                         'usage_stats',
                         'forecast',
                         'recommendations',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals($part->id, $data['part']['id']);
        $this->assertArrayHasKey('average_daily_usage', $data['usage_stats']);
        $this->assertArrayHasKey('days_of_stock', $data['usage_stats']);
        $this->assertIsArray($data['forecast']);
        $this->assertIsArray($data['recommendations']);
    }

    /**
     * Test low stock alerts.
     */
    public function test_low_stock_alerts(): void
    {
        // Create parts with different stock levels
        Part::factory()->create([
            'name' => 'Out of Stock Part',
            'current_stock' => 0,
            'minimum_stock' => 10,
        ]);

        Part::factory()->create([
            'name' => 'Low Stock Part',
            'current_stock' => 5,
            'minimum_stock' => 10,
        ]);

        Part::factory()->create([
            'name' => 'Normal Stock Part',
            'current_stock' => 50,
            'minimum_stock' => 10,
        ]);

        $response = $this->getJson('/api/inventory/low-stock-alerts');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'part_number',
                             'current_stock',
                             'minimum_stock',
                             'shortage',
                             'stock_level_status',
                             'stock_level_color',
                         ],
                     ],
                 ]);

        $alerts = $response->json('data');
        $this->assertCount(2, $alerts); // Should have 2 low stock alerts
        
        foreach ($alerts as $alert) {
            $this->assertLessThanOrEqual($alert['current_stock'], $alert['minimum_stock']);
        }
    }

    /**
     * Test part filtering.
     */
    public function test_part_filtering(): void
    {
        $category = PartCategory::first();
        $supplier = Supplier::first();

        // Test category filter
        $response = $this->getJson("/api/inventory/parts?category_id={$category->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $part) {
            $this->assertEquals($category->id, $part['category_id']);
        }

        // Test supplier filter
        $response = $this->getJson("/api/inventory/parts?supplier_id={$supplier->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $part) {
            $this->assertEquals($supplier->id, $part['supplier_id']);
        }

        // Test stock status filter
        $response = $this->getJson('/api/inventory/parts?stock_status=low_stock');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $part) {
            $this->assertLessThanOrEqual($part['current_stock'], $part['minimum_stock']);
        }

        // Test hazardous filter
        $response = $this->getJson('/api/inventory/parts?hazardous=1');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $part) {
            $this->assertTrue($part['hazardous_material']);
        }
    }

    /**
     * Test part search.
     */
    public function test_part_search(): void
    {
        // Create a part with specific name
        Part::factory()->create(['name' => 'Special Test Part']);

        $response = $this->getJson('/api/inventory/parts?search=Special');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $found = false;
        foreach ($data as $part) {
            if (str_contains(strtolower($part['name']), 'special')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test part sorting.
     */
    public function test_part_sorting(): void
    {
        // Test sort by name
        $response = $this->getJson('/api/inventory/parts?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    strcmp($data[$i]['name'], $data[$i + 1]['name']),
                    0,
                    "Parts should be sorted by name in ascending order"
                );
            }
        }

        // Test sort by current_stock
        $response = $this->getJson('/api/inventory/parts?sort_by=current_stock&sort_order=desc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $data[$i]['current_stock'],
                    $data[$i + 1]['current_stock'],
                    "Parts should be sorted by current stock in descending order"
                );
            }
        }
    }

    /**
     * Test transaction filtering.
     */
    public function test_transaction_filtering(): void
    {
        $part = Part::first();

        // Test part filter
        $response = $this->getJson("/api/inventory/transactions?part_id={$part->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $transaction) {
            $this->assertEquals($part->id, $transaction['part_id']);
        }

        // Test transaction type filter
        $response = $this->getJson('/api/inventory/transactions?transaction_type=receive');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $transaction) {
            $this->assertEquals('receive', $transaction['transaction_type']);
        }

        // Test date range filter
        $response = $this->getJson('/api/inventory/transactions?date_from=' . now()->subDays(7)->format('Y-m-d'));
        $response->assertStatus(200);
    }

    /**
     * Test purchase order filtering.
     */
    public function test_purchase_order_filtering(): void
    {
        $supplier = Supplier::first();

        // Test supplier filter
        $response = $this->getJson("/api/inventory/purchase-orders?supplier_id={$supplier->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $order) {
            $this->assertEquals($supplier->id, $order['supplier_id']);
        }

        // Test status filter
        $response = $this->getJson('/api/inventory/purchase-orders?status=draft');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $order) {
            $this->assertEquals('draft', $order['status']);
        }

        // Test overdue filter
        $response = $this->getJson('/api/inventory/purchase-orders?overdue=1');
        $response->assertStatus(200);
    }

    /**
     * Test part model relationships.
     */
    public function test_part_model_relationships(): void
    {
        $part = Part::factory()->create();
        
        // Test category relationship
        $this->assertInstanceOf(PartCategory::class, $part->category);
        
        // Test supplier relationship
        $this->assertInstanceOf(Supplier::class, $part->supplier);
        
        // Test manufacturer relationship
        $this->assertInstanceOf(Supplier::class, $part->manufacturer);
        
        // Test inventory transactions relationship
        $this->assertEmpty($part->inventoryTransactions);
        
        // Test stock locations relationship
        $this->assertEmpty($part->stockLocations);
        
        // Test purchase orders relationship
        $this->assertEmpty($part->purchaseOrders);
        
        // Test work orders relationship
        $this->assertEmpty($part->workOrders);
    }

    /**
     * Test part model methods.
     */
    public function test_part_model_methods(): void
    {
        $part = Part::factory()->create([
            'current_stock' => 50,
            'minimum_stock' => 20,
            'maximum_stock' => 100,
            'reorder_point' => 25,
            'unit_cost' => 10.00,
            'average_cost' => 12.00,
        ]);
        
        // Test stock level status
        $this->assertEquals('normal', $part->stock_level_status);
        $this->assertEquals('Normal Stock', $part->stock_level_status_display);
        $this->assertEquals('green', $part->stock_level_status_color);
        
        // Test stock checks
        $this->assertFalse($part->isOutOfStock());
        $this->assertFalse($part->isLowStock());
        $this->assertFalse($part->needsReordering());
        $this->assertFalse($part->isOverstocked());
        
        // Test calculations
        $this->assertEquals(600, $part->total_inventory_value); // 50 * 12
        $this->assertEquals(1200, $part->reorder_value); // 100 * 12 (reorder_quantity default)
        
        // Test display methods
        $this->assertIsString($part->formatted_specifications);
        $this->assertIsArray($part->documents);
        $this->assertIsArray($part->cross_references);
    }

    /**
     * Test part model stock management.
     */
    public function test_part_stock_management(): void
    {
        $part = Part::factory()->create([
            'current_stock' => 100,
            'average_cost' => 10.00,
        ]);
        
        // Test receiving stock
        $transaction = $part->receiveStock(50, 'TEST-RECEIVE', ['source' => 'test']);
        $this->assertEquals(50, $transaction->quantity);
        $this->assertEquals('receive', $transaction->transaction_type);
        
        $part->refresh();
        $this->assertEquals(150, $part->current_stock);
        
        // Test issuing stock
        $transaction = $part->issueStock(25, 'TEST-ISSUE', ['destination' => 'test']);
        $this->assertEquals(-25, $transaction->quantity);
        $this->assertEquals('issue', $transaction->transaction_type);
        
        $part->refresh();
        $this->assertEquals(125, $part->current_stock);
        
        // Test adjusting stock
        $transaction = $part->adjustStock(-5, 'TEST-ADJUSTMENT', ['reason' => 'test']);
        $this->assertEquals(-5, $transaction->quantity);
        $this->assertEquals('adjustment', $transaction->transaction_type);
        
        $part->refresh();
        $this->assertEquals(120, $part->current_stock);
        
        // Test average cost update
        $transaction = $part->receiveStock(10, 'TEST-RECEIVE-2', ['unit_cost' => 15.00]);
        $part->refresh();
        $this->assertGreaterThan(10.00, $part->average_cost);
    }

    /**
     * Test part model stock usage statistics.
     */
    public function test_part_stock_usage_statistics(): void
    {
        $part = Part::factory()->create();
        
        // Create usage transactions
        InventoryTransaction::factory()->count(5)->create([
            'part_id' => $part->id,
            'transaction_type' => 'issue',
            'quantity' => -10,
            'performed_at' => now()->subDays(rand(1, 30)),
        ]);
        
        $stats = $part->getStockUsageStats(30);
        
        $this->assertArrayHasKey('issued_quantity', $stats);
        $this->assertArrayHasKey('received_quantity', $stats);
        $this->assertArrayHasKey('average_daily_usage', $stats);
        $this->assertArrayHasKey('days_of_stock', $stats);
        $this->assertArrayHasKey('period_days', $stats);
        
        $this->assertEquals(30, $stats['period_days']);
        $this->assertEquals(50, $stats['issued_quantity']); // 5 * 10
    }

    /**
     * Test part model stock forecast.
     */
    public function test_part_stock_forecast(): void
    {
        $part = Part::factory()->create([
            'current_stock' => 100,
            'minimum_stock' => 20,
            'reorder_point' => 25,
        ]);
        
        $forecast = $part->getStockForecast(30);
        
        $this->assertCount(30, $forecast);
        $this->assertArrayHasKey('date', $forecast[0]);
        $this->assertArrayHasKey('projected_stock', $forecast[0]);
        $this->assertArrayHasKey('stock_status', $forecast[0]);
    }

    /**
     * Test inventory transaction model.
     */
    public function test_inventory_transaction_model(): void
    {
        $part = Part::factory()->create();
        
        $transaction = InventoryTransaction::factory()->create([
            'part_id' => $part->id,
            'quantity' => 10,
            'transaction_type' => 'receive',
            'unit_cost' => 15.00,
            'total_cost' => 150.00,
        ]);
        
        // Test relationships
        $this->assertInstanceOf(Part::class, $transaction->part);
        $this->assertInstanceOf(User::class, $transaction->performer);
        
        // Test properties
        $this->assertTrue($transaction->isPositive());
        $this->assertFalse($transaction->isNegative());
        $this->assertEquals(10, $transaction->absolute_quantity);
        $this->assertEquals('+10.0000', $transaction->quantity_with_sign);
        $this->assertEquals('15.0000', $transaction->formatted_unit_cost);
        $this->assertEquals('150.00', $transaction->formatted_total_cost);
        
        // Test display methods
        $this->assertIsString($transaction->transaction_type_display_name);
        $this->assertIsString($transaction->transaction_type_color);
        $this->assertIsString($transaction->description);
        $this->assertIsArray($transaction->location_info);
        $this->assertIsArray($transaction->tracking_info);
    }

    /**
     * Test purchase order model.
     */
    public function test_purchase_order_model(): void
    {
        $order = PurchaseOrder::factory()->create([
            'status' => 'approved',
            'expected_delivery_date' => now()->addDays(14),
        ]);
        
        // Create some items
        PurchaseOrderItem::factory()->count(3)->create([
            'purchase_order_id' => $order->id,
            'quantity' => 10,
            'received_quantity' => 5,
        ]);
        
        // Test relationships
        $this->assertInstanceOf(Supplier::class, $order->supplier);
        $this->assertInstanceOf(User::class, $order->creator);
        $this->assertNotEmpty($order->items);
        
        // Test properties
        $this->assertFalse($order->isOverdue());
        $this->assertFalse($order->isFullyReceived());
        $this->assertTrue($order->isPartiallyReceived());
        $this->assertEquals('partially_received', $order->receive_status);
        $this->assertEquals('Partially Received', $order->receive_status_display);
        
        // Test calculations
        $this->assertEquals(30, $order->total_quantity);
        $this->assertEquals(15, $order->total_received_quantity);
        $this->assertEquals(15, $order->remaining_quantity);
        $this->assertEquals(50.0, $order->receive_percentage);
        
        // Test display methods
        $this->assertIsString($order->status_display_name);
        $this->assertIsString($order->status_color);
        $this->assertIsString($order->priority_display_name);
        $this->assertIsString($order->priority_color);
        $this->assertIsArray($order->order_summary);
    }

    /**
     * Test inventory service automatic reordering.
     */
    public function test_inventory_service_automatic_reordering(): void
    {
        $supplier = Supplier::factory()->create();
        $part = Part::factory()->create([
            'supplier_id' => $supplier->id,
            'current_stock' => 10,
            'minimum_stock' => 20,
            'reorder_point' => 25,
            'reorder_quantity' => 50,
            'unit_cost' => 15.00,
            'lead_time_days' => 14,
        ]);
        
        $results = $this->inventoryService->processAutomaticReordering();
        
        $this->assertEquals(1, $results['processed']);
        $this->assertEquals(1, $results['created_orders']);
        $this->assertEmpty($results['errors']);
        
        // Check that purchase order was created
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);
        
        $this->assertDatabaseHas('purchase_order_items', [
            'part_id' => $part->id,
            'quantity' => 50,
            'unit_cost' => 15.00,
        ]);
    }

    /**
     * Test inventory service valuation calculation.
     */
    public function test_inventory_service_valuation_calculation(): void
    {
        // Create parts with different stock levels and costs
        Part::factory()->create([
            'current_stock' => 100,
            'average_cost' => 10.00,
        ]);
        
        Part::factory()->create([
            'current_stock' => 50,
            'average_cost' => 25.00,
        ]);
        
        Part::factory()->create([
            'current_stock' => 200,
            'average_cost' => 5.00,
        ]);
        
        $valuation = $this->inventoryService->calculateInventoryValuation();
        
        $this->assertArrayHasKey('total_value', $valuation);
        $this->assertArrayHasKey('by_category', $valuation);
        $this->assertArrayHasKey('by_location', $valuation);
        $this->assertArrayHasKey('low_stock_value', $valuation);
        $this->assertArrayHasKey('overstock_value', $valuation);
        $this->assertArrayHasKey('valuation_date', $valuation);
        
        // Total value should be: (100 * 10) + (50 * 25) + (200 * 5) = 1000 + 1250 + 1000 = 3250
        $this->assertEquals(3250, $valuation['total_value']);
    }

    /**
     * Test inventory service turnover analysis.
     */
    public function test_inventory_service_turnover_analysis(): void
    {
        $part = Part::factory()->create([
            'current_stock' => 100,
            'average_cost' => 10.00,
        ]);
        
        // Create usage transactions over the past year
        InventoryTransaction::factory()->count(50)->create([
            'part_id' => $part->id,
            'transaction_type' => 'issue',
            'quantity' => -5,
            'performed_at' => now()->subDays(rand(1, 365)),
        ]);
        
        $analysis = $this->inventoryService->generateInventoryTurnoverAnalysis();
        
        $this->assertArrayHasKey('parts', $analysis);
        $this->assertArrayHasKey('summary', $analysis);
        $this->assertArrayHasKey('generated_at', $analysis);
        
        $this->assertNotEmpty($analysis['parts']);
        $this->assertArrayHasKey('fast_moving', $analysis['summary']);
        $this->assertArrayHasKey('normal_moving', $analysis['summary']);
        $this->assertArrayHasKey('slow_moving', $analysis['summary']);
        $this->assertArrayHasKey('non_moving', $analysis['summary']);
    }

    /**
     * Test inventory service obsolete stock identification.
     */
    public function test_inventory_service_obsolete_stock_identification(): void
    {
        $part = Part::factory()->create([
            'current_stock' => 50,
            'average_cost' => 20.00,
        ]);
        
        // Create old transaction
        InventoryTransaction::factory()->create([
            'part_id' => $part->id,
            'performed_at' => now()->subDays(400), // Over a year ago
        ]);
        
        $obsoleteStock = $this->inventoryService->identifyObsoleteStock();
        
        $this->assertArrayHasKey('obsolete_items', $obsoleteStock);
        $this->assertArrayHasKey('total_obsolete_value', $obsoleteStock);
        $this->assertArrayHasKey('total_obsolete_count', $obsoleteStock);
        $this->assertArrayHasKey('generated_at', $obsoleteStock);
        
        $this->assertNotEmpty($obsoleteStock['obsolete_items']);
        $this->assertEquals(1000, $obsoleteStock['total_obsolete_value']); // 50 * 20
        $this->assertEquals(1, $obsoleteStock['total_obsolete_count']);
    }

    /**
     * Test inventory service ABC analysis.
     */
    public function test_inventory_service_abc_analysis(): void
    {
        // Create parts with different usage values
        $highValuePart = Part::factory()->create();
        $mediumValuePart = Part::factory()->create();
        $lowValuePart = Part::factory()->create();
        
        // Create usage transactions with different values
        InventoryTransaction::factory()->count(100)->create([
            'part_id' => $highValuePart->id,
            'transaction_type' => 'issue',
            'quantity' => -10,
            'unit_cost' => 100.00,
            'performed_at' => now()->subDays(rand(1, 365)),
        ]);
        
        InventoryTransaction::factory()->count(50)->create([
            'part_id' => $mediumValuePart->id,
            'transaction_type' => 'issue',
            'quantity' => -5,
            'unit_cost' => 20.00,
            'performed_at' => now()->subDays(rand(1, 365)),
        ]);
        
        InventoryTransaction::factory()->count(20)->create([
            'part_id' => $lowValuePart->id,
            'transaction_type' => 'issue',
            'quantity' => -2,
            'unit_cost' => 5.00,
            'performed_at' => now()->subDays(rand(1, 365)),
        ]);
        
        $abcAnalysis = $this->inventoryService->calculateABCAnalysis();
        
        $this->assertArrayHasKey('parts', $abcAnalysis);
        $this->assertArrayHasKey('summary', $abcAnalysis);
        $this->assertArrayHasKey('generated_at', $abcAnalysis);
        
        $this->assertNotEmpty($abcAnalysis['parts']);
        $this->assertEquals(3, $abcAnalysis['summary']['total_parts']);
        $this->assertArrayHasKey('class_a', $abcAnalysis['summary']);
        $this->assertArrayHasKey('class_b', $abcAnalysis['summary']);
        $this->assertArrayHasKey('class_c', $abcAnalysis['summary']);
    }

    /**
     * Test inventory without authentication.
     */
    public function test_inventory_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/inventory/parts');
        $response->assertStatus(401);

        $response = $this->postJson('/api/inventory/parts');
        $response->assertStatus(401);
    }

    /**
     * Test inventory with insufficient permissions.
     */
    public function test_inventory_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read inventory data
        $response = $this->getJson('/api/inventory/parts');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/inventory/statistics');
        $response->assertStatus(200);
        
        // But not be able to create parts
        $response = $this->postJson('/api/inventory/parts', [
            'name' => 'Test',
            'description' => 'Test',
            'part_number' => 'TEST-001',
            'unit_of_measure' => 'PCS',
        ]);
        $response->assertStatus(403);
    }
}
