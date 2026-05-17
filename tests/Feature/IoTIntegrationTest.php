<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Sensor;
use App\Models\SensorType;
use App\Models\SensorReading;
use App\Models\SensorAlert;
use App\Models\SensorCalibration;
use App\Models\SensorAlertTemplate;
use App\Models\User;
use App\Models\UserRole;
use App\Services\IoTService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class IoTIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected IoTService $iotService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->iotService = app(IoTService);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for IoT integration.
     */
    private function createTestData(): void
    {
        // Create sensor types
        SensorType::factory()->count(5)->create();
        
        // Create assets
        Asset::factory()->count(10)->create();
        
        // Create sensors
        Sensor::factory()->count(15)->create();
        
        // Create sensor readings
        SensorReading::factory()->count(100)->create();
        
        // Create sensor alerts
        SensorAlert::factory()->count(20)->create();
        
        // Create calibrations
        SensorCalibration::factory()->count(10)->create();
    }

    /**
     * Test sensors listing.
     */
    public function test_sensors_listing(): void
    {
        $response = $this->getJson('/api/iot/sensors');

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
     * Test sensor creation.
     */
    public function test_sensor_creation(): void
    {
        $asset = Asset::factory()->create();
        $sensorType = SensorType::factory()->create();

        $sensorData = [
            'asset_id' => $asset->id,
            'sensor_type_id' => $sensorType->id,
            'name' => 'Test Temperature Sensor',
            'description' => 'Temperature sensor for testing',
            'manufacturer' => 'Test Manufacturer',
            'model' => 'TS-1000',
            'serial_number' => 'TS123456789',
            'firmware_version' => '1.0.0',
            'hardware_version' => '2.0',
            'mac_address' => 'AA:BB:CC:DD:EE:FF:00',
            'ip_address' => '192.168.1.100',
            'location_description' => 'Server Room Rack A1',
            'installation_date' => now()->format('Y-m-d'),
            'calibration_date' => now()->format('Y-m-d'),
            'next_calibration_date' => now()->addMonths(6)->format('Y-m-d'),
            'threshold_min' => 15.0,
            'threshold_max' => 35.0,
            'alert_enabled' => true,
            'data_retention_days' => 90,
            'sampling_interval' => 300,
            'notes' => 'Test sensor notes',
        ];

        $response = $this->postJson('/api/iot/sensors', $sensorData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sensor created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'asset',
                         'sensorType',
                         'manufacturer',
                         'model',
                         'serial_number',
                         'status',
                     ],
                 ]);

        $this->assertDatabaseHas('sensors', [
            'name' => 'Test Temperature Sensor',
            'asset_id' => $asset->id,
            'sensor_type_id' => $sensorType->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF:00',
            'threshold_min' => 15.0,
            'threshold_max' => 35.0,
        ]);
    }

    /**
     * Test sensor creation validation.
     */
    public function test_sensor_creation_validation(): void
    {
        $response = $this->postJson('/api/iot/sensors', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'asset_id',
                     'sensor_type_id',
                     'name',
                 ]);
    }

    /**
     * Test sensor show.
     */
    public function test_sensor_show(): void
    {
        $sensor = Sensor::first();

        $response = $this->getJson("/api/iot/sensors/{$sensor->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'asset',
                         'sensorType',
                         'readings',
                         'alerts',
                         'calibrationRecords',
                         'creator',
                         'updater',
                     ],
                 ]);
    }

    /**
     * Test sensor update.
     */
    public function test_sensor_update(): void
    {
        $sensor = Sensor::factory()->create();

        $updateData = [
            'name' => 'Updated Sensor Name',
            'description' => 'Updated description',
            'firmware_version' => '2.0.0',
            'threshold_min' => 10.0,
            'threshold_max' => 40.0,
            'status' => 'maintenance',
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/iot/sensors/{$sensor->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sensor updated successfully',
                 ]);

        $this->assertDatabaseHas('sensors', [
            'id' => $sensor->id,
            'name' => 'Updated Sensor Name',
            'firmware_version' => '2.0.0',
            'threshold_min' => 10.0,
            'threshold_max' => 40.0,
            'status' => 'maintenance',
        ]);
    }

    /**
     * Test sensor deletion.
     */
    public function test_sensor_deletion(): void
    {
        $sensor = Sensor::factory()->create();

        $response = $this->deleteJson("/api/iot/sensors/{$sensor->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sensor deleted successfully',
                 ]);

        $this->assertSoftDeleted('sensors', ['id' => $sensor->id]);
    }

    /**
     * Test sensor deletion with readings.
     */
    public function test_sensor_deletion_with_readings(): void
    {
        $sensor = Sensor::factory()->create();
        SensorReading::factory()->create(['sensor_id' => $sensor->id]);

        $response = $this->deleteJson("/api/iot/sensors/{$sensor->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete sensor with readings',
                 ]);
    }

    /**
     * Test sensor readings listing.
     */
    public function test_sensor_readings_listing(): void
    {
        $response = $this->getJson('/api/iot/readings');

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
     * Test sensor reading creation.
     */
    public function test_sensor_reading_creation(): void
    {
        $sensor = Sensor::factory()->create();

        $readingData = [
            'sensor_id' => $sensor->id,
            'value' => 25.5,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'quality' => 0.95,
            'battery_level' => 85,
            'signal_strength' => 92,
            'temperature' => 22.5,
            'humidity' => 45.2,
        ];

        $response = $this->postJson('/api/iot/readings', $readingData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Reading created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'sensor',
                         'value',
                         'timestamp',
                         'quality',
                         'battery_level',
                         'signal_strength',
                     ],
                 ]);

        $this->assertDatabaseHas('sensor_readings', [
            'sensor_id' => $sensor->id,
            'value' => 25.5,
            'quality' => 0.95,
            'battery_level' => 85,
            'signal_strength' => 92,
        ]);
    }

    /**
     * Test sensor reading creation validation.
     */
    public function test_sensor_reading_creation_validation(): void
    {
        $response = $this->postJson('/api/iot/readings', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'sensor_id',
                     'value',
                 ]);
    }

    /**
     * Test sensor reading creation with value validation.
     */
    public function test_sensor_reading_creation_value_validation(): void
    {
        $sensor = Sensor::factory()->create();
        
        // Create a sensor type with limited range
        $sensorType = SensorType::factory()->create([
            'min_value' => 0,
            'max_value' => 100,
        ]);
        $sensor->sensor_type_id = $sensorType->id;
        $sensor->save();

        // Try to create reading outside the range
        $response = $this->postJson('/api/iot/readings', [
            'sensor_id' => $sensor->id,
            'value' => 150, // Outside the 0-100 range
        ]);

        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Failed to create reading',
                 ]);
    }

    /**
     * Test sensor alerts listing.
     */
    public function test_sensor_alerts_listing(): void
    {
        $response = $this->getJson('/api/iot/alerts');

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
     * Test sensor alert acknowledgment.
     */
    public function test_sensor_alert_acknowledgment(): void
    {
        $alert = SensorAlert::factory()->create([
            'acknowledged_at' => null,
            'acknowledged_by' => null,
        ]);

        $response = $this->postJson("/api/iot/alerts/{$alert->id}/acknowledge", [
            'notes' => 'Acknowledged for review',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Alert acknowledged successfully',
                 ]);

        $this->assertDatabaseHas('sensor_alerts', [
            'id' => $alert->id,
            'acknowledged_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Test sensor alert resolution.
     */
    public function test_sensor_alert_resolution(): void
    {
        $alert = SensorAlert::factory()->create([
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        $response = $this->postJson("/api/iot/alerts/{$alert->id}/resolve", [
            'resolution_notes' => 'Issue resolved by adjusting sensor configuration',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Alert resolved successfully',
                 ]);

        $this->assertDatabaseHas('sensor_alerts', [
            'id' => $alert->id,
            'resolved_at' => now()->format('Y-m-d H:i:s'),
            'resolution_notes' => 'Issue resolved by adjusting sensor configuration',
        ]);
    }

    /**
     * Test sensor types listing.
     */
    public function test_sensor_types_listing(): void
    {
        $response = $this->getJson('/api/iot/sensor-types');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'data_type',
                             'category',
                             'unit_of_measure',
                         ],
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /**
     * Test sensor calibrations listing.
     */
    public function test_sensor_calibrations_listing(): void
    {
        $response = $this->getJson('/api/iot/calibrations');

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
     * Test sensor calibration creation.
     */
    public function test_sensor_calibration_creation(): void
    {
        $sensor = Sensor::factory()->create();

        $calibrationData = [
            'sensor_id' => $sensor->id,
            'calibration_type' => 'routine',
            'reference_value' => 25.0,
            'measured_value' => 24.8,
            'correction_factor' => 0.992,
            'offset' => -0.2,
            'linearity_error' => 0.001,
            'equipment_used' => 'Calibration Kit CK-1000',
            'notes' => 'Routine calibration check',
            'next_calibration_date' => now()->addMonths(6)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/iot/calibrations', $calibrationData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Calibration created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'sensor',
                         'calibration_type',
                         'reference_value',
                         'measured_value',
                         'correction_factor',
                         'performer',
                     ],
                 ]);

        $this->assertDatabaseHas('sensor_calibrations', [
            'sensor_id' => $sensor->id,
            'calibration_type' => 'routine',
            'reference_value' => 25.0,
            'measured_value' => 24.8,
            'correction_factor' => 0.992,
        ]);
    }

    /**
     * Test calibration approval.
     */
    public function test_calibration_approval(): void
    {
        $calibration = SensorCalibration::factory()->create([
            'calibration_status' => 'pending',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $response = $this->postJson("/api/iot/calibrations/{$calibration->id}/approve", [
            'notes' => 'Calibration approved - within tolerance',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Calibration approved successfully',
                 ]);

        $this->assertDatabaseHas('sensor_calibrations', [
            'id' => $calibration->id,
            'calibration_status' => 'approved',
            'approved_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Test calibration rejection.
     */
    public function test_calibration_rejection(): void
    {
        $calibration = SensorCalibration::factory()->create([
            'calibration_status' => 'pending',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $response = $this->postJson("/api/iot/calibrations/{$calibration->id}/reject", [
            'reason' => 'Calibration error exceeds tolerance',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Calibration rejected successfully',
                 ]);

        $this->assertDatabaseHas('sensor_calibrations', [
            'id' => $calibration->id,
            'calibration_status' => 'failed',
        ]);
    }

    /**
     * Test IoT statistics.
     */
    public function test_iot_statistics(): void
    {
        $response = $this->getJson('/api/iot/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'sensors',
                         'readings',
                         'alerts',
                         'calibrations',
                         'sensor_types',
                         'recent_activity',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertArrayHasKey('total', $stats['sensors']);
        $this->assertArrayHasKey('active', $stats['sensors']);
        $this->assertArrayHasKey('total', $stats['readings']);
        $this->assertArrayHasKey('total', $stats['alerts']);
    }

    /**
     * Test sensor analytics.
     */
    public function test_sensor_analytics(): void
    {
        $sensor = Sensor::factory()->create();
        
        // Create some readings for the sensor
        SensorReading::factory()->count(50)->create([
            'sensor_id' => $sensor->id,
            'timestamp' => now()->subDays(rand(1, 30)),
        ]);

        $response = $this->getJson("/api/iot/analytics?sensor_id={$sensor->id}&period=week");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'sensor_id',
                         'period',
                         'total_readings',
                         'statistics',
                         'trends',
                         'anomalies',
                         'quality_metrics',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals($sensor->id, $data['sensor_id']);
        $this->assertEquals('week', $data['period']);
        $this->assertArrayHasKey('statistics', $data);
    }

    /**
     * Test health report.
     */
    public function test_health_report(): void
    {
        $response = $this->getJson('/api/iot/health-report');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'summary',
                         'by_status',
                         'by_type',
                         'by_asset',
                         'issues',
                         'recommendations',
                         'generated_at',
                     ],
                 ]);

        $report = $response->json('data');
        $this->assertArrayHasKey('total_sensors', $report['summary']);
        $this->assertArrayHasKey('healthy_sensors', $report['summary']);
        $this->assertArrayHasKey('overall_health_score', $report['summary']);
    }

    /**
     * Test batch sensor data processing.
     */
    public function test_batch_sensor_data_processing(): void
    {
        $sensor = Sensor::factory()->create();

        $batchData = [
            'sensor_id' => $sensor->id,
            'readings' => [
                [
                    'value' => 25.5,
                    'timestamp' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
                    'quality' => 0.95,
                    'battery_level' => 85,
                    'signal_strength' => 92,
                ],
                [
                    'value' => 26.0,
                    'timestamp' => now()->subMinutes(4)->format('Y-m-d H:i:s'),
                    'quality' => 0.98,
                    'battery_level' => 84,
                    'signal_strength' => 93,
                ],
                [
                    'value' => 24.8,
                    'timestamp' => now()->subMinutes(3)->format('Y-m-d H:i:s'),
                    'quality' => 0.92,
                    'battery_level' => 83,
                    'signal_strength' => 91,
                ],
            ],
            'metadata' => [
                'batch_id' => 'BATCH-001',
                'source' => 'IoT Gateway',
            ],
        ];

        $response = $this->postJson('/api/iot/process-data', $batchData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sensor data processed successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'processed',
                         'failed',
                         'alerts_created',
                         'errors',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['processed']);
        $this->assertEquals(0, $data['failed']);
        $this->assertIsArray($data['errors']);
    }

    /**
     * Test sensor filtering.
     */
    public function test_sensor_filtering(): void
    {
        $asset = Asset::factory()->create();
        $sensorType = SensorType::factory()->create();

        // Test asset filter
        $response = $this->getJson("/api/iot/sensors?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $sensor) {
            $this->assertEquals($asset->id, $sensor['asset_id']);
        }

        // Test sensor type filter
        $response = $this->getJson("/api/iot/sensors?sensor_type_id={$sensorType->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $sensor) {
            $this->assertEquals($sensorType->id, $sensor['sensor_type_id']);
        }

        // Test status filter
        $response = $this->getJson('/api/iot/sensors?status=active');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $sensor) {
            $this->assertEquals('active', $sensor['status']);
        }

        // Test health status filter
        $response = $this->getJson('/api/iot/sensors?needs_calibration=1');
        $response->assertStatus(200);
    }

    /**
     * Test sensor search.
     */
    public function test_sensor_search(): void
    {
        // Create a sensor with specific name
        Sensor::factory()->create(['name' => 'Special IoT Sensor']);

        $response = $this->getJson('/api/iot/sensors?search=Special');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $found = false;
        foreach ($data as $sensor) {
            if (str_contains(strtolower($sensor['name']), 'special')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test sensor sorting.
     */
    public function test_sensor_sorting(): void
    {
        // Test sort by name
        $response = $this->getJson('/api/iot/sensors?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) > 1) {
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    strcmp($data[$i]['name'], $data[$i + 1]['name']),
                    0,
                    "Sensors should be sorted by name in ascending order"
                );
            }
        }

        // Test sort by status
        $response = $this->getJson('/api/iot/sensors?sort_by=status&sort_order=desc');
        $response->assertStatus(200);
    }

    /**
     * Test sensor readings filtering.
     */
    public function test_sensor_readings_filtering(): void
    {
        $sensor = Sensor::factory()->create();

        // Test sensor filter
        $response = $this->getJson("/api/iot/readings?sensor_id={$sensor->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $reading) {
            $this->assertEquals($sensor->id, $reading['sensor_id']);
        }

        // Test time filter
        $response = $this->getJson('/api/iot/readings?hours=24');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $reading) {
            $this->assertGreaterThanOrEqual(
                now()->subHours(24)->timestamp,
                Carbon::parse($reading['timestamp'])->timestamp
            );
        }

        // Test quality filter
        $response = $this->getJson('/api/iot/readings?quality=good');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $reading) {
            $this->assertGreaterThanOrEqual($reading['quality'], 0.8);
        }
    }

    /**
     * Test sensor alerts filtering.
     */
    public function test_sensor_alerts_filtering(): void
    {
        $sensor = Sensor::factory()->create();

        // Test sensor filter
        $response = $this->getJson("/api/iot/alerts?sensor_id={$sensor->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $alert) {
            $this->assertEquals($sensor->id, $alert['sensor_id']);
        }

        // Test alert type filter
        $response = $this->getJson('/api/iot/alerts?alert_type=threshold_high');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $alert) {
            $this->assertEquals('threshold_high', $alert['alert_type']);
        }

        // Test status filters
        $response = $this->getJson('/api/iot/alerts?unacknowledged=1');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $alert) {
            $this->assertNull($alert['acknowledged_at']);
        }
    }

    /**
     * Test sensor model relationships.
     */
    public function test_sensor_model_relationships(): void
    {
        $sensor = Sensor::factory()->create();
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $sensor->asset);
        
        // Test sensor type relationship
        $this->assertInstanceOf(SensorType::class, $sensor->sensorType);
        
        // Test readings relationship
        $this->assertEmpty($sensor->readings);
        
        // Test alerts relationship
        $this->assertEmpty($sensor->alerts);
        
        // Test calibration records relationship
        $this->assertEmpty($sensor->calibrationRecords);
    }

    /**
     * Test sensor model methods.
     */
    public function test_sensor_model_methods(): void
    {
        $sensor = Sensor::factory()->create([
            'status' => 'active',
            'battery_level' => 85,
            'signal_strength' => 92,
            'last_heartbeat' => now()->subMinutes(5),
            'threshold_min' => 15.0,
            'threshold_max' => 35.0,
        ]);
        
        // Test status methods
        $this->assertTrue($sensor->isActive());
        $this->assertFalse($sensor->isOffline());
        $this->assertFalse($sensor->hasLowBattery());
        $this->assertFalse($sensor->hasPoorSignal());
        
        // Test threshold checking
        $this->assertEquals('normal', $sensor->exceedsThresholds(25.0));
        $this->assertEquals('below_min', $sensor->exceedsThresholds(10.0));
        $this->assertEquals('above_max', $sensor->exceedsThresholds(40.0));
        
        // Test display methods
        $this->assertIsString($sensor->status_display_name);
        $this->assertIsString($sensor->status_color);
        $this->assertIsString($sensor->health_status_display);
        $this->assertIsString($sensor->health_status_color);
    }

    /**
     * Test sensor reading model.
     */
    public function test_sensor_reading_model(): void
    {
        $sensor = Sensor::factory()->create();
        
        $reading = SensorReading::factory()->create([
            'sensor_id' => $sensor->id,
            'value' => 25.5,
            'quality' => 0.95,
            'battery_level' => 85,
            'signal_strength' => 92,
        ]);
        
        // Test relationships
        $this->assertInstanceOf(Sensor::class, $reading->sensor);
        
        // Test properties
        $this->assertEquals('25.50', $reading->formatted_value);
        $this->assertEquals('good', $reading->quality_status);
        $this->assertFalse($reading->hasErrors());
        $this->assertTrue($reading->hasGoodQuality());
        $this->assertIsArray($reading->summary);
    }

    /**
     * Test sensor alert model.
     */
    public function test_sensor_alert_model(): void
    {
        $sensor = Sensor::factory()->create();
        
        $alert = SensorAlert::factory()->create([
            'sensor_id' => $sensor->id,
            'alert_type' => 'threshold_high',
            'severity' => 'medium',
            'message' => 'Test alert message',
            'trigger_value' => 40.0,
            'threshold_value' => 35.0,
        ]);
        
        // Test relationships
        $this->assertInstanceOf(Sensor::class, $alert->sensor);
        
        // Test properties
        $this->assertFalse($alert->isAcknowledged());
        $this->assertFalse($alert->isResolved());
        $this->assertTrue($alert->isActive());
        $this->assertEquals('active', $alert->alert_status);
        $this->assertIsString($alert->alert_type_display_name);
        $this->assertIsString($alert->severity_display_name);
        $this->assertIsArray($alert->summary);
    }

    /**
     * Test IoT service analytics generation.
     */
    public function test_iot_service_analytics_generation(): void
    {
        $sensor = Sensor::factory()->create();
        
        // Create readings for the sensor
        SensorReading::factory()->count(50)->create([
            'sensor_id' => $sensor->id,
            'timestamp' => now()->subDays(rand(1, 30)),
        ]);
        
        $analytics = $this->iotService->generateSensorAnalytics($sensor, 'week');
        
        $this->assertEquals($sensor->id, $analytics['sensor_id']);
        $this->assertEquals('week', $analytics['period']);
        $this->assertEquals(50, $analytics['total_readings']);
        $this->assertArrayHasKey('statistics', $analytics);
        $this->assertArrayHasKey('trends', $analytics);
        $this->assertArrayHasKey('anomalies', $analytics);
        $this->assertArrayHasKey('quality_metrics', $analytics);
    }

    /**
     * Test IoT service health report.
     */
    public function test_iot_service_health_report(): void
    {
        // Create sensors with different health statuses
        Sensor::factory()->create(['status' => 'active', 'battery_level' => 90, 'last_heartbeat' => now()]);
        Sensor::factory()->create(['status' => 'active', 'battery_level' => 15, 'last_heartbeat' => now()]);
        Sensor::factory()->create(['status' => 'offline', 'last_heartbeat' => now()->subHours(2)]);
        
        $report = $this->iotService->generateHealthReport();
        
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('by_type', $report);
        $this->assertArrayHasKey('by_asset', $report);
        $this->assertArrayHasKey('issues', $report);
        $this->assertArrayHasKey('recommendations', $report);
        
        $this->assertEquals(3, $report['summary']['total_sensors']);
        $this->assertArrayHasKey('healthy_sensors', $report['summary']);
        $this->assertArrayHasKey('sensors_with_issues', $report['summary']);
    }

    /**
     * Test IoT service batch processing.
     */
    public function test_iot_service_batch_processing(): void
    {
        $sensor = Sensor::factory()->create();
        
        $readings = [
            [
                'value' => 25.5,
                'timestamp' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
                'quality' => 0.95,
            ],
            [
                'value' => 26.0,
                'timestamp' => now()->subMinutes(4)->format('Y-m-d H:i:s'),
                'quality' => 0.98,
            ],
        ];
        
        $results = $this->iotService->processBatchReadings($sensor->id, $readings);
        
        $this->assertEquals(2, $results['processed']);
        $this->assertEquals(0, $results['failed']);
        $this->assertIsArray($results['errors']);
    }

    /**
     * Test IoT without authentication.
     */
    public function test_iot_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/iot/sensors');
        $response->assertStatus(401);

        $response = $this->postJson('/api/iot/sensors');
        $response->assertStatus(401);
    }

    /**
     * Test IoT with insufficient permissions.
     */
    public function test_iot_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read IoT data
        $response = $this->getJson('/api/iot/sensors');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/iot/statistics');
        $response->assertStatus(200);
        
        // But not be able to create sensors
        $response = $this->postJson('/api/iot/sensors', [
            'asset_id' => Asset::factory()->create()->id,
            'sensor_type_id' => SensorType::factory()->create()->id,
            'name' => 'Test Sensor',
        ]);
        $response->assertStatus(403);
    }
}
