<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PredictiveModel extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'model_type',
        'algorithm',
        'target_variable',
        'input_features',
        'hyperparameters',
        'training_data_period',
        'validation_data_period',
        'accuracy_score',
        'precision_score',
        'recall_score',
        'f1_score',
        'mse',
        'rmse',
        'mae',
        'r2_score',
        'training_samples',
        'validation_samples',
        'model_version',
        'model_path',
        'feature_importance',
        'training_metrics',
        'validation_metrics',
        'last_trained_at',
        'next_retrain_at',
        'is_active',
        'auto_retrain',
        'retrain_frequency_days',
        'min_accuracy_threshold',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'input_features' => 'array',
        'hyperparameters' => 'array',
        'training_data_period' => 'array',
        'validation_data_period' => 'array',
        'accuracy_score' => 'decimal:5,4',
        'precision_score' => 'decimal:5,4',
        'recall_score' => 'decimal:5,4',
        'f1_score' => 'decimal:5,4',
        'mse' => 'decimal:10,6',
        'rmse' => 'decimal:10,6',
        'mae' => 'decimal:10,6',
        'r2_score' => 'decimal:5,4',
        'training_samples' => 'integer',
        'validation_samples' => 'integer',
        'feature_importance' => 'array',
        'training_metrics' => 'array',
        'validation_metrics' => 'array',
        'last_trained_at' => 'datetime',
        'next_retrain_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_retrain' => 'boolean',
        'retrain_frequency_days' => 'integer',
        'min_accuracy_threshold' => 'decimal:5,4',
        'model_type' => ModelType::class,
        'algorithm' => Algorithm::class,
    ];

    /**
     * Get the user who created the model.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the model.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the predictions for this model.
     */
    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Get the training histories for this model.
     */
    public function trainingHistories()
    {
        return $this->hasMany(ModelTrainingHistory::class);
    }

    /**
     * Get the model performances.
     */
    public function performances()
    {
        return $this->hasMany(ModelPerformance::class);
    }

    /**
     * Scope a query to only include active models.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include models of specific type.
     */
    public function scopeByType($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope a query to only include models using specific algorithm.
     */
    public function scopeByAlgorithm($query, $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }

    /**
     * Scope a query to only include models that need retraining.
     */
    public function scopeNeedsRetraining($query)
    {
        return $query->where('next_retrain_at', '<=', now());
    }

    /**
     * Check if model is accurate enough.
     */
    public function isAccurateEnough(): bool
    {
        return $this->accuracy_score >= $this->min_accuracy_threshold;
    }

    /**
     * Check if model needs retraining.
     */
    public function needsRetraining(): bool
    {
        return $this->next_retrain_at && $this->next_retrain_at <= now();
    }

    /**
     * Check if model is ready for retraining.
     */
    public function isReadyForRetraining(): bool
    {
        return $this->auto_retrain && $this->needsRetraining();
    }

    /**
     * Get the model performance summary.
     */
    public function getPerformanceSummaryAttribute(): array
    {
        return [
            'accuracy' => $this->accuracy_score,
            'precision' => $this->precision_score,
            'recall' => $this->recall_score,
            'f1_score' => $this->f1_score,
            'mse' => $this->mse,
            'rmse' => $this->rmse,
            'mae' => $this->mae,
            'r2_score' => $this->r2_score,
            'training_samples' => $this->training_samples,
            'validation_samples' => $this->validation_samples,
            'last_trained' => $this->last_trained_at?->toISOString(),
            'next_retrain' => $this->next_retrain_at?->toISOString(),
            'is_accurate' => $this->isAccurateEnough(),
            'needs_retraining' => $this->needsRetraining(),
        ];
    }

    /**
     * Get the model configuration.
     */
    public function getConfigurationAttribute(): array
    {
        return [
            'model_type' => $this->model_type->getDisplayName(),
            'algorithm' => $this->algorithm->getDisplayName(),
            'target_variable' => $this->target_variable,
            'input_features' => $this->input_features,
            'hyperparameters' => $this->hyperparameters,
            'feature_importance' => $this->feature_importance,
            'model_version' => $this->model_version,
        ];
    }

    /**
     * Get the training status.
     */
    public function getTrainingStatusAttribute(): string
    {
        if (!$this->last_trained_at) {
            return 'not_trained';
        } elseif ($this->needsRetraining()) {
            return 'needs_retraining';
        } elseif (!$this->isAccurateEnough()) {
            return 'poor_performance';
        } else {
            return 'trained';
        }
    }

    /**
     * Get the training status display.
     */
    public function getTrainingStatusDisplayAttribute(): string
    {
        return match($this->training_status) {
            'not_trained' => 'Not Trained',
            'needs_retraining' => 'Needs Retraining',
            'poor_performance' => 'Poor Performance',
            'trained' => 'Trained',
        };
    }

    /**
     * Get the training status color.
     */
    public function getTrainingStatusColorAttribute(): string
    {
        return match($this->training_status) {
            'not_trained' => 'gray',
            'needs_retraining' => 'orange',
            'poor_performance' => 'red',
            'trained' => 'green',
        };
    }
}

