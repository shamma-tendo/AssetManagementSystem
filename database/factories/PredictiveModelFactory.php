<?php

namespace Database\Factories;

use App\Models\PredictiveModel;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictiveModel>
 */
class PredictiveModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modelTypes = [
            'failure_prediction', 'remaining_useful_life', 'anomaly_detection',
            'predictive_maintenance', 'condition_monitoring', 'energy_consumption',
            'performance_degradation', 'optimal_maintenance'
        ];

        $algorithms = [
            'random_forest', 'gradient_boosting', 'linear_regression', 'logistic_regression',
            'support_vector_machine', 'neural_network', 'lstm', 'arima',
            'isolation_forest', 'one_class_svm', 'k_means', 'dbscan',
            'decision_tree', 'xgboost', 'lightgbm'
        ];

        $modelType = $this->faker->randomElement($modelTypes);
        $algorithm = $this->faker->randomElement($algorithms);
        $creator = User::factory()->create(['role' => UserRole::MANAGER]);

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(4),
            'model_type' => $modelType,
            'algorithm' => $algorithm,
            'target_variable' => $this->getTargetVariableForModelType($modelType),
            'input_features' => $this->getInputFeaturesForModelType($modelType),
            'hyperparameters' => $this->getHyperparametersForAlgorithm($algorithm),
            'training_data_period' => [
                'start_date' => $this->faker->dateTimeBetween('-2 years', '-1 year')->format('Y-m-d'),
                'end_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'data_source' => $this->faker->randomElement(['sensor_data', 'maintenance_records', 'operational_logs']),
            ],
            'validation_data_period' => [
                'start_date' => $this->faker->dateTimeBetween('-6 months', '-3 months')->format('Y-m-d'),
                'end_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'data_source' => $this->faker->randomElement(['sensor_data', 'maintenance_records', 'operational_logs']),
            ],
            'accuracy_score' => $this->faker->randomFloat(4, 0.7, 0.95),
            'precision_score' => $this->faker->randomFloat(4, 0.65, 0.93),
            'recall_score' => $this->faker->randomFloat(4, 0.68, 0.92),
            'f1_score' => $this->faker->randomFloat(4, 0.67, 0.94),
            'mse' => $this->faker->randomFloat(6, 0.001, 0.1),
            'rmse' => $this->faker->randomFloat(6, 0.01, 0.3),
            'mae' => $this->faker->randomFloat(6, 0.005, 0.2),
            'r2_score' => $this->faker->randomFloat(4, 0.6, 0.9),
            'training_samples' => $this->faker->numberBetween(1000, 50000),
            'validation_samples' => $this->faker->numberBetween(200, 10000),
            'feature_importance' => $this->generateFeatureImportance($this->getInputFeaturesForModelType($modelType)),
            'training_metrics' => [
                'training_loss' => $this->faker->randomFloat(4, 0.1, 0.8),
                'validation_loss' => $this->faker->randomFloat(4, 0.15, 0.85),
                'epochs_completed' => $this->faker->numberBetween(50, 200),
                'convergence_epoch' => $this->faker->numberBetween(20, 100),
            ],
            'validation_metrics' => [
                'validation_loss' => $this->faker->randomFloat(4, 0.15, 0.85),
                'early_stopping_epoch' => $this->faker->numberBetween(30, 120),
                'best_epoch' => $this->faker->numberBetween(40, 150),
            ],
            'last_trained_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'next_retrain_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'is_active' => $this->faker->boolean(80),
            'auto_retrain' => $this->faker->boolean(60),
            'retrain_frequency_days' => $this->faker->numberBetween(7, 90),
            'min_accuracy_threshold' => $this->faker->randomFloat(4, 0.65, 0.85),
            'model_version' => $this->faker->semver(),
            'model_path' => $this->faker->filePath(),
            'created_by' => $creator->id,
        ];
    }

    /**
     * Get target variable for model type.
     */
    private function getTargetVariableForModelType(string $modelType): string
    {
        return match($modelType) {
            'failure_prediction' => $this->faker->randomElement(['failure_probability', 'time_to_failure']),
            'remaining_useful_life' => $this->faker->randomElement(['rul_days', 'rul_hours']),
            'anomaly_detection' => $this->faker->randomElement(['anomaly_score', 'is_anomaly']),
            'predictive_maintenance' => $this->faker->randomElement(['maintenance_needed', 'maintenance_urgency']),
            'condition_monitoring' => $this->faker->randomElement(['condition_score', 'health_index']),
            'energy_consumption' => $this->faker->randomElement(['energy_usage', 'power_consumption']),
            'performance_degradation' => $this->faker->randomElement(['performance_score', 'efficiency']),
            'optimal_maintenance' => $this->faker->randomElement(['optimal_interval', 'maintenance_cost']),
            default => 'target_variable',
        };
    }

    /**
     * Get input features for model type.
     */
    private function getInputFeaturesForModelType(string $modelType): array
    {
        return match($modelType) {
            'failure_prediction' => ['age', 'usage_hours', 'maintenance_count', 'temperature', 'vibration', 'pressure'],
            'remaining_useful_life' => ['age', 'usage_hours', 'operating_conditions', 'maintenance_history', 'performance_metrics'],
            'anomaly_detection' => ['sensor_readings', 'operational_parameters', 'performance_metrics', 'environmental_conditions'],
            'predictive_maintenance' => ['age', 'usage', 'condition_indicators', 'operational_stress', 'maintenance_history'],
            'condition_monitoring' => ['vibration', 'temperature', 'pressure', 'noise', 'oil_analysis', 'performance_metrics'],
            'energy_consumption' => ['operating_hours', 'load_factor', 'environmental_conditions', 'equipment_status'],
            'performance_degradation' => ['age', 'usage_hours', 'maintenance_history', 'performance_metrics', 'operating_conditions'],
            'optimal_maintenance' => ['age', 'usage', 'maintenance_cost', 'downtime_cost', 'failure_probability'],
            default => ['age', 'usage_hours'],
        };
    }

    /**
     * Get hyperparameters for algorithm.
     */
    private function getHyperparametersForAlgorithm(string $algorithm): array
    {
        return match($algorithm) {
            'random_forest' => [
                'n_estimators' => $this->faker->numberBetween(50, 200),
                'max_depth' => $this->faker->numberBetween(5, 20),
                'min_samples_split' => $this->faker->numberBetween(2, 10),
                'min_samples_leaf' => $this->faker->numberBetween(1, 5),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'gradient_boosting' => [
                'n_estimators' => $this->faker->numberBetween(50, 200),
                'learning_rate' => $this->faker->randomFloat(3, 0.01, 0.3),
                'max_depth' => $this->faker->numberBetween(3, 10),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'neural_network' => [
                'hidden_layer_sizes' => [100, 50],
                'activation' => $this->faker->randomElement(['relu', 'tanh', 'sigmoid']),
                'solver' => $this->faker->randomElement(['adam', 'sgd', 'lbfgs']),
                'max_iter' => $this->faker->numberBetween(500, 2000),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'xgboost' => [
                'n_estimators' => $this->faker->numberBetween(50, 200),
                'learning_rate' => $this->faker->randomFloat(3, 0.01, 0.3),
                'max_depth' => $this->faker->numberBetween(3, 10),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'lightgbm' => [
                'n_estimators' => $this->faker->numberBetween(50, 200),
                'learning_rate' => $this->faker->randomFloat(3, 0.01, 0.3),
                'num_leaves' => $this->faker->numberBetween(20, 100),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'lstm' => [
                'units' => $this->faker->numberBetween(32, 128),
                'dropout' => $this->faker->randomFloat(3, 0.1, 0.5),
                'epochs' => $this->faker->numberBetween(50, 200),
                'batch_size' => $this->faker->numberBetween(16, 64),
            ],
            'arima' => [
                'order' => [1, 1, 1],
                'seasonal_order' => [0, 0, 0, 0],
            ],
            'isolation_forest' => [
                'n_estimators' => $this->faker->numberBetween(50, 200),
                'contamination' => $this->faker->randomFloat(3, 0.01, 0.2),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'one_class_svm' => [
                'nu' => $this->faker->randomFloat(3, 0.01, 0.5),
                'kernel' => $this->faker->randomElement(['rbf', 'linear', 'poly']),
                'gamma' => $this->faker->randomElement(['scale', 'auto']),
            ],
            'k_means' => [
                'n_clusters' => $this->faker->numberBetween(2, 10),
                'random_state' => $this->faker->numberBetween(1, 100),
                'n_init' => $this->faker->numberBetween(10, 20),
            ],
            'dbscan' => [
                'eps' => $this->faker->randomFloat(3, 0.1, 1.0),
                'min_samples' => $this->faker->numberBetween(3, 10),
            ],
            'decision_tree' => [
                'max_depth' => $this->faker->numberBetween(5, 20),
                'min_samples_split' => $this->faker->numberBetween(2, 10),
                'min_samples_leaf' => $this->faker->numberBetween(1, 5),
                'random_state' => $this->faker->numberBetween(1, 100),
            ],
            'linear_regression' => [
                'fit_intercept' => $this->faker->boolean(),
                'normalize' => $this->faker->boolean(),
            ],
            'logistic_regression' => [
                'C' => $this->faker->randomFloat(3, 0.1, 10.0),
                'random_state' => $this->faker->numberBetween(1, 100),
                'max_iter' => $this->faker->numberBetween(500, 2000),
            ],
            'support_vector_machine' => [
                'C' => $this->faker->randomFloat(3, 0.1, 10.0),
                'kernel' => $this->faker->randomElement(['rbf', 'linear', 'poly', 'sigmoid']),
                'gamma' => $this->faker->randomElement(['scale', 'auto']),
            ],
            default => [],
        };
    }

    /**
     * Generate feature importance.
     */
    private function generateFeatureImportance(array $features): array
    {
        $importance = [];
        $total = 0;

        foreach ($features as $feature) {
            $importance[$feature] = $this->faker->randomFloat(4, 0.01, 0.5);
            $total += $importance[$feature];
        }

        // Normalize to sum to 1
        foreach ($importance as $feature => $value) {
            $importance[$feature] = $value / $total;
        }

        return $importance;
    }

    /**
     * Create a failure prediction model.
     */
    public function failurePrediction(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'failure_prediction',
            'algorithm' => 'random_forest',
            'target_variable' => 'failure_probability',
            'input_features' => ['age', 'usage_hours', 'maintenance_count', 'temperature', 'vibration', 'pressure'],
            'description' => 'Predicts equipment failure probability based on operational data',
        ]);
    }

    /**
     * Create a remaining useful life model.
     */
    public function remainingUsefulLife(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'remaining_useful_life',
            'algorithm' => 'gradient_boosting',
            'target_variable' => 'rul_days',
            'input_features' => ['age', 'usage_hours', 'operating_conditions', 'maintenance_history', 'performance_metrics'],
            'description' => 'Estimates remaining operational life of equipment',
        ]);
    }

    /**
     * Create an anomaly detection model.
     */
    public function anomalyDetection(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'anomaly_detection',
            'algorithm' => 'isolation_forest',
            'target_variable' => 'anomaly_score',
            'input_features' => ['sensor_readings', 'operational_parameters', 'performance_metrics', 'environmental_conditions'],
            'description' => 'Identifies unusual patterns in equipment behavior',
        ]);
    }

    /**
     * Create a predictive maintenance model.
     */
    public function predictiveMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'predictive_maintenance',
            'algorithm' => 'xgboost',
            'target_variable' => 'maintenance_needed',
            'input_features' => ['age', 'usage', 'condition_indicators', 'operational_stress', 'maintenance_history'],
            'description' => 'Predicts optimal maintenance timing',
        ]);
    }

    /**
     * Create a condition monitoring model.
     */
    public function conditionMonitoring(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'condition_monitoring',
            'algorithm' => 'neural_network',
            'target_variable' => 'condition_score',
            'input_features' => ['vibration', 'temperature', 'pressure', 'noise', 'oil_analysis', 'performance_metrics'],
            'description' => 'Monitors equipment condition over time',
        ]);
    }

    /**
     * Create an energy consumption model.
     */
    public function energyConsumption(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'energy_consumption',
            'algorithm' => 'lstm',
            'target_variable' => 'energy_usage',
            'input_features' => ['operating_hours', 'load_factor', 'environmental_conditions', 'equipment_status'],
            'description' => 'Predicts energy usage patterns',
        ]);
    }

    /**
     * Create a performance degradation model.
     */
    public function performanceDegradation(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'performance_degradation',
            'algorithm' => 'gradient_boosting',
            'target_variable' => 'performance_score',
            'input_features' => ['age', 'usage_hours', 'maintenance_history', 'performance_metrics', 'operating_conditions'],
            'description' => 'Tracks performance decline over time',
        ]);
    }

    /**
     * Create an optimal maintenance model.
     */
    public function optimalMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'optimal_maintenance',
            'algorithm' => 'lightgbm',
            'target_variable' => 'optimal_interval',
            'input_features' => ['age', 'usage', 'maintenance_cost', 'downtime_cost', 'failure_probability'],
            'description' => 'Optimizes maintenance schedules',
        ]);
    }

    /**
     * Create a random forest model.
     */
    public function randomForest(): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => 'random_forest',
            'hyperparameters' => [
                'n_estimators' => 100,
                'max_depth' => 10,
                'min_samples_split' => 2,
                'min_samples_leaf' => 1,
                'random_state' => 42,
            ],
        ]);
    }

    /**
     * Create a gradient boosting model.
     */
    public function gradientBoosting(): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => 'gradient_boosting',
            'hyperparameters' => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'max_depth' => 3,
                'random_state' => 42,
            ],
        ]);
    }

    /**
     * Create a neural network model.
     */
    public function neuralNetwork(): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => 'neural_network',
            'hyperparameters' => [
                'hidden_layer_sizes' => [100, 50],
                'activation' => 'relu',
                'solver' => 'adam',
                'max_iter' => 1000,
                'random_state' => 42,
            ],
        ]);
    }

    /**
     * Create an XGBoost model.
     */
    public function xgboost(): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => 'xgboost',
            'hyperparameters' => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'max_depth' => 6,
                'random_state' => 42,
            ],
        ]);
    }

    /**
     * Create a LightGBM model.
     */
    public function lightgbm(): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => 'lightgbm',
            'hyperparameters' => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'num_leaves' => 31,
                'random_state' => 42,
            ],
        ]);
    }

    /**
     * Create an LSTM model.
     */
    public function lstm(): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => 'lstm',
            'hyperparameters' => [
                'units' => 50,
                'dropout' => 0.2,
                'epochs' => 100,
                'batch_size' => 32,
            ],
        ]);
    }

    /**
     * Create an active model.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'last_trained_at' => now()->subDays(rand(1, 30)),
            'next_retrain_at' => now()->addDays(rand(7, 60)),
        ]);
    }

    /**
     * Create an inactive model.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'description' => 'Model is currently inactive',
        ]);
    }

    /**
     * Create a model that needs retraining.
     */
    public function needsRetraining(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_retrain_at' => now()->subDays(rand(1, 30)),
            'last_trained_at' => now()->subDays(rand(60, 120)),
        ]);
    }

    /**
     * Create a model with auto-retrain enabled.
     */
    public function withAutoRetrain(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_retrain' => true,
            'retrain_frequency_days' => $this->faker->numberBetween(7, 30),
            'next_retrain_at' => now()->addDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Create a high-accuracy model.
     */
    public function highAccuracy(): static
    {
        return $this->state(fn (array $attributes) => [
            'accuracy_score' => $this->faker->randomFloat(4, 0.9, 0.98),
            'precision_score' => $this->faker->randomFloat(4, 0.88, 0.96),
            'recall_score' => $this->faker->randomFloat(4, 0.89, 0.97),
            'f1_score' => $this->faker->randomFloat(4, 0.88, 0.97),
            'r2_score' => $this->faker->randomFloat(4, 0.85, 0.95),
        ]);
    }

    /**
     * Create a low-accuracy model.
     */
    public function lowAccuracy(): static
    {
        return $this->state(fn (array $attributes) => [
            'accuracy_score' => $this->faker->randomFloat(4, 0.6, 0.75),
            'precision_score' => $this->faker->randomFloat(4, 0.58, 0.73),
            'recall_score' => $this->faker->randomFloat(4, 0.59, 0.74),
            'f1_score' => $this->faker->randomFloat(4, 0.58, 0.74),
            'r2_score' => $this->faker->randomFloat(4, 0.5, 0.7),
        ]);
    }

    /**
     * Create a recently trained model.
     */
    public function recentlyTrained(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_trained_at' => now()->subDays(rand(1, 7)),
            'next_retrain_at' => now()->addDays(rand(14, 60)),
            'model_version' => $this->faker->semver(),
        ]);
    }

    /**
     * Create an old model.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_trained_at' => now()->subDays(rand(90, 365)),
            'next_retrain_at' => now()->subDays(rand(1, 30)),
            'model_version' => '1.' . $this->faker->numberBetween(0, 5) . '.' . $this->faker->numberBetween(0, 9),
        ]);
    }

    /**
     * Create a model for specific creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create a model with specific algorithm.
     */
    public function withAlgorithm(string $algorithm): static
    {
        return $this->state(fn (array $attributes) => [
            'algorithm' => $algorithm,
            'hyperparameters' => $this->getHyperparametersForAlgorithm($algorithm),
        ]);
    }

    /**
     * Create a model with specific type.
     */
    public function ofType(string $modelType): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => $modelType,
            'target_variable' => $this->getTargetVariableForModelType($modelType),
            'input_features' => $this->getInputFeaturesForModelType($modelType),
        ]);
    }
}
