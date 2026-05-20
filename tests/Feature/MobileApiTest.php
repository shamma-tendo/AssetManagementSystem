<?php

namespace Tests\Feature;

use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkOrderStatus;
use App\Models\MaintenanceSchedule;
use App\Models\WorkOrderLabor;
use App\Models\WorkOrderComment;
use App\Models\WorkOrderAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $technician = User::factory()->create(['role' => UserRole::TECHNICIAN]);
        Sanctum::actingAs($technician);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for mobile API testing.
     */
    private function createTestData(): void
    {
        $technician = auth()->user();
        
        // Create assets
        Asset::factory()->count(5)->create(['status' => 'active']);
        
        // Create work orders assigned to technician
        WorkOrder::factory()->count(10)->create(['assigned_to' => $technician->id]);
        
        // Create maintenance schedules assigned to technician
        MaintenanceSchedule::factory()->count(5)->create(['assigned_technician_id' => $technician->id]);
    }

    /**
     * Test mobile dashboard.
     */
    public function test_mobile_dashboard(): void
    {
        $response = $this->getJson('/api/mobile/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'user',
                         'summary',
                         'today_work_orders',
                         'overdue_work_orders',
                         'upcoming_maintenance',
                         'recent_assets',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify user structure
        $this->assertArrayHasKey('id', $data['user']);
        $this->assertArrayHasKey('name', $data['user']);
        $this->assertArrayHasKey('email', $data['user']);
        $this->assertArrayHasKey('role', $data['user']);
        
        // Verify summary structure
        $this->assertArrayHasKey('today_work_orders', $data['summary']);
        $this->assertArrayHasKey('overdue_work_orders', $data['summary']);
        $this->assertArrayHasKey('upcoming_maintenance', $data['summary']);
        $this->assertArrayHasKey('total_assigned', $data['summary']);
        
        // Verify arrays are returned
        $this->assertIsArray($data['today_work_orders']);
        $this->assertIsArray($data['overdue_work_orders']);
        $this->assertIsArray($data['upcoming_maintenance']);
        $this->assertIsArray($data['recent_assets']);
    }

    /**
     * Test mobile work orders listing.
     */
    public function test_mobile_work_orders(): void
    {
        $response = $this->getJson('/api/mobile/work-orders');

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
        
        // Verify work order structure
        foreach ($data as $workOrder) {
            $this->assertArrayHasKey('id', $workOrder);
            $this->assertArrayHasKey('title', $workOrder);
            $this->assertArrayHasKey('asset_name', $workOrder);
            $this->assertArrayHasKey('priority', $workOrder);
            $this->assertArrayHasKey('status', $workOrder);
            $this->assertArrayHasKey('scheduled_date', $workOrder);
        }
    }

    /**
     * Test mobile work orders with filters.
     */
    public function test_mobile_work_orders_with_filters(): void
    {
        // Test status filter
        $response = $this->getJson('/api/mobile/work-orders?status=in_progress');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $workOrder) {
            $this->assertEquals('in_progress', $workOrder['status']);
        }

        // Test today filter
        $response = $this->getJson('/api/mobile/work-orders?today=1');
        $response->assertStatus(200);

        // Test overdue filter
        $response = $this->getJson('/api/mobile/work-orders?overdue=1');
        $response->assertStatus(200);

        // Test this week filter
        $response = $this->getJson('/api/mobile/work-orders?this_week=1');
        $response->assertStatus(200);
    }

    /**
     * Test mobile work order details.
     */
    public function test_mobile_work_order_details(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())->first();

        $response = $this->getJson("/api/mobile/work-orders/{$workOrder->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'work_order',
                         'asset',
                         'location',
                         'assigned_technician',
                         'creator',
                         'parts',
                         'labor_entries',
                         'attachments',
                         'comments',
                         'maintenance_schedule',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify work order details structure
        $this->assertArrayHasKey('id', $data['work_order']);
        $this->assertArrayHasKey('title', $data['work_order']);
        $this->assertArrayHasKey('description', $data['work_order']);
        $this->assertArrayHasKey('priority', $data['work_order']);
        $this->assertArrayHasKey('status', $data['work_order']);
        $this->assertArrayHasKey('scheduled_date', $data['work_order']);
        $this->assertArrayHasKey('estimated_hours', $data['work_order']);
        $this->assertArrayHasKey('actual_hours', $data['work_order']);
        
        // Verify related data structure
        $this->assertIsArray($data['parts']);
        $this->assertIsArray($data['labor_entries']);
        $this->assertIsArray($data['attachments']);
        $this->assertIsArray($data['comments']);
    }

    /**
     * Test mobile work order access denied.
     */
    public function test_mobile_work_order_access_denied(): void
    {
        // Create work order assigned to different user
        $otherUser = User::factory()->create(['role' => UserRole::TECHNICIAN]);
        $workOrder = WorkOrder::factory()->create(['assigned_to' => $otherUser->id]);

        $response = $this->getJson("/api/mobile/work-orders/{$workOrder->id}");

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Access denied',
                 ]);
    }

    /**
     * Test mobile work order status update.
     */
    public function test_mobile_work_order_status_update(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())
            ->where('status', 'scheduled')
            ->first();

        $updateData = [
            'status' => 'in_progress',
            'notes' => 'Starting work on this task',
        ];

        $response = $this->putJson("/api/mobile/work-orders/{$workOrder->id}/status", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Work order status updated successfully',
                 ]);

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Test mobile work order completion.
     */
    public function test_mobile_work_order_completion(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        $completionData = [
            'status' => 'completed',
            'completion_notes' => 'Work completed successfully',
            'work_performed' => 'Replaced the faulty component and tested functionality',
            'actual_hours' => 3.5,
            'actual_cost' => 250.00,
            'parts_used' => [
                ['name' => 'Replacement Part', 'quantity' => 1, 'cost' => 150.00],
            ],
            'tools_used' => ['Wrench', 'Screwdriver'],
            'follow_up_required' => false,
        ];

        $response = $this->putJson("/api/mobile/work-orders/{$workOrder->id}/status", $completionData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Work order status updated successfully',
                 ]);

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => 'completed',
            'actual_hours' => 3.5,
            'actual_cost' => 250.00,
        ]);
    }

    /**
     * Test mobile work order invalid status transition.
     */
    public function test_mobile_work_order_invalid_status_transition(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())
            ->where('status', 'scheduled')
            ->first();

        $response = $this->putJson("/api/mobile/work-orders/{$workOrder->id}/status", [
            'status' => 'closed', // Invalid transition
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test mobile add time entry.
     */
    public function test_mobile_add_time_entry(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())->first();

        $timeEntryData = [
            'hours_worked' => 2.5,
            'work_description' => 'Initial inspection and troubleshooting',
            'notes' => 'Found issue with the main component',
            'start_time' => '09:00',
            'end_time' => '11:30',
        ];

        $response = $this->postJson("/api/mobile/work-orders/{$workOrder->id}/time-entry", $timeEntryData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Time entry added successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'hours_worked',
                         'total_cost',
                         'technician',
                     ],
                 ]);

        $this->assertDatabaseHas('work_order_labor', [
            'work_order_id' => $workOrder->id,
            'technician_id' => auth()->id(),
            'hours_worked' => 2.5,
        ]);
    }

    /**
     * Test mobile add comment.
     */
    public function test_mobile_add_comment(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())->first();

        $commentData = [
            'comment' => 'Customer mentioned unusual noise during operation',
            'is_technician_note' => true,
        ];

        $response = $this->postJson("/api/mobile/work-orders/{$workOrder->id}/comments", $commentData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Comment added successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'comment',
                         'is_technician_note',
                         'user',
                     ],
                 ]);

        $this->assertDatabaseHas('work_order_comments', [
            'work_order_id' => $workOrder->id,
            'user_id' => auth()->id(),
            'comment' => 'Customer mentioned unusual noise during operation',
            'is_technician_note' => true,
        ]);
    }

    /**
     * Test mobile upload attachment.
     */
    public function test_mobile_upload_attachment(): void
    {
        $workOrder = WorkOrder::where('assigned_to', auth()->id())->first();

        $file = UploadedFile::fake()->create('test-photo.jpg', 1024, 'image/jpeg');

        $attachmentData = [
            'file' => $file,
            'description' => 'Photo of the issue before repair',
            'is_public' => true,
        ];

        $response = $this->postJson("/api/mobile/work-orders/{$workOrder->id}/attachments", $attachmentData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'File uploaded successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'file_name',
                         'original_name',
                         'file_size',
                         'mime_type',
                         'uploader',
                     ],
                 ]);

        $this->assertDatabaseHas('work_order_attachments', [
            'work_order_id' => $workOrder->id,
            'uploaded_by' => auth()->id(),
            'original_name' => 'test-photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    /**
     * Test mobile assets listing.
     */
    public function test_mobile_assets(): void
    {
        $response = $this->getJson('/api/mobile/assets');

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
        
        // Verify asset structure
        foreach ($data as $asset) {
            $this->assertArrayHasKey('id', $asset);
            $this->assertArrayHasKey('name', $asset);
            $this->assertArrayHasKey('serial_number', $asset);
            $this->assertArrayHasKey('status', $asset);
            $this->assertArrayHasKey('category', $asset);
            $this->assertArrayHasKey('location', $asset);
        }
    }

    /**
     * Test mobile assets with filters.
     */
    public function test_mobile_assets_with_filters(): void
    {
        // Test search filter
        $response = $this->getJson('/api/mobile/assets?search=test');
        $response->assertStatus(200);

        // Test status filter
        $response = $this->getJson('/api/mobile/assets?status=active');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $asset) {
            $this->assertEquals('active', $asset['status']);
        }

        // Test my assets filter
        $response = $this->getJson('/api/mobile/assets?my_assets=1');
        $response->assertStatus(200);

        // Test active filter
        $response = $this->getJson('/api/mobile/assets?active=1');
        $response->assertStatus(200);
    }

    /**
     * Test mobile asset details.
     */
    public function test_mobile_asset_details(): void
    {
        $asset = Asset::first();

        $response = $this->getJson("/api/mobile/assets/{$asset->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'asset',
                         'recent_work_orders',
                         'maintenance_schedules',
                         'depreciation_records',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify asset details structure
        $this->assertArrayHasKey('id', $data['asset']);
        $this->assertArrayHasKey('name', $data['asset']);
        $this->assertArrayHasKey('serial_number', $data['asset']);
        $this->assertArrayHasKey('status', $data['asset']);
        $this->assertArrayHasKey('category', $data['asset']);
        $this->assertArrayHasKey('location', $data['asset']);
        $this->assertArrayHasKey('purchase_date', $data['asset']);
        $this->assertArrayHasKey('current_value', $data['asset']);
        
        // Verify related data structure
        $this->assertIsArray($data['recent_work_orders']);
        $this->assertIsArray($data['maintenance_schedules']);
        $this->assertIsArray($data['depreciation_records']);
    }

    /**
     * Test mobile maintenance schedules.
     */
    public function test_mobile_maintenance_schedules(): void
    {
        $response = $this->getJson('/api/mobile/maintenance-schedules');

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
        
        // Verify schedule structure
        foreach ($data as $schedule) {
            $this->assertArrayHasKey('id', $schedule);
            $this->assertArrayHasKey('title', $schedule);
            $this->assertArrayHasKey('maintenance_type', $schedule);
            $this->assertArrayHasKey('frequency_type', $schedule);
            $this->assertArrayHasKey('next_due_date', $schedule);
            $this->assertArrayHasKey('asset', $schedule);
        }
    }

    /**
     * Test mobile maintenance schedules with filters.
     */
    public function test_mobile_maintenance_schedules_with_filters(): void
    {
        // Test assigned to me filter
        $response = $this->getJson('/api/mobile/maintenance-schedules?assigned_to_me=1');
        $response->assertStatus(200);

        // Test overdue filter
        $response = $this->getJson('/api/mobile/maintenance-schedules?overdue=1');
        $response->assertStatus(200);

        // Test due soon filter
        $response = $this->getJson('/api/mobile/maintenance-schedules?due_soon=1&days=30');
        $response->assertStatus(200);
    }

    /**
     * Test mobile calendar view.
     */
    public function test_mobile_calendar(): void
    {
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');

        $response = $this->getJson("/api/mobile/calendar?start_date={$startDate}&end_date={$endDate}");

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
                             'type',
                             'extendedProps',
                         ],
                     ],
                 ]);

        $events = $response->json('data');
        $this->assertNotEmpty($events);
        
        // Verify event structure
        foreach ($events as $event) {
            $this->assertArrayHasKey('id', $event);
            $this->assertArrayHasKey('title', $event);
            $this->assertArrayHasKey('start', $event);
            $this->assertArrayHasKey('type', $event);
            $this->assertArrayHasKey('extendedProps', $event);
            $this->assertContains($event['type'], ['work_order', 'maintenance_schedule']);
        }
    }

    /**
     * Test mobile search.
     */
    public function test_mobile_search(): void
    {
        // Test asset search
        $response = $this->getJson('/api/mobile/search?query=test&type=assets');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success' => true,
                     'data' => [
                         'items',
                         'pagination',
                     ],
                 ]);

        // Test work order search
        $response = $this->getJson('/api/mobile/search?query=test&type=work_orders');
        $response->assertStatus(200);

        // Test maintenance schedule search
        $response = $this->getJson('/api/mobile/search?query=test&type=maintenance_schedules');
        $response->assertStatus(200);
    }

    /**
     * Test mobile search validation.
     */
    public function test_mobile_search_validation(): void
    {
        // Test missing query
        $response = $this->getJson('/api/mobile/search?type=assets');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['query']);

        // Test invalid type
        $response = $this->getJson('/api/mobile/search?query=test&type=invalid');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type']);

        // Test query too short
        $response = $this->getJson('/api/mobile/search?query=a&type=assets');
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['query']);
    }

    /**
     * Test mobile profile.
     */
    public function test_mobile_profile(): void
    {
        $response = $this->getJson('/api/mobile/profile');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'username',
                         'phone',
                         'role',
                         'department',
                         'location',
                         'avatar',
                         'is_active',
                         'last_login_at',
                         'created_at',
                         'preferences',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals(auth()->id(), $data['id']);
        $this->assertArrayHasKey('preferences', $data);
    }

    /**
     * Test mobile profile update.
     */
    public function test_mobile_profile_update(): void
    {
        $updateData = [
            'phone' => '+1234567890',
            'preferences' => [
                'notifications' => false,
                'theme' => 'dark',
                'language' => 'en',
            ],
        ];

        $response = $this->putJson('/api/mobile/profile', $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profile updated successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => auth()->id(),
            'phone' => '+1234567890',
        ]);
    }

    /**
     * Test mobile profile avatar upload.
     */
    public function test_mobile_profile_avatar_upload(): void
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 512, 'image/jpeg');

        $response = $this->putJson('/api/mobile/profile', [
            'avatar' => $file,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profile updated successfully',
                 ]);
    }

    /**
     * Test mobile notifications.
     */
    public function test_mobile_notifications(): void
    {
        // Create overdue work order
        $workOrder = WorkOrder::factory()->create([
            'assigned_to' => auth()->id(),
            'scheduled_date' => now()->subDays(2),
            'status' => 'scheduled',
        ]);

        // Create overdue maintenance schedule
        $schedule = MaintenanceSchedule::factory()->create([
            'assigned_technician_id' => auth()->id(),
            'next_due_date' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/mobile/notifications');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'type',
                             'title',
                             'message',
                             'priority',
                             'created_at',
                             'data',
                         ],
                     ],
                 ]);

        $notifications = $response->json('data');
        $this->assertNotEmpty($notifications);
        
        // Verify notification structure
        foreach ($notifications as $notification) {
            $this->assertArrayHasKey('type', $notification);
            $this->assertArrayHasKey('title', $notification);
            $this->assertArrayHasKey('message', $notification);
            $this->assertArrayHasKey('priority', $notification);
            $this->assertArrayHasKey('data', $notification);
            
            // Verify notification types
            $this->assertContains($notification['type'], [
                'overdue_work_order',
                'due_soon_work_order',
                'overdue_maintenance'
            ]);
        }
    }

    /**
     * Test mobile statistics.
     */
    public function test_mobile_statistics(): void
    {
        $response = $this->getJson('/api/mobile/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'work_orders',
                         'maintenance_schedules',
                         'assets',
                         'performance',
                     ],
                 ]);

        $data = $response->json('data');
        
        // Verify work order statistics
        $this->assertArrayHasKey('total', $data['work_orders']);
        $this->assertArrayHasKey('completed', $data['work_orders']);
        $this->assertArrayHasKey('in_progress', $data['work_orders']);
        $this->assertArrayHasKey('overdue', $data['work_orders']);
        $this->assertArrayHasKey('due_today', $data['work_orders']);
        $this->assertArrayHasKey('due_this_week', $data['work_orders']);
        
        // Verify maintenance schedule statistics
        $this->assertArrayHasKey('total', $data['maintenance_schedules']);
        $this->assertArrayHasKey('active', $data['maintenance_schedules']);
        $this->assertArrayHasKey('overdue', $data['maintenance_schedules']);
        $this->assertArrayHasKey('due_soon', $data['maintenance_schedules']);
        
        // Verify asset statistics
        $this->assertArrayHasKey('total', $data['assets']);
        $this->assertArrayHasKey('active', $data['assets']);
        $this->assertArrayHasKey('under_maintenance', $data['assets']);
        
        // Verify performance statistics
        $this->assertArrayHasKey('completion_rate', $data['performance']);
        $this->assertArrayHasKey('average_completion_time', $data['performance']);
        $this->assertArrayHasKey('total_hours_worked', $data['performance']);
    }

    /**
     * Test mobile API without authentication.
     */
    public function test_mobile_api_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/mobile/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/mobile/work-orders');
        $response->assertStatus(401);

        $response = $this->getJson('/api/mobile/profile');
        $response->assertStatus(401);
    }

    /**
     * Test mobile API pagination limits.
     */
    public function test_mobile_api_pagination_limits(): void
    {
        // Test work orders with high per_page
        $response = $this->getJson('/api/mobile/work-orders?per_page=100');
        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertLessThanOrEqual(50, $pagination['per_page']); // Should be limited to 50

        // Test assets with high per_page
        $response = $this->getJson('/api/mobile/assets?per_page=100');
        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertLessThanOrEqual(50, $pagination['per_page']);
    }

    /**
     * Test mobile API data optimization.
     */
    public function test_mobile_api_data_optimization(): void
    {
        $response = $this->getJson('/api/mobile/work-orders');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        // Verify only essential fields are returned
        foreach ($data as $workOrder) {
            $this->assertArrayHasKey('id', $workOrder);
            $this->assertArrayHasKey('title', $workOrder);
            $this->assertArrayHasKey('asset_name', $workOrder);
            $this->assertArrayHasKey('priority', $workOrder);
            $this->assertArrayHasKey('status', $workOrder);
            
            // Verify no unnecessary fields
            $this->assertArrayNotHasKey('description', $workOrder);
            $this->assertArrayNotHasKey('notes', $workOrder);
            $this->assertArrayNotHasKey('work_performed', $workOrder);
        }
    }
}