/**
 * Model Type Enum
 */
enum ModelType: string
{
    case FAILURE_PREDICTION = 'failure_prediction';
    case REMAINING_USEFUL_LIFE = 'remaining_useful_life';
    case ANOMALY_DETECTION = 'anomaly_detection';
    case PREDICTIVE_MAINTENANCE = 'predictive_maintenance';
    case CONDITION_MONITORING = 'condition_monitoring';
    case ENERGY_CONSUMPTION = 'energy_consumption';
    case PERFORMANCE_DEGRADATION = 'performance_degradation';
    case OPTIMAL_MAINTENANCE = 'optimal_maintenance';

    public function getDisplayName(): string
    {
        return match($this) {
            self::FAILURE_PREDICTION => 'Failure Prediction',
            self::REMAINING_USEFUL_LIFE => 'Remaining Useful Life',
            self::ANOMALY_DETECTION => 'Anomaly Detection',
            self::PREDICTIVE_MAINTENANCE => 'Predictive Maintenance',
            self::CONDITION_MONITORING => 'Condition Monitoring',
            self::ENERGY_CONSUMPTION => 'Energy Consumption',
            self::PERFORMANCE_DEGRADATION => 'Performance Degradation',
            self::OPTIMAL_MAINTENANCE => 'Optimal Maintenance',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::FAILURE_PREDICTION => 'Predicts when equipment is likely to fail',
            self::REMAINING_USEFUL_LIFE => 'Estimates remaining operational life of equipment',
            self::ANOMALY_DETECTION => 'Identifies unusual patterns in equipment behavior',
            self::PREDICTIVE_MAINTENANCE => 'Predicts optimal maintenance timing',
            self::CONDITION_MONITORING => 'Monitors equipment condition over time',
            self::ENERGY_CONSUMPTION => 'Predicts energy usage patterns',
            self::PERFORMANCE_DEGRADATION => 'Tracks performance decline over time',
            self::OPTIMAL_MAINTENANCE => 'Optimizes maintenance schedules',
        };
    }

    public function getTargetVariables(): array
    {
        return match($this) {
            self::FAILURE_PREDICTION => ['failure_probability', 'time_to_failure'],
            self::REMAINING_USEFUL_LIFE => ['rul_days', 'rul_hours', 'rul_cycles'],
            self::ANOMALY_DETECTION => ['anomaly_score', 'is_anomaly'],
            self::PREDICTIVE_MAINTENANCE => ['maintenance_needed', 'maintenance_urgency'],
            self::CONDITION_MONITORING => ['condition_score', 'health_index'],
            self::ENERGY_CONSUMPTION => ['energy_usage', 'power_consumption'],
            self::PERFORMANCE_DEGRADATION => ['performance_score', 'efficiency'],
            self::OPTIMAL_MAINTENANCE => ['optimal_interval', 'maintenance_cost'],
        };
    }

    public function getDefaultFeatures(): array
    {
        return match($this) {
            self::FAILURE_PREDICTION => [
                'age', 'usage_hours', 'maintenance_count', 'failure_count',
                'temperature', 'vibration', 'pressure', 'load'
            ],
            self::REMAINING_USEFUL_LIFE => [
                'age', 'usage_hours', 'operating_conditions', 'maintenance_history',
                'performance_metrics', 'environmental_factors'
            ],
            self::ANOMALY_DETECTION => [
                'sensor_readings', 'operational_parameters', 'performance_metrics',
                'environmental_conditions', 'temporal_patterns'
            ],
            self::PREDICTIVE_MAINTENANCE => [
                'age', 'usage', 'maintenance_history', 'condition_indicators',
                'operational_stress', 'environmental_factors'
            ],
            self::CONDITION_MONITORING => [
                'vibration', 'temperature', 'pressure', 'noise', 'oil_analysis',
                'performance_metrics', 'operational_parameters'
            ],
            self::ENERGY_CONSUMPTION => [
                'operating_hours', 'load_factor', 'environmental_conditions',
                'equipment_status', 'time_of_day', 'seasonal_factors'
            ],
            self::PERFORMANCE_DEGRADATION => [
                'age', 'usage_hours', 'maintenance_history', 'performance_metrics',
                'operating_conditions', 'stress_factors'
            ],
            self::OPTIMAL_MAINTENANCE => [
                'age', 'usage', 'maintenance_cost', 'downtime_cost',
                'failure_probability', 'operational_conditions'
            ],
        };
    }
}

/**
 * Algorithm Enum
 */
enum Algorithm: string
{
    case RANDOM_FOREST = 'random_forest';
    case GRADIENT_BOOSTING = 'gradient_boosting';
    case LINEAR_REGRESSION = 'linear_regression';
    case LOGISTIC_REGRESSION = 'logistic_regression';
    case SUPPORT_VECTOR_MACHINE = 'support_vector_machine';
    case NEURAL_NETWORK = 'neural_network';
    case LSTM = 'lstm';
    case ARIMA = 'arima';
    case ISOLATION_FOREST = 'isolation_forest';
    case ONE_CLASS_SVM = 'one_class_svm';
    case K_MEANS = 'k_means';
    case DBSCAN = 'dbscan';
    case DECISION_TREE = 'decision_tree';
    case XGBOOST = 'xgboost';
    case LIGHTGBM = 'lightgbm';

