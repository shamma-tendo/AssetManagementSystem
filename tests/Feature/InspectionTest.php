<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\ChecklistTemplate;
use App\Models\InspectionAttachment;
use App\Models\InspectionComment;
use App\Models\InspectionHistory;
use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\InspectionType;
use App\Models\InspectionStatus;
use App\Models\InspectionPriority;
use App\Services\InspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class InspectionTest extends TestCase
{
    use RefreshDatabase;

    protected InspectionService $inspectionService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inspectionService = app(InspectionService);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $inspector = User::factory()->create(['role' => UserRole::TECHNICIAN]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for inspection management.
     */
    private function createTestData(): void
    {
        // Create assets
        Asset::factory()->count(5)->create(['status' => 'active']);
        
        // Create checklist templates
        ChecklistTemplate::factory()->count(3)->create();
        
        // Create some inspections
        Inspection::factory()->count(10)->create();
    }

    /**
     * Test inspection listing.
     */
    public function test_inspection_listing(): void
    {
        $response = $this->getJson('/api/inspections');

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
     * Test inspection creation.
     */
    public function test_inspection_creation(): void
    {
        $asset = Asset::first();
        $inspector = User::where('role', UserRole::TECHNICIAN)->first();
        $template = ChecklistTemplate::first();

        $inspectionData = [
            'asset_id' => $asset->id,
            'title' => 'Routine Safety Inspection',
            'description' => 'Monthly safety inspection for the asset',
            'inspection_type' => 'safety',
            'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
            'inspector_id' => $inspector->id,
            'priority' => 'normal',
            'checklist_template_id' => $template->id,
            'passing_score' => 80,
            'estimated_duration_minutes' => 60,
            'notes' => 'Standard monthly safety check',
        ];

        $response = $this->postJson('/api/inspections', $inspectionData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Inspection created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'inspection_type',
                         'status',
                         'priority',
                         'asset_id',
                         'inspector_id',
                         'scheduled_date',
                         'checklist_template_id',
                         'passing_score',
                     ],
                 ]);

        $this->assertDatabaseHas('inspections', [
            'title' => 'Routine Safety Inspection',
            'inspection_type' => 'safety',
            'asset_id' => $asset->id,
            'inspector_id' => $inspector->id,
            'priority' => 'normal',
        ]);
    }

    /**
     * Test inspection creation validation.
     */
    public function test_inspection_creation_validation(): void
    {
        $response = $this->postJson('/api/inspections', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'asset_id',
                     'title',
                     'description',
                     'inspection_type',
                     'scheduled_date',
                     'priority',
                 ]);
    }

    /**
     * Test inspection show.
     */
    public function test_inspection_show(): void
    {
        $inspection = Inspection::first();

        $response = $this->getJson("/api/inspections/{$inspection->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'title',
                         'description',
                         'inspection_type',
                         'status',
                         'priority',
                         'asset',
                         'inspector',
                         'supervisor',
                         'checklistTemplate',
                         'attachments',
                         'comments',
                         'history',
                         'workOrders',
                     ],
                 ]);
    }

    /**
     * Test inspection update.
     */
    public function test_inspection_update(): void
    {
        $inspection = Inspection::first();
        $inspector = User::where('role', UserRole::TECHNICIAN)->first();

        $updateData = [
            'title' => 'Updated Inspection',
            'priority' => 'high',
            'inspector_id' => $inspector->id,
            'estimated_duration_minutes' => 90,
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/inspections/{$inspection->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Inspection updated successfully',
                 ]);

        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'title' => 'Updated Inspection',
            'priority' => 'high',
            'inspector_id' => $inspector->id,
        ]);
    }

    /**
     * Test inspection deletion.
     */
    public function test_inspection_deletion(): void
    {
        $inspection = Inspection::factory()->create(['status' => 'scheduled']);

        $response = $this->deleteJson("/api/inspections/{$inspection->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Inspection deleted successfully',
                 ]);

        $this->assertSoftDeleted('inspections', ['id' => $inspection->id]);
    }

    /**
     * Test inspection deletion restrictions.
     */
    public function test_inspection_deletion_restrictions(): void
    {
        $inspection = Inspection::factory()->create(['status' => 'in_progress']);

        $response = $this->deleteJson("/api/inspections/{$inspection->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete inspection in In Progress status',
                 ]);
    }

    /**
     * Test inspection statistics.
     */
    public function test_inspection_statistics(): void
    {
        $response = $this->getJson('/api/inspections/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'total_inspections',
                         'by_status',
                         'by_type',
                         'by_priority',
                         'overdue_count',
                         'due_today_count',
                         'due_this_week_count',
                         'requires_follow_up_count',
                         'with_deficiencies_count',
                         'average_score',
                         'pass_rate',
                         'completion_rate',
                         'recent_inspections',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertGreaterThan(0, $stats['total_inspections']);
        $this->assertArrayHasKey('scheduled', $stats['by_status']);
        $this->assertArrayHasKey('routine', $stats['by_type']);
        $this->assertArrayHasKey('normal', $stats['by_priority']);
    }

    /**
     * Test starting an inspection.
     */
    public function test_start_inspection(): void
    {
        $inspection = Inspection::factory()->create(['status' => 'scheduled']);

        $response = $this->postJson("/api/inspections/{$inspection->id}/start");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Inspection started successfully',
                 ]);

        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Test starting inspection restrictions.
     */
    public function test_start_inspection_restrictions(): void
    {
        $inspection = Inspection::factory()->create(['status' => 'in_progress']);

        $response = $this->postJson("/api/inspections/{$inspection->id}/start");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Inspection cannot be started in current status',
                 ]);
    }

    /**
     * Test completing an inspection.
     */
    public function test_complete_inspection(): void
    {
        $inspection = Inspection::factory()->create([
            'status' => 'in_progress',
            'checklist_items' => [
                ['id' => 'item1', 'title' => 'Test Item', 'type' => 'checkbox', 'max_points' => 10]
            ],
            'passing_score' => 70,
        ]);

        $completionData = [
            'checklist_results' => [
                [
                    'item_id' => 'item1',
                    'completed' => true,
                    'result' => true,
                    'notes' => 'Item completed successfully',
                ]
            ],
            'duration_minutes' => 45,
            'findings' => [
                ['description' => 'Minor wear observed', 'severity' => 'low']
            ],
            'recommendations' => [
                ['description' => 'Monitor during next inspection']
            ],
            'deficiencies' => [],
            'corrective_actions_required' => false,
            'follow_up_required' => false,
            'notes' => 'Inspection completed successfully',
        ];

        $response = $this->postJson("/api/inspections/{$inspection->id}/complete", $completionData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Inspection completed successfully',
                 ]);

        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'status' => 'passed',
            'duration_minutes' => 45,
        ]);
    }

    /**
     * Test completing inspection with deficiencies.
     */
    public function test_complete_inspection_with_deficiencies(): void
    {
        $inspection = Inspection::factory()->create([
            'status' => 'in_progress',
            'checklist_items' => [
                ['id' => 'item1', 'title' => 'Test Item', 'type' => 'checkbox', 'max_points' => 10]
            ],
            'passing_score' => 70,
        ]);

        $completionData = [
            'checklist_results' => [
                [
                    'item_id' => 'item1',
                    'completed' => false,
                    'result' => false,
                    'notes' => 'Item failed inspection',
                ]
            ],
            'duration_minutes' => 30,
            'deficiencies' => [
                [
                    'description' => 'Critical safety issue found',
                    'severity' => 'critical',
                    'category' => 'safety'
                ]
            ],
            'corrective_actions_required' => true,
            'follow_up_required' => true,
            'follow_up_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->postJson("/api/inspections/{$inspection->id}/complete", $completionData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Inspection completed successfully',
                 ]);

        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'status' => 'failed',
            'corrective_actions_required' => true,
            'follow_up_required' => true,
        ]);

        // Check that work order was created
        $this->assertDatabaseHas('work_orders', [
            'inspection_id' => $inspection->id,
            'type' => 'corrective_maintenance',
        ]);
    }

    /**
     * Test adding comment to inspection.
     */
    public function test_add_inspection_comment(): void
    {
        $inspection = Inspection::first();

        $commentData = [
            'comment' => 'This is a test comment for the inspection',
            'comment_type' => 'general',
            'is_internal' => false,
            'is_private' => false,
        ];

        $response = $this->postJson("/api/inspections/{$inspection->id}/comments", $commentData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Comment added successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'comment',
                         'comment_type',
                         'is_internal',
                         'is_private',
                         'user',
                         'created_at',
                     ],
                 ]);

        $this->assertDatabaseHas('inspection_comments', [
            'inspection_id' => $inspection->id,
            'comment' => 'This is a test comment for the inspection',
            'comment_type' => 'general',
        ]);
    }

    /**
     * Test uploading attachment to inspection.
     */
    public function test_upload_inspection_attachment(): void
    {
        $inspection = Inspection::first();

        $file = \Illuminate\Http\UploadedFile::fake()->create('test-photo.jpg', 1024, 'image/jpeg');

        $attachmentData = [
            'file' => $file,
            'description' => 'Photo of the inspection area',
            'attachment_type' => 'photo',
            'is_public' => true,
        ];

        $response = $this->postJson("/api/inspections/{$inspection->id}/attachments", $attachmentData);

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
                         'attachment_type',
                         'uploader',
                     ],
                 ]);

        $this->assertDatabaseHas('inspection_attachments', [
            'inspection_id' => $inspection->id,
            'original_name' => 'test-photo.jpg',
            'mime_type' => 'image/jpeg',
            'attachment_type' => 'photo',
        ]);
    }

    /**
     * Test inspection calendar view.
     */
    public function test_inspection_calendar(): void
    {
        // Create inspections with specific dates
        Inspection::factory()->create([
            'scheduled_date' => now()->addDays(5),
            'title' => 'Upcoming Inspection 1',
        ]);
        
        Inspection::factory()->create([
            'scheduled_date' => now()->addDays(15),
            'title' => 'Upcoming Inspection 2',
        ]);

        $response = $this->getJson('/api/inspections/calendar');

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
     * Test inspection history.
     */
    public function test_inspection_history(): void
    {
        $inspection = Inspection::first();
        
        // Create some history records
        InspectionHistory::factory()->count(3)->create([
            'inspection_id' => $inspection->id,
        ]);

        $response = $this->getJson("/api/inspections/{$inspection->id}/history");

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
            $this->assertEquals($inspection->id, $history['inspection_id']);
        }
    }

    /**
     * Test inspection report generation.
     */
    public function test_inspection_report_generation(): void
    {
        $inspection = Inspection::factory()->create([
            'status' => 'completed',
            'overall_score' => 85,
            'max_score' => 100,
            'findings' => [
                ['description' => 'Minor issue found', 'severity' => 'low']
            ],
            'deficiencies' => [],
            'recommendations' => [
                ['description' => 'Monitor performance']
            ],
        ]);

        $response = $this->getJson("/api/inspections/{$inspection->id}/report");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'inspection',
                         'asset_details',
                         'checklist_results',
                         'findings_summary',
                         'attachments',
                         'comments',
                         'generated_at',
                     ],
                 ]);

        $report = $response->json('data');
        $this->assertEquals(85, $report['inspection']['overall_score']);
        $this->assertEquals(1, $report['findings_summary']['total_findings']);
        $this->assertEquals(0, $report['findings_summary']['total_deficiencies']);
    }

    /**
     * Test inspection filtering.
     */
    public function test_inspection_filtering(): void
    {
        $asset = Asset::first();
        $inspector = User::where('role', UserRole::TECHNICIAN)->first();

        // Test asset filter
        $response = $this->getJson("/api/inspections?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $inspection) {
            $this->assertEquals($asset->id, $inspection['asset_id']);
        }

        // Test inspection type filter
        $response = $this->getJson('/api/inspections?inspection_type=safety');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $inspection) {
            $this->assertEquals('safety', $inspection['inspection_type']);
        }

        // Test status filter
        $response = $this->getJson('/api/inspections?status=scheduled');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $inspection) {
            $this->assertEquals('scheduled', $inspection['status']);
        }

        // Test priority filter
        $response = $this->getJson('/api/inspections?priority=high');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $inspection) {
            $this->assertEquals('high', $inspection['priority']);
        }

        // Test inspector filter
        $response = $this->getJson("/api/inspections?inspector_id={$inspector->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $inspection) {
            $this->assertEquals($inspector->id, $inspection['inspector_id']);
        }
    }

    /**
     * Test inspection search.
     */
    public function test_inspection_search(): void
    {
        // Create an inspection with specific title
        Inspection::factory()->create(['title' => 'Special Safety Inspection']);

        $response = $this->getJson('/api/inspections?search=Special');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $found = false;
        foreach ($data as $inspection) {
            if (str_contains(strtolower($inspection['title']), 'special')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test inspection sorting.
     */
    public function test_inspection_sorting(): void
    {
        // Test sort by scheduled_date
        $response = $this->getJson('/api/inspections?sort_by=scheduled_date&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    $data[$i]['scheduled_date'],
                    $data[$i + 1]['scheduled_date']
                );
            }
        }

        // Test sort by title
        $response = $this->getJson('/api/inspections?sort_by=title&sort_order=asc');
        $response->assertStatus(200);
    }

    /**
     * Test inspection model relationships.
     */
    public function test_inspection_model_relationships(): void
    {
        $inspection = Inspection::factory()->create();
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $inspection->asset);
        
        // Test inspector relationship
        $this->assertInstanceOf(User::class, $inspection->inspector);
        
        // Test supervisor relationship
        $this->assertInstanceOf(User::class, $inspection->supervisor);
        
        // Test checklist template relationship
        $this->assertInstanceOf(ChecklistTemplate::class, $inspection->checklistTemplate);
        
        // Test attachments relationship
        $this->assertEmpty($inspection->attachments);
        
        // Test comments relationship
        $this->assertEmpty($inspection->comments);
        
        // Test history relationship
        $this->assertEmpty($inspection->history);
        
        // Test work orders relationship
        $this->assertEmpty($inspection->workOrders);
    }

    /**
     * Test inspection model methods.
     */
    public function test_inspection_model_methods(): void
    {
        $inspection = Inspection::factory()->create([
            'overall_score' => 85,
            'max_score' => 100,
            'passing_score' => 70,
            'scheduled_date' => now()->addDays(5),
        ]);
        
        // Test isPassed
        $this->assertTrue($inspection->isPassed());
        
        // Test isFailed
        $this->assertFalse($inspection->isFailed());
        
        // Test isCompleted
        $this->assertFalse($inspection->isCompleted());
        
        // Test compliance_percentage
        $this->assertEquals(85, $inspection->compliance_percentage);
        
        // Test days_until_due
        $this->assertEquals(5, $inspection->days_until_due);
        
        // Test display name methods
        $this->assertIsString($inspection->priority_display_name);
        $this->assertIsString($inspection->status_display_name);
        $this->assertIsString($inspection->type_display_name);
        
        // Test color methods
        $this->assertIsString($inspection->priority_color);
        $this->assertIsString($inspection->status_color);
    }

    /**
     * Test inspection scopes.
     */
    public function test_inspection_scopes(): void
    {
        // Create overdue inspection
        Inspection::factory()->create([
            'scheduled_date' => now()->subDays(1),
            'status' => 'scheduled',
        ]);

        // Test overdue scope
        $overdueInspections = Inspection::overdue()->get();
        $this->assertNotEmpty($overdueInspections);
        foreach ($overdueInspections as $inspection) {
            $this->assertTrue($inspection->isOverdue());
        }

        // Create due today inspection
        Inspection::factory()->create([
            'scheduled_date' => today(),
            'status' => 'scheduled',
        ]);

        // Test due today scope
        $dueTodayInspections = Inspection::dueToday()->get();
        $this->assertNotEmpty($dueTodayInspections);
        foreach ($dueTodayInspections as $inspection) {
            $this->assertEquals(today()->format('Y-m-d'), $inspection->scheduled_date->format('Y-m-d'));
        }

        // Create inspection requiring follow-up
        Inspection::factory()->create([
            'follow_up_required' => true,
        ]);

        // Test requires follow-up scope
        $followUpInspections = Inspection::requiresFollowUp()->get();
        $this->assertNotEmpty($followUpInspections);
        foreach ($followUpInspections as $inspection) {
            $this->assertTrue($inspection->follow_up_required);
        }
    }

    /**
     * Test inspection service checklist processing.
     */
    public function test_inspection_service_checklist_processing(): void
    {
        $inspection = Inspection::factory()->create([
            'checklist_items' => [
                ['id' => 'item1', 'title' => 'Test Checkbox', 'type' => 'checkbox', 'max_points' => 10],
                ['id' => 'item2', 'title' => 'Test Rating', 'type' => 'rating', 'max_points' => 20, 'options' => [1, 2, 3, 4, 5]],
                ['id' => 'item3', 'title' => 'Test Text', 'type' => 'text', 'max_points' => 15, 'required' => true],
            ],
            'passing_score' => 70,
        ]);

        $checklistResults = [
            [
                'item_id' => 'item1',
                'completed' => true,
                'result' => true,
                'notes' => 'Checkbox completed',
            ],
            [
                'item_id' => 'item2',
                'completed' => true,
                'result' => 4,
                'notes' => 'Rating of 4 out of 5',
            ],
            [
                'item_id' => 'item3',
                'completed' => true,
                'result' => 'Text response provided',
                'notes' => 'Text item completed',
            ],
        ];

        $result = $this->inspectionService->processChecklistResults($inspection, $checklistResults);

        $this->assertEquals(45, $result['overall_score']); // 10 + 16 + 15 = 41 (4/5 * 20 = 16)
        $this->assertEquals(45, $result['max_score']); // 10 + 20 + 15 = 45
        $this->assertCount(3, $result['processed_results']);
    }

    /**
     * Test inspection service compliance metrics.
     */
    public function test_inspection_service_compliance_metrics(): void
    {
        $inspection = Inspection::factory()->create([
            'checklist_results' => [
                ['item_id' => 'item1', 'completed' => true, 'score' => 10, 'max_points' => 10],
                ['item_id' => 'item2', 'completed' => true, 'score' => 8, 'max_points' => 10],
                ['item_id' => 'item3', 'completed' => false, 'score' => 0, 'max_points' => 10],
            ],
            'overall_score' => 18,
            'max_score' => 30,
            'passing_score' => 21,
        ]);

        $metrics = $this->inspectionService->calculateComplianceMetrics($inspection);

        $this->assertEquals(66.67, round($metrics['completion_rate'], 2)); // 2/3 completed
        $this->assertEquals(60, $metrics['score_rate']); // 18/30 * 100
        $this->assertFalse($metrics['passed']); // 18 < 21 passing score
        $this->assertEquals('needs_improvement', $metrics['compliance_level']);
    }

    /**
     * Test checklist template model.
     */
    public function test_checklist_template_model(): void
    {
        $template = ChecklistTemplate::factory()->create([
            'checklist_items' => [
                ['id' => 'item1', 'title' => 'Test Item', 'max_points' => 10],
                ['id' => 'item2', 'title' => 'Another Item', 'max_points' => 15],
            ],
            'passing_score_percentage' => 75,
        ]);

        // Test max score calculation
        $this->assertEquals(25, $template->max_score);

        // Test passing score calculation
        $this->assertEquals(18.75, $template->passing_score); // 25 * 0.75

        // Test item count
        $this->assertEquals(2, $template->item_count);

        // Test display methods
        $this->assertIsString($template->inspection_type_display_name);
        $this->assertIsString($template->inspection_type_color);
    }

    /**
     * Test inspection without authentication.
     */
    public function test_inspection_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/inspections');
        $response->assertStatus(401);

        $response = $this->postJson('/api/inspections');
        $response->assertStatus(401);
    }

    /**
     * Test inspection with insufficient permissions.
     */
    public function test_inspection_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read inspections
        $response = $this->getJson('/api/inspections');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/inspections/statistics');
        $response->assertStatus(200);
        
        // But not be able to create inspections
        $response = $this->postJson('/api/inspections', [
            'asset_id' => Asset::first()->id,
            'title' => 'Test',
            'description' => 'Test',
            'inspection_type' => 'routine',
            'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'normal',
        ]);
        $response->assertStatus(403);
    }
}
