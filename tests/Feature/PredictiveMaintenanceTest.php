<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\PredictiveModel;
use App\Models\Prediction;
use App\Models\MaintenanceRecommendation;
use App\Models\ModelTrainingHistory;
use App\Models\ModelPerformance;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PredictiveMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Tests\TestCase;

class PredictiveMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected PredictiveMaintenanceService $predictiveService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->predictiveService = app(PredictiveMaintenanceService);
        
        // Create test users
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($manager);
        
        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for predictive maintenance.
     */
    private function createTestData(): void
    {
        // Create assets
        Asset::factory()->count(10)->create();
        
        // Create predictive models
        PredictiveModel::factory()->count(5)->create();
        
        // Create predictions
        Prediction::factory()->count(20)->create();
        
        // Create maintenance recommendations
        MaintenanceRecommendation::factory()->count(15)->create();
        
        // Create training histories
        ModelTrainingHistory::factory()->count(10)->create();
        
        // Create model performances
        ModelPerformance::factory()->count(8)->create();
    }

    /**
     * Test predictive models listing.
     */
    public function test_predictive_models_listing(): void
    {
        $response = $this->getJson('/api/predictive/models');

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
     * Test predictive model creation.
     */
    public function test_predictive_model_creation(): void
    {
        $modelData = [
            'name' => 'Failure Prediction Model',
            'description' => 'Predicts equipment failure probability',
            'model_type' => 'failure_prediction',
            'algorithm' => 'random_forest',
            'target_variable' => 'failure_probability',
            'input_features' => ['age', 'usage_hours', 'temperature', 'vibration'],
            'hyperparameters' => [
                'n_estimators' => 100,
                'max_depth' => 10,
                'random_state' => 42,
            ],
            'auto_retrain' => true,
            'retrain_frequency_days' => 30,
            'min_accuracy_threshold' => 0.8,
        ];

        $response = $this->postJson('/api/predictive/models', $modelData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Predictive model created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'model_type',
                         'algorithm',
                         'target_variable',
                         'input_features',
                         'is_active',
                         'model_version',
                         'creator',
                     ],
                 ]);

        $this->assertDatabaseHas('predictive_models', [
            'name' => 'Failure Prediction Model',
            'model_type' => 'failure_prediction',
            'algorithm' => 'random_forest',
            'target_variable' => 'failure_probability',
            'auto_retrain' => true,
        ]);
    }

    /**
     * Test predictive model creation validation.
     */
    public function test_predictive_model_creation_validation(): void
    {
        $response = $this->postJson('/api/predictive/models', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'name',
                     'model_type',
                     'algorithm',
                     'target_variable',
                     'input_features',
                ]);
    }

    /**
     * Test predictive model show.
     */
    public function test_predictive_model_show(): void
    {
        $model = PredictiveModel::factory()->create();

        $response = $this->getJson("/api/predictive/models/{$model->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'model_type',
                         'algorithm',
                         'target_variable',
                         'input_features',
                         'hyperparameters',
                         'accuracy_score',
                         'training_samples',
                         'last_trained_at',
                         'creator',
                         'trainingHistories',
                         'performances',
                         'predictions',
                     ],
                 ]);
    }

    /**
     * Test predictive model update.
     */
    public function test_predictive_model_update(): void
    {
        $model = PredictiveModel::factory()->create();

        $updateData = [
            'name' => 'Updated Model Name',
            'description' => 'Updated description',
            'input_features' => ['age', 'usage_hours', 'temperature', 'vibration', 'pressure'],
            'auto_retrain' => false,
            'retrain_frequency_days' => 60,
            'min_accuracy_threshold' => 0.85,
        ];

        $response = $this->putJson("/api/predictive/models/{$model->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Predictive model updated successfully',
                 ]);

        $this->assertDatabaseHas('predictive_models', [
            'id' => $model->id,
            'name' => 'Updated Model Name',
            'auto_retrain' => false,
            'retrain_frequency_days' => 60,
            'min_accuracy_threshold' => 0.85,
        ]);
    }

    /**
     * Test predictive model deletion.
     */
    public function test_predictive_model_deletion(): void
    {
        $model = PredictiveModel::factory()->create();

        $response = $this->deleteJson("/api/predictive/models/{$model->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Predictive model deleted successfully',
                 ]);

        $this->assertSoftDeleted('predictive_models', ['id' => $model->id]);
    }

    /**
     * Test predictive model deletion with predictions.
     */
    public function test_predictive_model_deletion_with_predictions(): void
    {
        $model = PredictiveModel::factory()->create();
        Prediction::factory()->create(['predictive_model_id' => $model->id]);

        $response = $this->deleteJson("/api/predictive/models/{$model->id}");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete model with predictions',
                 ]);
    }

    /**
     * Test model training.
     */
    public function test_model_training(): void
    {
        $model = PredictiveModel::factory()->create();

        $trainingData = [];
        for ($i = 0; $i < 100; $i++) {
            $trainingData[] = [
                'features' => [
                    'age' => rand(1, 3650),
                    'usage_hours' => rand(100, 10000),
                    'temperature' => rand(15, 35) + mt_rand() / mt_getrandmax(),
                    'vibration' => rand(0, 100) + mt_rand() / mt_getrandmax(),
                ],
                'target' => mt_rand() / mt_getrandmax(),
            ];
        }

        $response = $this->postJson("/api/predictive/models/{$model->id}/train", [
            'training_data' => $trainingData,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Model training completed successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'success',
                         'training_history_id',
                         'model_performance',
                         'message',
                     ],
                 ]);

        $this->assertDatabaseHas('model_training_histories', [
            'predictive_model_id' => $model->id,
            'training_status' => 'completed',
        ]);

        $this->assertDatabaseHas('predictive_models', [
            'id' => $model->id,
            'last_trained_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Test model training validation.
     */
    public function test_model_training_validation(): void
    {
        $model = PredictiveModel::factory()->create();

        $response = $this->postJson("/api/predictive/models/{$model->id}/train", []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'training_data',
                ]);
    }

    /**
     * Test prediction generation.
     */
    public function test_prediction_generation(): void
    {
        $model = PredictiveModel::factory()->create();
        $assets = Asset::factory()->count(3)->create();

        $response = $this->postJson('/api/predictive/generate-predictions', [
            'model_id' => $model->id,
            'asset_ids' => $assets->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Predictions generated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'success',
                         'predictions_count',
                         'predictions',
                         'message',
                     ],
                 ]);

        $this->assertDatabaseCount('predictions', 23); // 20 from setup + 3 new
    }

    /**
     * Test prediction generation validation.
     */
    public function test_prediction_generation_validation(): void
    {
        $response = $this->postJson('/api/predictive/generate-predictions', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'model_id',
                     'asset_ids',
                ]);
    }

    /**
     * Test predictions listing.
     */
    public function test_predictions_listing(): void
    {
        $response = $this->getJson('/api/predictive/predictions');

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
     * Test prediction filtering.
     */
    public function test_prediction_filtering(): void
    {
        $model = PredictiveModel::factory()->create();
        $asset = Asset::factory()->create();

        // Test model filter
        $response = $this->getJson("/api/predictive/predictions?model_id={$model->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $prediction) {
            $this->assertEquals($model->id, $prediction['predictive_model_id']);
        }

        // Test asset filter
        $response = $this->getJson("/api/predictive/predictions?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $prediction) {
            $this->assertEquals($asset->id, $prediction['asset_id']);
        }

        // Test risk level filter
        $response = $this->getJson('/api/predictive/predictions?risk_level=high');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $prediction) {
            $this->assertEquals('high', $prediction['risk_level']);
        }

        // Test status filters
        $response = $this->getJson('/api/predictive/predictions?status=high_risk');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $prediction) {
            $this->assertTrue(in_array($prediction['risk_level'], ['high', 'critical']));
        }
    }

    /**
     * Test prediction show.
     */
    public function test_prediction_show(): void
    {
        $prediction = Prediction::factory()->create();

        $response = $this->getJson("/api/predictive/predictions/{$prediction->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'predictiveModel',
                         'asset',
                         'prediction_type',
                         'predicted_value',
                         'confidence_score',
                         'risk_level',
                         'target_date',
                         'maintenanceRecommendations',
                     ],
                 ]);
    }

    /**
     * Test prediction validation.
     */
    public function test_prediction_validation(): void
    {
        $prediction = Prediction::factory()->create([
            'validation_status' => 'pending',
        ]);

        $validationData = [
            'actual_value' => 0.75,
            'actual_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson("/api/predictive/predictions/{$prediction->id}/validate", $validationData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Prediction validated successfully',
                 ]);

        $this->assertDatabaseHas('predictions', [
            'id' => $prediction->id,
            'validation_status' => 'validated',
            'actual_value' => 0.75,
        ]);
    }

    /**
     * Test prediction validation with actual value.
     */
    public function test_prediction_validation_accuracy(): void
    {
        $prediction = Prediction::factory()->create([
            'predicted_value' => 0.8,
            'validation_status' => 'pending',
        ]);

        $response = $this->postJson("/api/predictive/predictions/{$prediction->id}/validate", [
            'actual_value' => 0.75,
        ]);

        $response->assertStatus(200);
        
        $updatedPrediction = $prediction->fresh();
        $this->assertEquals(0.75, $updatedPrediction->actual_value);
        $this->assertEquals('validated', $updatedPrediction->validation_status);
        $this->assertNotNull($updatedPrediction->accuracy_score);
        $this->assertEquals(-0.05, $updatedPrediction->error_margin);
    }

    /**
     * Test maintenance recommendations listing.
     */
    public function test_maintenance_recommendations_listing(): void
    {
        $response = $this->getJson('/api/predictive/recommendations');

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
     * Test maintenance recommendation filtering.
     */
    public function test_maintenance_recommendation_filtering(): void
    {
        $asset = Asset::factory()->create();

        // Test asset filter
        $response = $this->getJson("/api/predictive/recommendations?asset_id={$asset->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $recommendation) {
            $this->assertEquals($asset->id, $recommendation['asset_id']);
        }

        // Test type filter
        $response = $this->getJson('/api/predictive/recommendations?recommendation_type=inspection');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $recommendation) {
            $this->assertEquals('inspection', $recommendation['recommendation_type']);
        }

        // Test urgency filter
        $response = $this->getJson('/api/predictive/recommendations?urgency=high');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $recommendation) {
            $this->assertEquals('high', $recommendation['urgency']);
        }

        // Test status filters
        $response = $this->getJson('/api/predictive/recommendations?status=pending');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $recommendation) {
            $this->assertEquals('pending', $recommendation['status']);
        }

        // Test boolean filters
        $response = $this->getJson('/api/predictive/recommendations?overdue=1');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/predictive/recommendations?urgent=1');
        $response->assertStatus(200);
    }

    /**
     * Test maintenance recommendation show.
     */
    public function test_maintenance_recommendation_show(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create();

        $response = $this->getJson("/api/predictive/recommendations/{$recommendation->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'prediction',
                         'asset',
                         'recommendation_type',
                         'urgency',
                         'description',
                         'estimated_cost',
                         'recommended_date',
                         'deadline_date',
                         'status',
                         'assignedTo',
                         'workOrder',
                     ],
                 ]);
    }

    /**
     * Test maintenance recommendation approval.
     */
    public function test_maintenance_recommendation_approval(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/predictive/recommendations/{$recommendation->id}/approve", [
            'notes' => 'Approved for immediate action',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Recommendation approved successfully',
                 ]);

        $this->assertDatabaseHas('maintenance_recommendations', [
            'id' => $recommendation->id,
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);
    }

    /**
     * Test maintenance recommendation rejection.
     */
    public function test_maintenance_recommendation_rejection(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/predictive/recommendations/{$recommendation->id}/reject", [
            'reason' => 'Not necessary at this time',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Recommendation rejected successfully',
                 ]);

        $this->assertDatabaseHas('maintenance_recommendations', [
            'id' => $recommendation->id,
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejection_reason' => 'Not necessary at this time',
        ]);
    }

    /**
     * Test maintenance recommendation completion.
     */
    public function test_maintenance_recommendation_completion(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create([
            'status' => 'approved',
        ]);

        $completionData = [
            'notes' => 'Maintenance completed successfully',
            'actual_cost' => 350.50,
            'actual_duration_hours' => 3.5,
            'effectiveness_rating' => 4.5,
        ];

        $response = $this->postJson("/api/predictive/recommendations/{$recommendation->id}/complete", $completionData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Recommendation completed successfully',
                 ]);

        $this->assertDatabaseHas('maintenance_recommendations', [
            'id' => $recommendation->id,
            'status' => 'completed',
            'actual_cost' => 350.50,
            'actual_duration_hours' => 3.5,
            'effectiveness_rating' => 4.5,
        ]);
    }

    /**
     * Test work order creation from recommendation.
     */
    public function test_work_order_creation_from_recommendation(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create([
            'status' => 'approved',
        ]);

        $response = $this->postJson("/api/predictive/recommendations/{$recommendation->id}/create-work-order");

        $response->assertStatus(200)
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
                         'asset',
                         'assignedTo',
                     ],
                 ]);

        $this->assertDatabaseHas('work_orders', [
            'asset_id' => $recommendation->asset_id,
        ]);

        $this->assertDatabaseHas('maintenance_recommendations', [
            'id' => $recommendation->id,
            'work_order_id' => $response->json('data.id'),
        ]);
    }

    /**
     * Test training histories listing.
     */
    public function test_training_histories_listing(): void
    {
        $response = $this->getJson('/api/predictive/training-histories');

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
     * Test training histories filtering.
     */
    public function test_training_histories_filtering(): void
    {
        $model = PredictiveModel::factory()->create();

        // Test model filter
        $response = $this->getJson("/api/predictive/training-histories?model_id={$model->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $history) {
            $this->assertEquals($model->id, $history['predictive_model_id']);
        }

        // Test status filters
        $response = $this->getJson('/api/predictive/training-histories?status=successful');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $history) {
            $this->assertEquals('completed', $history['training_status']);
        }

        $response = $this->getJson('/api/predictive/training-histories?status=failed');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/predictive/training-histories?status=in_progress');
        $response->assertStatus(200);
    }

    /**
     * Test model performances listing.
     */
    public function test_model_performances_listing(): void
    {
        $response = $this->getJson('/api/predictive/model-performances');

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
     * Test model performances filtering.
     */
    public function test_model_performances_filtering(): void
    {
        $model = PredictiveModel::factory()->create();

        // Test model filter
        $response = $this->getJson("/api/predictive/model-performances?model_id={$model->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $performance) {
            $this->assertEquals($model->id, $performance['predictive_model_id']);
        }

        // Test evaluation type filter
        $response = $this->getJson('/api/predictive/model-performances?evaluation_type=testing');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $performance) {
            $this->assertEquals('testing', $performance['evaluation_type']);
        }

        // Test boolean filters
        $response = $this->getJson('/api/predictive/model-performances?with_drift=1');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/predictive/model-performances?recent=1');
        $response->assertStatus(200);
    }

    /**
     * Test model evaluation.
     */
    public function test_model_evaluation(): void
    {
        $model = PredictiveModel::factory()->create();

        $testData = [];
        for ($i = 0; $i < 50; $i++) {
            $testData[] = [
                'features' => [
                    'age' => rand(1, 3650),
                    'usage_hours' => rand(100, 10000),
                    'temperature' => rand(15, 35) + mt_rand() / mt_getrandmax(),
                ],
                'target' => mt_rand() / mt_getrandmax(),
            ];
        }

        $response = $this->postJson("/api/predictive/models/{$model->id}/evaluate", [
            'test_data' => $testData,
            'evaluation_type' => 'testing',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Model evaluation completed successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'predictive_model_id',
                         'evaluation_date',
                         'evaluation_type',
                         'accuracy_score',
                         'f1_score',
                         'sample_count',
                     ],
                 ]);

        $this->assertDatabaseHas('model_performances', [
            'predictive_model_id' => $model->id,
            'evaluation_type' => 'testing',
        ]);
    }

    /**
     * Test model drift detection.
     */
    public function test_model_drift_detection(): void
    {
        $model = PredictiveModel::factory()->create();

        // Create some validated predictions
        Prediction::factory()->count(15)->create([
            'predictive_model_id' => $model->id,
            'validation_status' => 'validated',
            'accuracy_score' => 0.85,
            'actual_date' => now()->subDays(rand(1, 20)),
        ]);

        $response = $this->postJson("/api/predictive/models/{$model->id}/detect-drift");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Drift detection completed',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'drift_detected',
                         'current_accuracy',
                         'baseline_accuracy',
                         'accuracy_drop',
                     ],
                 ]);
    }

    /**
     * Test maintenance schedule generation.
     */
    public function test_maintenance_schedule_generation(): void
    {
        $predictions = Prediction::factory()->count(5)->create();

        $response = $this->postJson('/api/predictive/generate-schedule', [
            'prediction_ids' => $predictions->pluck('id')->toArray(),
            'optimization_enabled' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Maintenance schedule generated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'time_window',
                             'predictions',
                             'maintenance_actions',
                             'resource_requirements',
                         ],
                     ],
                 ]);
    }

    /**
     * Test optimized maintenance schedule generation.
     */
    public function test_optimized_maintenance_schedule_generation(): void
    {
        $predictions = Prediction::factory()->count(5)->create();

        $response = $this->postJson('/api/predictive/generate-schedule', [
            'prediction_ids' => $predictions->pluck('id')->toArray(),
            'optimization_enabled' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Maintenance schedule generated successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'original_schedule',
                         'optimized_schedule',
                         'optimization_metrics',
                     ],
                 ]);
    }

    /**
     * Test predictive maintenance statistics.
     */
    public function test_predictive_maintenance_statistics(): void
    {
        $response = $this->getJson('/api/predictive/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'models',
                         'predictions',
                         'recommendations',
                         'training',
                         'performance',
                         'recent_activity',
                     ],
                 ]);

        $stats = $response->json('data');
        $this->assertArrayHasKey('total', $stats['models']);
        $this->assertArrayHasKey('active', $stats['models']);
        $this->assertArrayHasKey('total', $stats['predictions']);
        $this->assertArrayHasKey('total', $stats['recommendations']);
        $this->assertArrayHasKey('total_trainings', $stats['training']);
    }

    /**
     * Test predictive maintenance dashboard.
     */
    public function test_predictive_maintenance_dashboard(): void
    {
        $response = $this->getJson('/api/predictive/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'overview',
                         'model_performance',
                         'risk_analysis',
                         'recommendations_summary',
                         'trends',
                     ],
                 ]);

        $dashboard = $response->json('data');
        $this->assertArrayHasKey('active_models', $dashboard['overview']);
        $this->assertArrayHasKey('total_predictions_today', $dashboard['overview']);
        $this->assertArrayHasKey('high_risk_predictions', $dashboard['overview']);
        $this->assertArrayHasKey('pending_recommendations', $dashboard['overview']);
    }

    /**
     * Test PredictiveModel model relationships.
     */
    public function test_predictive_model_relationships(): void
    {
        $model = PredictiveModel::factory()->create();
        
        // Test creator relationship
        $this->assertInstanceOf(User::class, $model->creator);
        
        // Test predictions relationship
        $this->assertEmpty($model->predictions);
        
        // Test training histories relationship
        $this->assertEmpty($model->trainingHistories);
        
        // Test performances relationship
        $this->assertEmpty($model->performances);
    }

    /**
     * Test Prediction model relationships.
     */
    public function test_prediction_model_relationships(): void
    {
        $prediction = Prediction::factory()->create();
        
        // Test predictive model relationship
        $this->assertInstanceOf(PredictiveModel::class, $prediction->predictiveModel);
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $prediction->asset);
        
        // Test maintenance recommendations relationship
        $this->assertEmpty($prediction->maintenanceRecommendations);
    }

    /**
     * Test MaintenanceRecommendation model relationships.
     */
    public function test_maintenance_recommendation_relationships(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create();
        
        // Test prediction relationship
        $this->assertInstanceOf(Prediction::class, $recommendation->prediction);
        
        // Test asset relationship
        $this->assertInstanceOf(Asset::class, $recommendation->asset);
        
        // Test creator relationship
        $this->assertInstanceOf(User::class, $recommendation->creator);
    }

    /**
     * Test PredictiveMaintenanceService training.
     */
    public function test_predictive_maintenance_service_training(): void
    {
        $model = PredictiveModel::factory()->create();
        
        $trainingData = [];
        for ($i = 0; $i < 50; $i++) {
            $trainingData[] = [
                'features' => [
                    'age' => rand(1, 3650),
                    'usage_hours' => rand(100, 10000),
                ],
                'target' => mt_rand() / mt_getrandmax(),
            ];
        }

        $result = $this->predictiveService->trainModel($model, $trainingData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('training_history_id', $result);
        $this->assertArrayHasKey('model_performance', $result);
    }

    /**
     * Test PredictiveMaintenanceService prediction generation.
     */
    public function test_predictive_maintenance_service_prediction_generation(): void
    {
        $model = PredictiveModel::factory()->create();
        $assets = Asset::factory()->count(3)->create();

        $result = $this->predictiveService->generatePredictions($model, $assets->toArray());

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['predictions_count']);
        $this->assertCount(3, $result['predictions']);
    }

    /**
     * Test PredictiveMaintenanceService model evaluation.
     */
    public function test_predictive_maintenance_service_model_evaluation(): void
    {
        $model = PredictiveModel::factory()->create();
        
        $testData = [];
        for ($i = 0; $i < 30; $i++) {
            $testData[] = [
                'features' => [
                    'age' => rand(1, 3650),
                    'usage_hours' => rand(100, 10000),
                ],
                'target' => mt_rand() / mt_getrandmax(),
            ];
        }

        $performance = $this->predictiveService->evaluateModel($model, $testData);

        $this->assertInstanceOf(ModelPerformance::class, $performance);
        $this->assertEquals($model->id, $performance->predictive_model_id);
        $this->assertNotNull($performance->accuracy_score);
    }

    /**
     * Test PredictiveMaintenanceService drift detection.
     */
    public function test_predictive_maintenance_service_drift_detection(): void
    {
        $model = PredictiveModel::factory()->create();

        // Create validated predictions
        Prediction::factory()->count(15)->create([
            'predictive_model_id' => $model->id,
            'validation_status' => 'validated',
            'accuracy_score' => 0.82,
            'actual_date' => now()->subDays(rand(1, 25)),
        ]);

        $driftAnalysis = $this->predictiveService->detectModelDrift($model);

        $this->assertArrayHasKey('drift_detected', $driftAnalysis);
        $this->assertArrayHasKey('current_accuracy', $driftAnalysis);
    }

    /**
     * Test PredictiveMaintenanceService schedule generation.
     */
    public function test_predictive_maintenance_service_schedule_generation(): void
    {
        $predictions = Prediction::factory()->count(5)->create();

        $schedule = $this->predictiveService->generateMaintenanceSchedule($predictions->toArray());

        $this->assertIsArray($schedule);
        $this->assertNotEmpty($schedule);
        $this->assertArrayHasKey('time_window', $schedule[0]);
        $this->assertArrayHasKey('predictions', $schedule[0]);
    }

    /**
     * Test PredictiveMaintenanceService schedule optimization.
     */
    public function test_predictive_maintenance_service_schedule_optimization(): void
    {
        $predictions = Prediction::factory()->count(3)->create();

        $schedule = $this->predictiveService->generateMaintenanceSchedule($predictions->toArray());
        $optimizedSchedule = $this->predictiveService->optimizeMaintenanceSchedule($schedule);

        $this->assertArrayHasKey('original_schedule', $optimizedSchedule);
        $this->assertArrayHasKey('optimized_schedule', $optimizedSchedule);
        $this->assertArrayHasKey('optimization_metrics', $optimizedSchedule);
    }

    /**
     * Test predictive maintenance without authentication.
     */
    public function test_predictive_maintenance_without_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/predictive/models');
        $response->assertStatus(401);

        $response = $this->postJson('/api/predictive/models');
        $response->assertStatus(401);
    }

    /**
     * Test predictive maintenance with insufficient permissions.
     */
    public function test_predictive_maintenance_with_insufficient_permissions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        Sanctum::actingAs($viewer);

        // Viewers should be able to read predictive maintenance data
        $response = $this->getJson('/api/predictive/models');
        $response->assertStatus(200);
        
        $response = $this->getJson('/api/predictive/statistics');
        $response->assertStatus(200);
        
        // But not be able to create models
        $response = $this->postJson('/api/predictive/models', [
            'name' => 'Test Model',
            'model_type' => 'failure_prediction',
            'algorithm' => 'random_forest',
            'target_variable' => 'failure_probability',
            'input_features' => ['age', 'usage_hours'],
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test model type enums.
     */
    public function test_model_type_enums(): void
    {
        $model = PredictiveModel::factory()->create([
            'model_type' => 'failure_prediction',
        ]);

        $this->assertEquals('failure_prediction', $model->model_type->value);
        $this->assertEquals('Failure Prediction', $model->model_type->getDisplayName());
        $this->assertEquals('Predicts when equipment is likely to fail', $model->model_type->getDescription());
    }

    /**
     * Test algorithm enums.
     */
    public function test_algorithm_enums(): void
    {
        $model = PredictiveModel::factory()->create([
            'algorithm' => 'random_forest',
        ]);

        $this->assertEquals('random_forest', $model->algorithm->value);
        $this->assertEquals('Random Forest', $model->algorithm->getDisplayName());
        $this->assertEquals('Ensemble', $model->algorithm->getCategory());
    }

    /**
     * Test prediction type enums.
     */
    public function test_prediction_type_enums(): void
    {
        $prediction = Prediction::factory()->create([
            'prediction_type' => 'failure_probability',
        ]);

        $this->assertEquals('failure_probability', $prediction->prediction_type->value);
        $this->assertEquals('Failure Probability', $prediction->prediction_type->getDisplayName());
        $this->assertEquals('%', $prediction->prediction_type->getUnit());
    }

    /**
     * Test risk level enums.
     */
    public function test_risk_level_enums(): void
    {
        $prediction = Prediction::factory()->create([
            'risk_level' => 'high',
        ]);

        $this->assertEquals('high', $prediction->risk_level->value);
        $this->assertEquals('High', $prediction->risk_level->getDisplayName());
        $this->assertEquals('orange', $prediction->risk_level->getColor());
        $this->assertTrue($prediction->risk_level->requiresImmediateAction());
    }

    /**
     * Test recommendation type enums.
     */
    public function test_recommendation_type_enums(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create([
            'recommendation_type' => 'preventive_maintenance',
        ]);

        $this->assertEquals('preventive_maintenance', $recommendation->recommendation_type->value);
        $this->assertEquals('Preventive Maintenance', $recommendation->recommendation_type->getDisplayName());
        $this->assertEquals(4.0, $recommendation->recommendation_type->getTypicalDurationHours());
    }

    /**
     * Test recommendation status enums.
     */
    public function test_recommendation_status_enums(): void
    {
        $recommendation = MaintenanceRecommendation::factory()->create([
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $recommendation->status->value);
        $this->assertEquals('Pending', $recommendation->status->getDisplayName());
        $this->assertEquals('blue', $recommendation->status->getColor());
        $this->assertTrue($recommendation->status->requiresAction());
    }
}