    public function getDisplayName(): string
    {
        return match($this) {
            self::RANDOM_FOREST => 'Random Forest',
            self::GRADIENT_BOOSTING => 'Gradient Boosting',
            self::LINEAR_REGRESSION => 'Linear Regression',
            self::LOGISTIC_REGRESSION => 'Logistic Regression',
            self::SUPPORT_VECTOR_MACHINE => 'Support Vector Machine',
            self::NEURAL_NETWORK => 'Neural Network',
            self::LSTM => 'Long Short-Term Memory',
            self::ARIMA => 'ARIMA',
            self::ISOLATION_FOREST => 'Isolation Forest',
            self::ONE_CLASS_SVM => 'One-Class SVM',
            self::K_MEANS => 'K-Means',
            self::DBSCAN => 'DBSCAN',
            self::DECISION_TREE => 'Decision Tree',
            self::XGBOOST => 'XGBoost',
            self::LIGHTGBM => 'LightGBM',
        };
    }

    public function getCategory(): string
    {
        return match($this) {
            self::RANDOM_FOREST, self::GRADIENT_BOOSTING, self::DECISION_TREE, 
            self::XGBOOST, self::LIGHTGBM => 'Ensemble',
            self::LINEAR_REGRESSION, self::LOGISTIC_REGRESSION, 
            self::SUPPORT_VECTOR_MACHINE => 'Classical',
            self::NEURAL_NETWORK, self::LSTM => 'Deep Learning',
            self::ARIMA => 'Time Series',
            self::ISOLATION_FOREST, self::ONE_CLASS_SVM => 'Anomaly Detection',
            self::K_MEANS, self::DBSCAN => 'Clustering',
        };
    }

    public function getProblemTypes(): array
    {
        return match($this) {
            self::RANDOM_FOREST, self::GRADIENT_BOOSTING, self::DECISION_TREE,
            self::XGBOOST, self::LIGHTGBM, self::NEURAL_NETWORK, self::LSTM,
            self::LOGISTIC_REGRESSION, self::SUPPORT_VECTOR_MACHINE => ['classification', 'regression'],
            self::LINEAR_REGRESSION, self::ARIMA => ['regression'],
            self::ISOLATION_FOREST, self::ONE_CLASS_SVM => ['anomaly_detection'],
            self::K_MEANS, self::DBSCAN => ['clustering'],
        };
    }

    public function getDefaultHyperparameters(): array
    {
        return match($this) {
            self::RANDOM_FOREST => [
                'n_estimators' => 100,
                'max_depth' => 10,
                'min_samples_split' => 2,
                'min_samples_leaf' => 1,
                'random_state' => 42,
            ],
            self::GRADIENT_BOOSTING => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'max_depth' => 3,
                'random_state' => 42,
            ],
            self::LINEAR_REGRESSION => [
                'fit_intercept' => true,
                'normalize' => false,
            ],
            self::LOGISTIC_REGRESSION => [
                'C' => 1.0,
                'random_state' => 42,
                'max_iter' => 1000,
            ],
            self::SUPPORT_VECTOR_MACHINE => [
                'C' => 1.0,
                'kernel' => 'rbf',
                'gamma' => 'scale',
            ],
            self::NEURAL_NETWORK => [
                'hidden_layer_sizes' => [100, 50],
                'activation' => 'relu',
                'solver' => 'adam',
                'max_iter' => 1000,
                'random_state' => 42,
            ],
            self::LSTM => [
                'units' => 50,
                'dropout' => 0.2,
                'epochs' => 100,
                'batch_size' => 32,
            ],
            self::ARIMA => [
                'order' => [1, 1, 1],
                'seasonal_order' => [0, 0, 0, 0],
            ],
            self::ISOLATION_FOREST => [
                'n_estimators' => 100,
                'contamination' => 0.1,
                'random_state' => 42,
            ],
            self::ONE_CLASS_SVM => [
                'nu' => 0.1,
                'kernel' => 'rbf',
                'gamma' => 'scale',
            ],
            self::K_MEANS => [
                'n_clusters' => 3,
                'random_state' => 42,
                'n_init' => 10,
            ],
            self::DBSCAN => [
                'eps' => 0.5,
                'min_samples' => 5,
            ],
            self::DECISION_TREE => [
                'max_depth' => 10,
                'min_samples_split' => 2,
                'min_samples_leaf' => 1,
                'random_state' => 42,
            ],
            self::XGBOOST => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'max_depth' => 6,
                'random_state' => 42,
            ],
            self::LIGHTGBM => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'num_leaves' => 31,
                'random_state' => 42,
            ],
        };
    }
}
