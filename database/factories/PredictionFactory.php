<?php

namespace Database\Factories;

use App\Models\Prediction;
use App\Models\PredictiveModel;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<Prediction>
 */
class PredictionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $model = PredictiveModel::inRandomOrder()->first() ?? PredictiveModel::factory()->create();
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        
        $predictionType = $this->getPredictionTypeForModel($model->model_type);
        $predictedValue = $this->generatePredictedValue($predictionType, $model);
        $confidence = $this->faker->randomFloat(4, 0.6, 0.95);
        $riskLevel = $this->assessRiskLevel($predictedValue, $predictionType, $confidence);

        return [
            'predictive_model_id' => $model->id,
            'asset_id' => $asset->id,
            'prediction_type' => $predictionType,
            'predicted_value' => $predictedValue,
            'confidence_score' => $confidence,
            'probability_distribution' => $this->generateProbabilityDistribution($predictedValue, $predictionType),
            'feature_values' => $this->generateFeatureValues($model->input_features),
            'prediction_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'target_date' => $this->faker->dateTimeBetween('now', '+90 days'),
            'time_horizon_days' => $this->faker->numberBetween(1, 365),
            'risk_level' => $riskLevel,
            'recommendations' => $this->generateRecommendations($riskLevel, $predictionType, $asset),
            'uncertainty_bounds' => $this->generateUncertaintyBounds($predictedValue, $confidence),
            'model_version' => $model->model_version,
            'prediction_metadata' => [
                'inference_time_ms' => $this->faker->numberBetween(10, 200),
                'feature_contributions' => $this->generateFeatureContributions($model->input_features, $model->feature_importance),
                'model_confidence' => $confidence,
                'data_quality_score' => $this->faker->randomFloat(4, 0.7, 0.95),
            ],
        ];
    }

    /**
     * Get prediction type for model.
     */
    private function getPredictionTypeForModel(string $modelType): string
    {
        return match($modelType) {
            'failure_prediction' => 'failure_probability',
            'remaining_useful_life' => 'remaining_useful_life',
            'anomaly_detection' => 'anomaly_likelihood',
            'predictive_maintenance' => 'maintenance_needed',
            'condition_monitoring' => 'condition_score',
            'energy_consumption' => 'energy_consumption',
            'performance_degradation' => 'performance_degradation',
            'optimal_maintenance' => 'optimal_maintenance_interval',
            default => 'failure_probability',
        };
    }

    /**
     * Generate predicted value based on prediction type.
     */
    private function generatePredictedValue(string $predictionType, PredictiveModel $model): float
    {
        return match($predictionType) {
            'failure_probability' => $this->faker->randomFloat(4, 0, 1),
            'remaining_useful_life' => $this->faker->numberBetween(1, 365),
            'anomaly_likelihood' => $this->faker->randomFloat(4, 0, 1),
            'maintenance_needed' => $this->faker->randomFloat(4, 0, 1),
            'condition_score' => $this->faker->randomFloat(4, 0, 1),
            'energy_consumption' => $this->faker->randomFloat(2, 100, 10000),
            'performance_degradation' => $this->faker->randomFloat(4, 0, 1),
            'optimal_maintenance_interval' => $this->faker->numberBetween(7, 180),
            default => $this->faker->randomFloat(4, 0, 1),
        };
    }

    /**
     * Assess risk level based on prediction.
     */
    private function assessRiskLevel(float $predictedValue, string $predictionType, float $confidence): string
    {
        $adjustedValue = $predictedValue * $confidence;

        return match($predictionType) {
            'failure_probability' => $this->getRiskLevelFromProbability($adjustedValue),
            'remaining_useful_life' => $this->getRiskLevelFromRul($adjustedValue),
            'anomaly_likelihood' => $this->getRiskLevelFromProbability($adjustedValue),
            'maintenance_needed' => $this->getRiskLevelFromProbability($adjustedValue),
            'condition_score' => $this->getRiskLevelFromCondition($adjustedValue),
            'energy_consumption' => 'medium', // Energy consumption typically medium risk
            'performance_degradation' => $this->getRiskLevelFromDegradation($adjustedValue),
            'optimal_maintenance_interval' => 'low', // Maintenance interval typically low risk
            default => 'medium',
        };
    }

    /**
     * Get risk level from probability.
     */
    private function getRiskLevelFromProbability(float $probability): string
    {
        if ($probability >= 0.8) return 'critical';
        if ($probability >= 0.6) return 'high';
        if ($probability >= 0.4) return 'medium';
        if ($probability >= 0.2) return 'low';
        return 'very_low';
    }

    /**
     * Get risk level from remaining useful life.
     */
    private function getRiskLevelFromRul(float $rul): string
    {
        if ($rul <= 7) return 'critical';
        if ($rul <= 30) return 'high';
        if ($rul <= 90) return 'medium';
        if ($rul <= 180) return 'low';
        return 'very_low';
    }

    /**
     * Get risk level from condition score.
     */
    private function getRiskLevelFromCondition(float $condition): string
    {
        if ($condition <= 0.3) return 'critical';
        if ($condition <= 0.5) return 'high';
        if ($condition <= 0.7) return 'medium';
        if ($condition <= 0.9) return 'low';
        return 'very_low';
    }

    /**
     * Get risk level from performance degradation.
     */
    private function getRiskLevelFromDegradation(float $degradation): string
    {
        if ($degradation >= 0.7) return 'high';
        if ($degradation >= 0.4) return 'medium';
        if ($degradation >= 0.2) return 'low';
        return 'very_low';
    }

    /**
     * Generate probability distribution.
     */
    private function generateProbabilityDistribution(float $value, string $predictionType): array
    {
        $stdDev = match($predictionType) {
            'failure_probability', 'anomaly_likelihood', 'maintenance_needed' => 0.1,
            'condition_score', 'performance_degradation' => 0.08,
            'remaining_useful_life' => $value * 0.2,
            'energy_consumption' => $value * 0.15,
            'optimal_maintenance_interval' => $value * 0.1,
            default => 0.1,
        };

        return [
            'mean' => $value,
            'std_dev' => $stdDev,
            'percentiles' => [
                5 => max(0, $value - 2 * $stdDev),
                25 => max(0, $value - 0.67 * $stdDev),
                50 => $value,
                75 => $value + 0.67 * $stdDev,
                95 => $value + 2 * $stdDev,
            ],
        ];
    }

    /**
     * Generate feature values.
     */
    private function generateFeatureValues(array $features): array
    {
        $values = [];
        
        foreach ($features as $feature) {
            $values[$feature] = match($feature) {
                'age' => $this->faker->numberBetween(1, 3650),
                'usage_hours' => $this->faker->numberBetween(100, 50000),
                'maintenance_count' => $this->faker->numberBetween(0, 50),
                'failure_count' => $this->faker->numberBetween(0, 10),
                'temperature' => $this->faker->randomFloat(2, 15, 45),
                'vibration' => $this->faker->randomFloat(2, 0, 100),
                'pressure' => $this->faker->randomFloat(2, 50, 150),
                'load' => $this->faker->randomFloat(2, 0, 100),
                'operating_conditions' => $this->faker->randomFloat(4, 0, 1),
                'maintenance_history' => $this->faker->randomFloat(4, 0, 1),
                'performance_metrics' => $this->faker->randomFloat(4, 0, 1),
                'environmental_factors' => $this->faker->randomFloat(4, 0, 1),
                'sensor_readings' => $this->faker->randomFloat(4, 0, 1),
                'operational_parameters' => $this->faker->randomFloat(4, 0, 1),
                'environmental_conditions' => $this->faker->randomFloat(4, 0, 1),
                'condition_indicators' => $this->faker->randomFloat(4, 0, 1),
                'operational_stress' => $this->faker->randomFloat(4, 0, 1),
                'noise' => $this->faker->randomFloat(2, 30, 90),
                'oil_analysis' => $this->faker->randomFloat(4, 0, 1),
                'operating_hours' => $this->faker->numberBetween(100, 10000),
                'load_factor' => $this->faker->randomFloat(4, 0, 1),
                'equipment_status' => $this->faker->randomElement(['running', 'idle', 'stopped']),
                'maintenance_cost' => $this->faker->randomFloat(2, 100, 5000),
                'downtime_cost' => $this->faker->randomFloat(2, 500, 10000),
                default => $this->faker->randomFloat(4, 0, 1),
            };
        }

        return $values;
    }

    /**
     * Generate recommendations.
     */
    private function generateRecommendations(string $riskLevel, string $predictionType, Asset $asset): array
    {
        $recommendations = [];

        if (in_array($riskLevel, ['critical', 'high'])) {
            $urgency = $riskLevel === 'critical' ? 'immediate' : 'urgent';
            
            $recommendations[] = [
                'type' => 'inspection',
                'urgency' => $urgency,
                'description' => "Schedule immediate inspection for {$asset->name}",
                'estimated_cost' => $asset->purchase_cost * 0.02,
                'recommended_date' => now()->addDays($riskLevel === 'critical' ? 1 : 3)->format('Y-m-d'),
            ];

            $recommendations[] = [
                'type' => 'preventive_maintenance',
                'urgency' => $urgency,
                'description' => "Perform preventive maintenance on {$asset->name}",
                'estimated_cost' => $asset->purchase_cost * 0.05,
                'recommended_date' => now()->addDays($riskLevel === 'critical' ? 3 : 7)->format('Y-m-d'),
            ];
        }

        if ($predictionType === 'failure_probability' && $riskLevel === 'critical') {
            $recommendations[] = [
                'type' => 'replacement',
                'urgency' => 'medium',
                'description' => "Plan for replacement of {$asset->name}",
                'estimated_cost' => $asset->purchase_cost * 0.8,
                'recommended_date' => now()->addDays(30)->format('Y-m-d'),
            ];
        }

        if ($predictionType === 'remaining_useful_life' && $riskLevel === 'high') {
            $recommendations[] = [
                'type' => 'replacement_planning',
                'urgency' => 'medium',
                'description' => "Begin replacement planning for {$asset->name}",
                'estimated_cost' => $asset->purchase_cost * 0.8,
                'recommended_date' => now()->addDays(60)->format('Y-m-d'),
            ];
        }

        return $recommendations;
    }

    /**
     * Generate uncertainty bounds.
     */
    private function generateUncertaintyBounds(float $value, float $confidence): array
    {
        $uncertainty = (1 - $confidence) * $value;
        
        return [
            'lower_bound' => max(0, $value - $uncertainty),
            'upper_bound' => $value + $uncertainty,
            'confidence_interval' => $confidence * 100,
        ];
    }

    /**
     * Generate feature contributions.
     */
    private function generateFeatureContributions(array $features, array $importance): array
    {
        $contributions = [];
        
        foreach ($features as $feature) {
            $weight = $importance[$feature] ?? 0.1;
            $value = $this->faker->randomFloat(4, -1, 1);
            
            $contributions[$feature] = [
                'value' => $value,
                'importance' => $weight,
                'contribution' => $value * $weight,
            ];
        }
        
        return $contributions;
    }

    /**
     * Create a failure probability prediction.
     */
    public function failureProbability(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'failure_probability',
            'predicted_value' => $this->faker->randomFloat(4, 0, 1),
            'risk_level' => $this->faker->randomElement(['very_low', 'low', 'medium', 'high', 'critical']),
        ]);
    }

    /**
     * Create a remaining useful life prediction.
     */
    public function remainingUsefulLife(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'remaining_useful_life',
            'predicted_value' => $this->faker->numberBetween(1, 365),
            'risk_level' => $this->faker->randomElement(['very_low', 'low', 'medium', 'high', 'critical']),
        ]);
    }

    /**
     * Create an anomaly likelihood prediction.
     */
    public function anomalyLikelihood(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'anomaly_likelihood',
            'predicted_value' => $this->faker->randomFloat(4, 0, 1),
            'risk_level' => $this->faker->randomElement(['very_low', 'low', 'medium', 'high', 'critical']),
        ]);
    }

    /**
     * Create a maintenance needed prediction.
     */
    public function maintenanceNeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'maintenance_needed',
            'predicted_value' => $this->faker->randomFloat(4, 0, 1),
            'risk_level' => $this->faker->randomElement(['very_low', 'low', 'medium', 'high']),
        ]);
    }

    /**
     * Create a condition score prediction.
     */
    public function conditionScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'condition_score',
            'predicted_value' => $this->faker->randomFloat(4, 0, 1),
            'risk_level' => $this->faker->randomElement(['very_low', 'low', 'medium', 'high', 'critical']),
        ]);
    }

    /**
     * Create an energy consumption prediction.
     */
    public function energyConsumption(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'energy_consumption',
            'predicted_value' => $this->faker->randomFloat(2, 100, 10000),
            'risk_level' => 'medium',
        ]);
    }

    /**
     * Create a performance degradation prediction.
     */
    public function performanceDegradation(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'performance_degradation',
            'predicted_value' => $this->faker->randomFloat(4, 0, 1),
            'risk_level' => $this->faker->randomElement(['very_low', 'low', 'medium', 'high']),
        ]);
    }

    /**
     * Create a critical risk prediction.
     */
    public function criticalRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'critical',
            'confidence_score' => $this->faker->randomFloat(4, 0.8, 0.95),
            'predicted_value' => match($attributes['prediction_type'] ?? 'failure_probability') {
                'failure_probability' => $this->faker->randomFloat(4, 0.8, 1.0),
                'remaining_useful_life' => $this->faker->numberBetween(1, 7),
                'anomaly_likelihood' => $this->faker->randomFloat(4, 0.8, 1.0),
                'condition_score' => $this->faker->randomFloat(4, 0, 0.3),
                'performance_degradation' => $this->faker->randomFloat(4, 0.7, 1.0),
                default => $this->faker->randomFloat(4, 0.8, 1.0),
            },
        ]);
    }

    /**
     * Create a high risk prediction.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'high',
            'confidence_score' => $this->faker->randomFloat(4, 0.7, 0.9),
            'predicted_value' => match($attributes['prediction_type'] ?? 'failure_probability') {
                'failure_probability' => $this->faker->randomFloat(4, 0.6, 0.8),
                'remaining_useful_life' => $this->faker->numberBetween(8, 30),
                'anomaly_likelihood' => $this->faker->randomFloat(4, 0.6, 0.8),
                'condition_score' => $this->faker->randomFloat(4, 0.3, 0.5),
                'performance_degradation' => $this->faker->randomFloat(4, 0.4, 0.7),
                default => $this->faker->randomFloat(4, 0.6, 0.8),
            },
        ]);
    }

    /**
     * Create a low risk prediction.
     */
    public function lowRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'low',
            'confidence_score' => $this->faker->randomFloat(4, 0.6, 0.85),
            'predicted_value' => match($attributes['prediction_type'] ?? 'failure_probability') {
                'failure_probability' => $this->faker->randomFloat(4, 0.1, 0.3),
                'remaining_useful_life' => $this->faker->numberBetween(180, 365),
                'anomaly_likelihood' => $this->faker->randomFloat(4, 0.1, 0.3),
                'condition_score' => $this->faker->randomFloat(4, 0.7, 0.9),
                'performance_degradation' => $this->faker->randomFloat(4, 0.1, 0.3),
                default => $this->faker->randomFloat(4, 0.1, 0.3),
            },
        ]);
    }

    /**
     * Create a high confidence prediction.
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(4, 0.85, 0.98),
            'uncertainty_bounds' => function (array $attributes) {
                $value = $attributes['predicted_value'];
                $confidence = 0.9;
                $uncertainty = (1 - $confidence) * $value;
                
                return [
                    'lower_bound' => max(0, $value - $uncertainty),
                    'upper_bound' => $value + $uncertainty,
                    'confidence_interval' => $confidence * 100,
                ];
            },
        ]);
    }

    /**
     * Create a low confidence prediction.
     */
    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(4, 0.6, 0.75),
            'uncertainty_bounds' => function (array $attributes) {
                $value = $attributes['predicted_value'];
                $confidence = 0.7;
                $uncertainty = (1 - $confidence) * $value;
                
                return [
                    'lower_bound' => max(0, $value - $uncertainty),
                    'upper_bound' => $value + $uncertainty,
                    'confidence_interval' => $confidence * 100,
                ];
            },
        ]);
    }

    /**
     * Create a recent prediction.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_date' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'target_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Create an old prediction.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_date' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
            'target_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a validated prediction.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'validation_status' => 'validated',
            'actual_value' => $this->faker->randomFloat(4, 0, 1),
            'actual_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'accuracy_score' => $this->faker->randomFloat(4, 0.7, 0.95),
            'error_margin' => $this->faker->randomFloat(4, -0.2, 0.2),
        ]);
    }

    /**
     * Create a prediction for specific model.
     */
    public function forModel(PredictiveModel $model): static
    {
        return $this->state(fn (array $attributes) => [
            'predictive_model_id' => $model->id,
            'prediction_type' => $this->getPredictionTypeForModel($model->model_type),
            'model_version' => $model->model_version,
        ]);
    }

    /**
     * Create a prediction for specific asset.
     */
    public function forAsset(Asset $asset): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => $asset->id,
        ]);
    }

    /**
     * Create a prediction with specific risk level.
     */
    public function withRiskLevel(string $riskLevel): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => $riskLevel,
        ]);
    }

    /**
     * Create a prediction for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_date' => $this->faker->dateTimeBetween('today', 'now'),
        ]);
    }

    /**
     * Create a prediction for this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_date' => $this->faker->dateTimeBetween('this week', 'now'),
        ]);
    }

    /**
     * Create a prediction that needs validation.
     */
    public function needsValidation(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'validation_status' => 'pending',
        ]);
    }

    /**
     * Create an imminent prediction (target date soon).
     */
    public function imminent(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'risk_level' => $this->faker->randomElement(['high', 'critical']),
        ]);
    }

    /**
     * Create a long-term prediction.
     */
    public function longTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => $this->faker->dateTimeBetween('+30 days', '+365 days'),
            'time_horizon_days' => $this->faker->numberBetween(30, 365),
        ]);
    }
}
