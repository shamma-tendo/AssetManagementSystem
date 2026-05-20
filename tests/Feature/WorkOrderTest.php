<?php

namespace Tests\Feature;

use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkOrderStatus;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $technician = User::factory()->create(['role' => UserRole::TECHNICIAN]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for work order management.
     */
    private function createTestData(): void
    {
        // Create assets
        Asset::factory()->count(5)->create(['status' => 'active']);
        
        // Create some work orders
        WorkOrder::factory()->count(10)->create();
    }

    /**
     * Test work order listing.
     */
    public function test_work_order_listing(): void
    {
        $response = $this->getJson('/api/work-orders');

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
     * Test work order creation.
     */
    public function test_work_order_creation(): void
    {
        $asset = Asset::first();
        $technician = User::where('role', UserRole::TECHNICIAN)->first();

        $workOrderData = [
            'title' => 'Test Work Order',
            'description' => 'This is a test work order',
            'priority' => 'high',
            'type' => 'corrective_maintenance',
            'asset_id' => $asset->id,
            'assigned_to' => $technician->id,
            'estimated_hours' => 4.5,
            'estimated_cost' => 250.00,
            'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/work-orders', $workOrderData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Work order created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'priority',
                         'status',
                         'type',
                         'asset_id',
                         'assigned_to',
                         'created_by',
                         'estimated_hours',
                         'estimated_cost',
                         'scheduled_date',
                     ],
                 ]);

        $this->assertDatabaseHas('work_orders', [
            'title' => 'Test Work Order',
            'priority' => 'high',
            'type' => 'corrective_maintenance',
            'asset_id' => $asset->id,
            'assigned_to' => $technician->id,
        ]);
    }

    /**
     * Test work order creation validation.
     */
    public function test_work_order_creation_validation(): void
    {
        $response = $this->postJson('/api/work-orders', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'title',
                     'description',
                     'priority',
                     'type',
                     'asset_id',
                 ]);
    }

    /**
     * Test work order show.
     */
    public function test_work_order_show(): void
    {
        $workOrder = WorkOrder::first();

        $response = $this->getJson("/api/work-orders/{$workOrder->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'priority',
                         'status',
                         'type',
                         'asset',
                         'assignedTo',
                         'creator',
                         'parts',
                         'laborEntries',
                         'attachments',
                         'comments',
                         'history',
                     ],
                 ]);
    }

    /**
     * Test work order update.
     */
    public function test_work_order_update(): void
    {
        $workOrder = WorkOrder::first();
        $technician = User::where('role', UserRole::TECHNICIAN)->first();

        $updateData = [
            'title' => 'Updated Work Order',
            'priority' => 'urgent',
            'assigned_to' => $technician->id,
            'estimated_hours' => 6.0,
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/work-orders/{$workOrder->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Work order updated successfully',
                 ]);

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'title' => 'Updated Work Order',
            'priority' => 'urgent',
            'assigned_to' => $technician->id,
        ]);
    }

    /**
     * Test work order status transition.
     */
    public function test_work_order_status_transition(): void
    {
        $workOrder = WorkOrder::where('status', WorkOrderStatus::REQUESTED)->first();

        // Valid transition: requested -> approved
        $response = $this->putJson("/api/work-orders/{$workOrder->id}", [
            'status' => 'approved'
        ]);

        $response->assertStatus(200);
        
        $workOrder->refresh();
        $this->assertEquals('approved', $workOrder->status->value);

        // Valid transition: approved -> assigned
        $response = $this->putJson("/api/work-orders/{$workOrder->id}", [
            'status' => 'assigned'
        ]);

        $response->assertStatus(200);
        
        $workOrder->refresh();
        $this->assertEquals('assigned', $workOrder->status->value);
    }

    /**
     * Test invalid work order status transition.
     */
    public function test_invalid_work_order_status_transition(): void
    {
        $workOrder = WorkOrder::where('status', WorkOrderStatus::REQUESTED)->first();

        // Invalid transition: requested -> completed
        $response = $this->putJson("/api/work-orders/{$workOrder->id}", [
            'status' => 'completed'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test work order deletion.
     */
    public function test_work_order_deletion(): void
    {
        $workOrder = WorkOrder::where('status', WorkOrderStatus::REQUESTED)->first();

        $response = $this->deleteJson("/api/work-orders/{$workOrder->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Work order deleted successfully',
                 ]);

        $this->assertSoftDeleted('work_orders', ['id' => $workOrder->id]);
    }

    /**
     * Test work order deletion restrictions.
     */
    public function test_work_order_deletion_restrictions(): void
    {
        $workOrder = WorkOrder::where('status', WorkOrderStatus::IN_PROGRESS)->first();

        $response = $this->deleteJson("/api/work-orders/{$workOrder->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete work order in In Progress status',
                 ]);
    }

    /**
     * Test work order statistics.
     */
    public function test_work_order_statistics(): void
    {
        $response = $this->getJson('/api/work-orders/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'total_work_orders',
                         'by_status',
                         'by_priority',
                         'by_type',
                         'overdue_count',
                         'due_today_count',
                         'due_this_week_count',
                         'total_cost',
                         'total_hours',
                         'completion_rate',
                         'average_completion_time',
                         'recent_work_orders',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertGreaterThan(0, $stats['total_work_orders']);
        $this->assertArrayHasKey('requested', $stats['by_status']);
        $this->assertArrayHasKey('normal', $stats['by_priority']);
    }

    /**
     * Test work orders by status.
     */
    public function test_work_orders_by_status(): void
    {
        $response = $this->getJson('/api/work-orders/status/requested');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        foreach ($data as $workOrder) {
            $this->assertEquals('requested', $workOrder['status']);
        }
    }

    /**
     * Test invalid status filter.
     */
    public function test_invalid_status_filter(): void
    {
        $response = $this->getJson('/api/work-orders/status/invalid_status');

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid status',
                 ]);
    }

    /**
     * Test my work orders endpoint.
     */
    public function test_my_work_orders(): void
    {
        $technician = User::where('role', UserRole::TECHNICIAN)->first();
        Sanctum::actingAs($technician);

        // Create work orders assigned to this technician
        WorkOrder::factory()->count(3)->create(['assigned_to' => $technician->id]);

        $response = $this->getJson('/api/work-orders/my');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        foreach ($data as $workOrder) {
            $this->assertEquals($technician->id, $workOrder['assigned_to']['id']);
        }
    }

    /**
     * Test adding comment to work order.
     */
    public function test_add_work_order_comment(): void
    {
        $workOrder = WorkOrder::first();

        $commentData = [
            'comment' => 'This is a test comment',
            'is_internal' => false,
            'is_technician_note' => false,
        ];

        $response = $this->postJson("/api/work-orders/{$workOrder->id}/comments", $commentData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Comment added successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'comment',
                         'is_internal',
                         'is_technician_note',
                         'user',
                         'created_at',
                     ],
                 ]);

        $this->assertDatabaseHas('work_order_comments', [
            'work_order_id' => $workOrder->id,
            'comment' => 'This is a test comment',
        ]);
    }

    /**
     * Test work order calendar view.
     */
    public function test_work_order_calendar(): void
    {
        // Create work orders with scheduled dates
        WorkOrder::factory()->create([
            'scheduled_date' => now()->addDays(5),
            'status' => WorkOrderStatus::SCHEDULED,
        ]);

        $response = $this->getJson('/api/work-orders/calendar');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'start',
                             'backgroundColor',
                             'borderColor',
                             'extendedProps',
                         ],
                     ],
                 ]);
    }

    /**
     * Test work order filtering.
     */
    public function test_work_order_filtering(): void
    {
        $asset = Asset::first();
        $technician = User::where('role', UserRole::TECHNICIAN)->first();

        // Test priority filter
        $response = $this->getJson('/api/work-orders?priority=high');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $workOrder) {
            $this->assertEquals('high', $workOrder['priority']);
        }

        // Test asset filter
        $response = $this->getJson("/api/work-orders?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $workOrder) {
            $this->assertEquals($asset->id, $workOrder['asset_id']);
        }

        // Test assigned to filter
        $response = $this->getJson("/api/work-orders?assigned_to={$technician->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $workOrder) {
            $this->assertEquals($technician->id, $workOrder['assigned_to']);
        }
    }

    /**
     * Test work order search.
     */
    public function test_work_order_search(): void
    {
        // Create a work order with specific title
        WorkOrder::factory()->create(['title' => 'Special Maintenance Task']);

        $response = $this->getJson('/api/work-orders?search=Special');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $found = false;
        foreach ($data as $workOrder) {
            if (str_contains(strtolower($workOrder['title']), 'special')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test work order sorting.
     */
    public function test_work_order_sorting(): void
    {
        // Test sort by priority
        $response = $this->getJson('/api/work-orders?sort_by=priority&sort_order=desc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            $priorityOrder = ['emergency', 'urgent', 'high', 'normal', 'low'];
            for ($i = 0; $i < count($data) - 1; $i++) {
                $currentPriority = array_search($data[$i]['priority'], $priorityOrder);
                $nextPriority = array_search($data[$i + 1]['priority'], $priorityOrder);
                $this->assertLessThanOrEqual($currentPriority, $nextPriority);
            }
        }

        // Test sort by scheduled date
        $response = $this->getJson('/api/work-orders?sort_by=scheduled_date&sort_order=asc');
        $response->assertStatus(200);
    }

    /**
     * Test work order without authentication.
     */
    public function test_work_order_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/work-orders');
        $response->assertStatus(401);

        $response = $this->postJson('/api/work-orders');
        $response->assertStatus(401);
    }

    /**
     * Test work order with insufficient permissions.
     */
    public function test_work_order_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read work orders
        $response = $this->getJson('/api/work-orders');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/work-orders/statistics');
        $response->assertStatus(200);
        
        // But not be able to create work orders
        $response = $this->postJson('/api/work-orders', [
            'title' => 'Test',
            'description' => 'Test',
            'priority' => 'normal',
            'type' => 'corrective_maintenance',
            'asset_id' => Asset::first()->id,
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test work order model relationships.
     */
    public function test_work_order_model_relationships(): void
    {
        $workOrder = WorkOrder::factory()->create();
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $workOrder->asset);
        
        // Test assigned to relationship
        $this->assertInstanceOf(User::class, $workOrder->assignedTo);
        
        // Test creator relationship
        $this->assertInstanceOf(User::class, $workOrder->creator);
        
        // Test parts relationship
        $this->assertEmpty($workOrder->parts);
        
        // Test labor entries relationship
        $this->assertEmpty($workOrder->laborEntries);
        
        // Test comments relationship
        $this->assertEmpty($workOrder->comments);
        
        // Test history relationship
        $this->assertEmpty($workOrder->history);
    }

    /**
     * Test work order scopes.
     */
    public function test_work_order_scopes(): void
    {
        // Create overdue work order
        WorkOrder::factory()->create([
            'scheduled_date' => now()->subDays(1),
            'status' => WorkOrderStatus::SCHEDULED,
        ]);

        // Test overdue scope
        $overdueWorkOrders = WorkOrder::overdue()->get();
        $this->assertNotEmpty($overdueWorkOrders);
        foreach ($overdueWorkOrders as $workOrder) {
            $this->assertTrue($workOrder->isOverdue());
        }

        // Test due today scope
        WorkOrder::factory()->create([
            'scheduled_date' => today(),
            'status' => WorkOrderStatus::SCHEDULED,
        ]);

        $dueTodayWorkOrders = WorkOrder::dueToday()->get();
        $this->assertNotEmpty($dueTodayWorkOrders);
        foreach ($dueTodayWorkOrders as $workOrder) {
            $this->assertEquals(today()->format('Y-m-d'), $workOrder->scheduled_date->format('Y-m-d'));
        }

        // Test due this week scope
        $dueThisWeekWorkOrders = WorkOrder::dueThisWeek()->get();
        $this->assertNotEmpty($dueThisWeekWorkOrders);
    }
}
