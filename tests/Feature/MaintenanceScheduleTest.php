<?php

namespace Tests\Feature;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceHistory;
use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\MaintenanceType;
use App\Models\FrequencyType;
use App\Services\MaintenanceSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected MaintenanceSchedulingService $schedulingService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->schedulingService = app(MaintenanceSchedulingService::class);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $technician = User::factory()->create(['role' => UserRole::TECHNICIAN]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for maintenance scheduling.
     */
    private function createTestData(): void
    {
        // Create assets
        Asset::factory()->count(5)->create(['status' => 'active']);
        
        // Create some maintenance schedules
        MaintenanceSchedule::factory()->count(10)->create();
    }

    /**
     * Test maintenance schedule listing.
     */
    public function test_maintenance_schedule_listing(): void
    {
        $response = $this->getJson('/api/maintenance-schedules');

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
     * Test maintenance schedule creation.
     */
    public function test_maintenance_schedule_creation(): void
    {
        $asset = Asset::first();
        $technician = User::where('role', UserRole::TECHNICIAN)->first();

        $scheduleData = [
            'asset_id' => $asset->id,
            'title' => 'Monthly Preventive Maintenance',
            'description' => 'Routine preventive maintenance for the asset',
            'maintenance_type' => 'preventive',
            'frequency_type' => 'monthly',
            'frequency_months' => 1,
            'next_due_date' => now()->addMonth()->format('Y-m-d'),
            'auto_create_work_order' => true,
            'work_order_priority' => 'normal',
            'assigned_technician_id' => $technician->id,
            'estimated_duration_hours' => 2.5,
            'estimated_cost' => 150.00,
            'required_parts' => [
                ['name' => 'Oil Filter', 'quantity' => 1],
                ['name' => 'Air Filter', 'quantity' => 1],
            ],
            'checklist_items' => [
                'Check oil level',
                'Inspect belts',
                'Test safety features',
            ],
        ];

        $response = $this->postJson('/api/maintenance-schedules', $scheduleData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Maintenance schedule created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'maintenance_type',
                         'frequency_type',
                         'frequency_months',
                         'next_due_date',
                         'auto_create_work_order',
                         'work_order_priority',
                         'assigned_technician_id',
                         'estimated_duration_hours',
                         'estimated_cost',
                     ],
                 ]);

        $this->assertDatabaseHas('maintenance_schedules', [
            'title' => 'Monthly Preventive Maintenance',
            'maintenance_type' => 'preventive',
            'frequency_type' => 'monthly',
            'frequency_months' => 1,
            'asset_id' => $asset->id,
            'assigned_technician_id' => $technician->id,
        ]);
    }

    /**
     * Test maintenance schedule creation validation.
     */
    public function test_maintenance_schedule_creation_validation(): void
    {
        $response = $this->postJson('/api/maintenance-schedules', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'asset_id',
                     'title',
                     'description',
                     'maintenance_type',
                     'frequency_type',
                     'frequency_interval',
                     'next_due_date',
                 ]);
    }

    /**
     * Test maintenance schedule show.
     */
    public function test_maintenance_schedule_show(): void
    {
        $schedule = MaintenanceSchedule::first();

        $response = $this->getJson("/api/maintenance-schedules/{$schedule->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'maintenance_type',
                         'frequency_type',
                         'asset',
                         'assignedTechnician',
                         'creator',
                         'workOrders',
                         'maintenanceHistory',
                     ],
                 ]);
    }

    /**
     * Test maintenance schedule update.
     */
    public function test_maintenance_schedule_update(): void
    {
        $schedule = MaintenanceSchedule::first();
        $technician = User::where('role', UserRole::TECHNICIAN)->first();

        $updateData = [
            'title' => 'Updated Maintenance Schedule',
            'frequency_months' => 2,
            'work_order_priority' => 'high',
            'assigned_technician_id' => $technician->id,
            'estimated_duration_hours' => 3.0,
        ];

        $response = $this->putJson("/api/maintenance-schedules/{$schedule->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Maintenance schedule updated successfully',
                 ]);

        $this->assertDatabaseHas('maintenance_schedules', [
            'id' => $schedule->id,
            'title' => 'Updated Maintenance Schedule',
            'frequency_months' => 2,
            'work_order_priority' => 'high',
            'assigned_technician_id' => $technician->id,
        ]);
    }

    /**
     * Test maintenance schedule deletion.
     */
    public function test_maintenance_schedule_deletion(): void
    {
        $schedule = MaintenanceSchedule::factory()->create();

        $response = $this->deleteJson("/api/maintenance-schedules/{$schedule->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Maintenance schedule deleted successfully',
                 ]);

        $this->assertSoftDeleted('maintenance_schedules', ['id' => $schedule->id]);
    }

    /**
     * Test maintenance schedule deletion restrictions.
     */
    public function test_maintenance_schedule_deletion_restrictions(): void
    {
        // Create a schedule with active work order
        $schedule = MaintenanceSchedule::factory()->create();
        WorkOrder::factory()->create([
            'maintenance_schedule_id' => $schedule->id,
            'status' => 'in_progress',
        ]);

        $response = $this->deleteJson("/api/maintenance-schedules/{$schedule->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete schedule with active or completed work orders',
                 ]);
    }

    /**
     * Test maintenance schedule statistics.
     */
    public function test_maintenance_schedule_statistics(): void
    {
        $response = $this->getJson('/api/maintenance-schedules/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'total_schedules',
                         'active_schedules',
                         'overdue_count',
                         'due_soon_count',
                         'auto_create_count',
                         'by_maintenance_type',
                         'by_frequency_type',
                         'compliance_rate',
                         'upcoming_maintenance',
                         'overdue_maintenance',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertGreaterThan(0, $stats['total_schedules']);
        $this->assertArrayHasKey('preventive', $stats['by_maintenance_type']);
        $this->assertArrayHasKey('monthly', $stats['by_frequency_type']);
    }

    /**
     * Test work order creation from schedule.
     */
    public function test_create_work_order_from_schedule(): void
    {
        $schedule = MaintenanceSchedule::factory()->create([
            'auto_create_work_order' => false,
            'next_due_date' => now()->addDays(5),
        ]);

        $response = $this->postJson("/api/maintenance-schedules/{$schedule->id}/create-work-order");

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Work order created successfully from maintenance schedule',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'type',
                         'priority',
                         'asset',
                         'assignedTo',
                         'maintenance_schedule_id',
                     ],
                 ]);

        $this->assertDatabaseHas('work_orders', [
            'maintenance_schedule_id' => $schedule->id,
            'type' => 'preventive_maintenance',
            'scheduled_date' => $schedule->next_due_date->format('Y-m-d'),
        ]);
    }

    /**
     * Test duplicate work order prevention.
     */
    public function test_duplicate_work_order_prevention(): void
    {
        $schedule = MaintenanceSchedule::factory()->create();
        
        // Create first work order
        $this->postJson("/api/maintenance-schedules/{$schedule->id}/create-work-order");
        
        // Try to create second work order for same date
        $response = $this->postJson("/api/maintenance-schedules/{$schedule->id}/create-work-order");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Work order already exists for this maintenance schedule',
                 ]);
    }

    /**
     * Test marking maintenance as performed.
     */
    public function test_mark_maintenance_as_performed(): void
    {
        $schedule = MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->subDays(2),
        ]);

        $performData = [
            'performed_date' => now()->subDays(2)->format('Y-m-d'),
            'actual_duration_hours' => 2.5,
            'actual_cost' => 180.00,
            'notes' => 'Maintenance completed successfully',
            'findings' => 'Minor wear on belts',
            'performance_rating' => 4,
            'checklist_completed' => true,
            'checklist_items' => [
                ['item' => 'Oil check', 'completed' => true],
                ['item' => 'Filter check', 'completed' => true],
            ],
        ];

        $response = $this->postJson("/api/maintenance-schedules/{$schedule->id}/mark-performed", $performData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Maintenance marked as performed successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'history',
                         'schedule',
                     ],
                 ]);

        $this->assertDatabaseHas('maintenance_history', [
            'maintenance_schedule_id' => $schedule->id,
            'performed_date' => $performData['performed_date'],
            'actual_duration_hours' => 2.5,
            'actual_cost' => 180.00,
            'performance_rating' => 4,
            'completed_on_time' => true,
        ]);

        // Check that schedule was updated
        $schedule->refresh();
        $this->assertEquals($performData['performed_date'], $schedule->last_performed_date->format('Y-m-d'));
        $this->assertNotNull($schedule->next_due_date);
    }

    /**
     * Test maintenance calendar view.
     */
    public function test_maintenance_calendar(): void
    {
        // Create schedules with specific due dates
        MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->addDays(5),
            'title' => 'Upcoming Maintenance 1',
        ]);
        
        MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->addDays(15),
            'title' => 'Upcoming Maintenance 2',
        ]);

        $response = $this->getJson('/api/maintenance-schedules/calendar');

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
                             'extendedProps',
                         ],
                     ],
                 ]);

        $events = $response->json('data');
        $this->assertNotEmpty($events);
        
        // Check event structure
        $event = $events[0];
        $this->assertArrayHasKey('id', $event);
        $this->assertArrayHasKey('title', $event);
        $this->assertArrayHasKey('start', $event);
        $this->assertArrayHasKey('backgroundColor', $event);
        $this->assertArrayHasKey('extendedProps', $event);
    }

    /**
     * Test automated scheduling.
     */
    public function test_automated_scheduling(): void
    {
        // Create schedules that should trigger automated work orders
        MaintenanceSchedule::factory()->create([
            'auto_create_work_order' => true,
            'next_due_date' => now()->addDays(2), // Due soon
            'title' => 'Due Soon Schedule',
        ]);

        MaintenanceSchedule::factory()->create([
            'auto_create_work_order' => true,
            'next_due_date' => now()->subDays(1), // Overdue
            'title' => 'Overdue Schedule',
        ]);

        $response = $this->postJson('/api/maintenance-schedules/automated-scheduling');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Automated scheduling completed',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'processed',
                         'work_orders_created',
                         'overdue_found',
                         'due_soon_found',
                         'errors',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, $data['processed']);
        $this->assertGreaterThan(0, $data['work_orders_created']);
    }

    /**
     * Test maintenance schedule filtering.
     */
    public function test_maintenance_schedule_filtering(): void
    {
        $asset = Asset::first();
        $technician = User::where('role', UserRole::TECHNICIAN)->first();

        // Test asset filter
        $response = $this->getJson("/api/maintenance-schedules?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $schedule) {
            $this->assertEquals($asset->id, $schedule['asset_id']);
        }

        // Test maintenance type filter
        $response = $this->getJson('/api/maintenance-schedules?maintenance_type=preventive');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $schedule) {
            $this->assertEquals('preventive', $schedule['maintenance_type']);
        }

        // Test auto-create filter
        $response = $this->getJson('/api/maintenance-schedules?auto_create_work_order=1');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $schedule) {
            $this->assertTrue($schedule['auto_create_work_order']);
        }

        // Test overdue filter
        $response = $this->getJson('/api/maintenance-schedules?overdue=1');
        $response->assertStatus(200);
    }

    /**
     * Test maintenance schedule search.
     */
    public function test_maintenance_schedule_search(): void
    {
        // Create a schedule with specific title
        MaintenanceSchedule::factory()->create(['title' => 'Special Maintenance Task']);

        $response = $this->getJson('/api/maintenance-schedules?search=Special');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $found = false;
        foreach ($data as $schedule) {
            if (str_contains(strtolower($schedule['title']), 'special')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test maintenance schedule sorting.
     */
    public function test_maintenance_schedule_sorting(): void
    {
        // Test sort by next_due_date
        $response = $this->getJson('/api/maintenance-schedules?sort_by=next_due_date&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    $data[$i]['next_due_date'],
                    $data[$i + 1]['next_due_date']
                );
            }
        }

        // Test sort by title
        $response = $this->getJson('/api/maintenance-schedules?sort_by=title&sort_order=asc');
        $response->assertStatus(200);
    }

    /**
     * Test maintenance history.
     */
    public function test_maintenance_history(): void
    {
        $schedule = MaintenanceSchedule::factory()->create();
        
        // Create some history records
        MaintenanceHistory::factory()->count(3)->create([
            'maintenance_schedule_id' => $schedule->id,
        ]);

        $response = $this->getJson("/api/maintenance-schedules/{$schedule->id}/history");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'pagination',
                 ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        foreach ($data as $history) {
            $this->assertEquals($schedule->id, $history['maintenance_schedule_id']);
        }
    }

    /**
     * Test maintenance schedule model relationships.
     */
    public function test_maintenance_schedule_model_relationships(): void
    {
        $schedule = MaintenanceSchedule::factory()->create();
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $schedule->asset);
        
        // Test assigned technician relationship
        $this->assertInstanceOf(User::class, $schedule->assignedTechnician);
        
        // Test creator relationship
        $this->assertInstanceOf(User::class, $schedule->creator);
        
        // Test work orders relationship
        $this->assertEmpty($schedule->workOrders);
        
        // Test maintenance history relationship
        $this->assertEmpty($schedule->maintenanceHistory);
    }

    /**
     * Test maintenance schedule scopes.
     */
    public function test_maintenance_schedule_scopes(): void
    {
        // Create active schedule
        MaintenanceSchedule::factory()->create(['is_active' => true]);
        
        // Test active scope
        $activeSchedules = MaintenanceSchedule::active()->get();
        $this->assertNotEmpty($activeSchedules);
        foreach ($activeSchedules as $schedule) {
            $this->assertTrue($schedule->is_active);
        }

        // Create overdue schedule
        MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->subDays(1),
            'is_active' => true,
        ]);

        // Test overdue scope
        $overdueSchedules = MaintenanceSchedule::overdue()->get();
        $this->assertNotEmpty($overdueSchedules);
        foreach ($overdueSchedules as $schedule) {
            $this->assertTrue($schedule->isOverdue());
        }

        // Create due soon schedule
        MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->addDays(15),
            'is_active' => true,
        ]);

        // Test due soon scope
        $dueSoonSchedules = MaintenanceSchedule::dueSoon(30)->get();
        $this->assertNotEmpty($dueSoonSchedules);
        foreach ($dueSoonSchedules as $schedule) {
            $this->assertTrue($schedule->isDueSoon(30));
        }
    }

    /**
     * Test maintenance scheduling service.
     */
    public function test_maintenance_scheduling_service(): void
    {
        $asset = Asset::factory()->create();
        
        $results = $this->schedulingService->generateSchedulesForAsset($asset->id);
        
        $this->assertArrayHasKey('schedules_created', $results);
        $this->assertArrayHasKey('schedules_updated', $results);
        $this->assertArrayHasKey('errors', $results);
        
        if ($results['schedules_created'] > 0) {
            $this->assertDatabaseHas('maintenance_schedules', ['asset_id' => $asset->id]);
        }
    }

    /**
     * Test compliance reporting.
     */
    public function test_compliance_reporting(): void
    {
        // Create some maintenance history
        MaintenanceHistory::factory()->count(5)->create([
            'completed_on_time' => true,
        ]);
        
        MaintenanceHistory::factory()->count(2)->create([
            'completed_on_time' => false,
        ]);

        $report = $this->schedulingService->getComplianceReport();
        
        $this->assertArrayHasKey('total_maintenance', $report);
        $this->assertArrayHasKey('completed_on_time', $report);
        $this->assertArrayHasKey('compliance_rate', $report);
        $this->assertEquals(7, $report['total_maintenance']);
        $this->assertEquals(5, $report['completed_on_time']);
        $this->assertEquals(71.4, round($report['compliance_rate'], 1));
    }

    /**
     * Test upcoming maintenance summary.
     */
    public function test_upcoming_maintenance_summary(): void
    {
        // Create upcoming schedules
        MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->addDays(5),
            'is_active' => true,
        ]);
        
        MaintenanceSchedule::factory()->create([
            'next_due_date' => now()->subDays(1),
            'is_active' => true,
        ]);

        $summary = $this->schedulingService->getUpcomingMaintenanceSummary(30);
        
        $this->assertArrayHasKey('total_upcoming', $summary);
        $this->assertArrayHasKey('overdue_count', $summary);
        $this->assertArrayHasKey('due_today_count', $summary);
        $this->assertArrayHasKey('by_priority', $summary);
        $this->assertArrayHasKey('by_maintenance_type', $summary);
        
        $this->assertGreaterThan(0, $summary['total_upcoming']);
        $this->assertGreaterThan(0, $summary['overdue_count']);
    }

    /**
     * Test maintenance schedule without authentication.
     */
    public function test_maintenance_schedule_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/maintenance-schedules');
        $response->assertStatus(401);

        $response = $this->postJson('/api/maintenance-schedules');
        $response->assertStatus(401);
    }

    /**
     * Test maintenance schedule with insufficient permissions.
     */
    public function test_maintenance_schedule_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read schedules
        $response = $this->getJson('/api/maintenance-schedules');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/maintenance-schedules/statistics');
        $response->assertStatus(200);
        
        // But not be able to create schedules
        $response = $this->postJson('/api/maintenance-schedules', [
            'asset_id' => Asset::first()->id,
            'title' => 'Test',
            'description' => 'Test',
            'maintenance_type' => 'preventive',
            'frequency_type' => 'monthly',
            'next_due_date' => now()->addMonth()->format('Y-m-d'),
        ]);
        $response->assertStatus(403);
    }
}
