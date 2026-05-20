<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ModelPerformance extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'predictive_model_id',
        'evaluation_date',
        'evaluation_type',
        'dataset_type',
        'sample_count',
        'accuracy_score',
        'precision_score',
        'recall_score',
        'f1_score',
        'mse',
        'rmse',
        'mae',
        'r2_score',
        'auc_score',
        'log_loss',
        'confusion_matrix',
        'classification_report',
        'feature_importance',
        'prediction_distribution',
        'error_distribution',
        'calibration_curve',
        'roc_curve',
        'precision_recall_curve',
        'feature_contributions',
        'model_drift_detected',
        'drift_metrics',
        'performance_degradation',
        'comparison_to_baseline',
        'statistical_significance',
        'cross_validation_scores',
        'evaluation_metadata',
        'created_by',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'sample_count' => 'integer',
        'accuracy_score' => 'decimal:5,4',
        'precision_score' => 'decimal:5,4',
        'recall_score' => 'decimal:5,4',
        'f1_score' => 'decimal:5,4',
        'mse' => 'decimal:15,6',
        'rmse' => 'decimal:10,6',
        'mae' => 'decimal:10,6',
        'r2_score' => 'decimal:5,4',
        'auc_score' => 'decimal:5,4',
        'log_loss' => 'decimal:10,6',
        'confusion_matrix' => 'array',
        'classification_report' => 'array',
        'feature_importance' => 'array',
        'prediction_distribution' => 'array',
        'error_distribution' => 'array',
        'calibration_curve' => 'array',
        'roc_curve' => 'array',
        'precision_recall_curve' => 'array',
        'feature_contributions' => 'array',
        'model_drift_detected' => 'boolean',
        'drift_metrics' => 'array',
        'performance_degradation' => 'array',
        'comparison_to_baseline' => 'array',
        'statistical_significance' => 'array',
        'cross_validation_scores' => 'array',
        'evaluation_metadata' => 'array',
        'evaluation_type' => EvaluationType::class,
        'dataset_type' => DatasetType::class,
    ];

    /**
     * Get the predictive model for this performance evaluation.
     */
    public function predictiveModel()
    {
        return $this->belongsTo(PredictiveModel::class);
    }

    /**
     * Get the user who created the performance evaluation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include evaluations of specific type.
     */
    public function scopeByType($query, $evaluationType)
    {
        return $query->where('evaluation_type', $evaluationType);
    }

    /**
     * Scope a query to only include evaluations on specific dataset type.
     */
    public function scopeByDatasetType($query, $datasetType)
    {
        return $query->where('dataset_type', $datasetType);
    }

    /**
     * Scope a query to only include evaluations within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('evaluation_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include recent evaluations.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('evaluation_date', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include evaluations with model drift detected.
     */
    public function scopeWithDrift($query)
    {
        return $query->where('model_drift_detected', true);
    }

    /**
     * Check if model performance is good.
     */
    public function isGoodPerformance(): bool
    {
        return $this->accuracy_score >= 0.8 && 
               $this->f1_score >= 0.8 && 
               (!$this->r2_score || $this->r2_score >= 0.7);
    }

    /**
     * Check if model performance is poor.
     */
    public function isPoorPerformance(): bool
    {
        return $this->accuracy_score < 0.6 || 
               $this->f1_score < 0.6 || 
               ($this->r2_score && $this->r2_score < 0.5);
    }

    /**
     * Check if model drift was detected.
     */
    public function hasModelDrift(): bool
    {
        return $this->model_drift_detected;
    }

    /**
     * Get the evaluation type display.
     */
    public function getEvaluationTypeDisplayAttribute(): string
    {
        return $this->evaluation_type->getDisplayName();
    }

    /**
     * Get the dataset type display.
     */
    public function getDatasetTypeDisplayAttribute(): string
    {
        return $this->dataset_type->getDisplayName();
    }

    /**
     * Get the formatted accuracy score.
     */
    public function getFormattedAccuracyScoreAttribute(): string
    {
        return number_format($this->accuracy_score * 100, 2) . '%';
    }

    /**
     * Get the formatted F1 score.
     */
    public function getFormattedF1ScoreAttribute(): string
    {
        return number_format($this->f1_score * 100, 2) . '%';
    }

    /**
     * Get the formatted R2 score.
     */
    public function getFormattedR2ScoreAttribute(): string
    {
        return $this->r2_score ? number_format($this->r2_score * 100, 2) . '%' : 'N/A';
    }

    /**
     * Get the performance grade.
     */
    public function getPerformanceGradeAttribute(): string
    {
        $score = ($this->accuracy_score + $this->f1_score) / 2;

        if ($score >= 0.95) return 'A+';
        if ($score >= 0.90) return 'A';
        if ($score >= 0.85) return 'B+';
        if ($score >= 0.80) return 'B';
        if ($score >= 0.75) return 'C+';
        if ($score >= 0.70) return 'C';
        if ($score >= 0.65) return 'D+';
        if ($score >= 0.60) return 'D';
        return 'F';
    }

    /**
     * Get the performance grade color.
     */
    public function getPerformanceGradeColorAttribute(): string
    {
        $grade = $this->performance_grade;

        return match($grade) {
            'A+', 'A' => 'green',
            'B+', 'B' => 'blue',
            'C+', 'C' => 'yellow',
            'D+', 'D' => 'orange',
            'F' => 'red',
        };
    }

    /**
     * Get the performance summary.
     */
    public function getPerformanceSummaryAttribute(): array
    {
        return [
            'evaluation_date' => $this->evaluation_date->format('Y-m-d'),
            'evaluation_type' => $this->evaluation_type_display,
            'dataset_type' => $this->dataset_type_display,
            'sample_count' => $this->sample_count,
            'metrics' => [
                'accuracy' => $this->formatted_accuracy_score,
                'precision' => $this->precision_score ? number_format($this->precision_score * 100, 2) . '%' : 'N/A',
                'recall' => $this->recall_score ? number_format($this->recall_score * 100, 2) . '%' : 'N/A',
                'f1_score' => $this->formatted_f1_score,
                'r2_score' => $this->formatted_r2_score,
                'mse' => $this->mse ? number_format($this->mse, 6) : 'N/A',
                'rmse' => $this->rmse ? number_format($this->rmse, 6) : 'N/A',
                'mae' => $this->mae ? number_format($this->mae, 6) : 'N/A',
            ],
            'grade' => $this->performance_grade,
            'grade_color' => $this->performance_grade_color,
            'is_good_performance' => $this->isGoodPerformance(),
            'is_poor_performance' => $this->isPoorPerformance(),
            'has_drift' => $this->hasModelDrift(),
        ];
    }

    /**
     * Get the detailed metrics.
     */
    public function getDetailedMetricsAttribute(): array
    {
        return [
            'classification_metrics' => [
                'accuracy' => $this->accuracy_score,
                'precision' => $this->precision_score,
                'recall' => $this->recall_score,
                'f1_score' => $this->f1_score,
                'auc_score' => $this->auc_score,
                'log_loss' => $this->log_loss,
            ],
            'regression_metrics' => [
                'mse' => $this->mse,
                'rmse' => $this->rmse,
                'mae' => $this->mae,
                'r2_score' => $this->r2_score,
            ],
            'confusion_matrix' => $this->confusion_matrix,
            'classification_report' => $this->classification_report,
            'feature_importance' => $this->feature_importance,
            'prediction_distribution' => $this->prediction_distribution,
            'error_distribution' => $this->error_distribution,
        ];
    }

    /**
     * Get the drift analysis.
     */
    public function getDriftAnalysisAttribute(): array
    {
        if (!$this->hasModelDrift()) {
            return [
                'drift_detected' => false,
                'drift_metrics' => [],
            ];
        }

        return [
            'drift_detected' => true,
            'drift_metrics' => $this->drift_metrics,
            'performance_degradation' => $this->performance_degradation,
            'recommendations' => $this->generateDriftRecommendations(),
        ];
    }

    /**
     * Generate drift recommendations.
     */
    private function generateDriftRecommendations(): array
    {
        $recommendations = [];

        if ($this->performance_degradation['accuracy_degradation'] ?? 0 > 0.1) {
            $recommendations[] = [
                'type' => 'retrain',
                'priority' => 'high',
                'description' => 'Model accuracy has degraded significantly. Retraining recommended.',
                'action' => 'Retrain model with recent data',
            ];
        }

        if ($this->performance_degradation['feature_drift'] ?? false) {
            $recommendations[] = [
                'type' => 'feature_update',
                'priority' => 'medium',
                'description' => 'Feature distribution has changed. Update feature engineering.',
                'action' => 'Review and update feature selection and engineering',
            ];
        }

        if ($this->performance_degradation['data_drift'] ?? false) {
            $recommendations[] = [
                'type' => 'data_refresh',
                'priority' => 'medium',
                'description' => 'Data distribution has shifted. Refresh training data.',
                'action' => 'Update training dataset with recent data',
            ];
        }

        return $recommendations;
    }

    /**
     * Compare with another performance evaluation.
     */
    public function compareTo(ModelPerformance $other): array
    {
        $comparison = [];

        // Accuracy comparison
        if ($this->accuracy_score && $other->accuracy_score) {
            $comparison['accuracy'] = [
                'current' => $this->accuracy_score,
                'other' => $other->accuracy_score,
                'difference' => $this->accuracy_score - $other->accuracy_score,
                'improvement' => ($this->accuracy_score - $other->accuracy_score) / $other->accuracy_score * 100,
            ];
        }

        // F1 score comparison
        if ($this->f1_score && $other->f1_score) {
            $comparison['f1_score'] = [
                'current' => $this->f1_score,
                'other' => $other->f1_score,
                'difference' => $this->f1_score - $other->f1_score,
                'improvement' => ($this->f1_score - $other->f1_score) / $other->f1_score * 100,
            ];
        }

        // MSE comparison (lower is better)
        if ($this->mse && $other->mse) {
            $comparison['mse'] = [
                'current' => $this->mse,
                'other' => $other->mse,
                'difference' => $other->mse - $this->mse,
                'improvement' => (($other->mse - $this->mse) / $other->mse) * 100,
            ];
        }

        return $comparison;
    }
}

/**
 * Evaluation Type Enum
 */
enum EvaluationType: string
{
    case VALIDATION = 'validation';
    case TESTING = 'testing';
    case CROSS_VALIDATION = 'cross_validation';
    case PRODUCTION = 'production';
    case DRIFT_DETECTION = 'drift_detection';
    case BENCHMARK = 'benchmark';
    case A_B_TESTING = 'a_b_testing';

    public function getDisplayName(): string
    {
        return match($this) {
            self::VALIDATION => 'Validation',
            self::TESTING => 'Testing',
            self::CROSS_VALIDATION => 'Cross Validation',
            self::PRODUCTION => 'Production',
            self::DRIFT_DETECTION => 'Drift Detection',
            self::BENCHMARK => 'Benchmark',
            self::A_B_TESTING => 'A/B Testing',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::VALIDATION => 'Model evaluation on validation dataset',
            self::TESTING => 'Model evaluation on test dataset',
            self::CROSS_VALIDATION => 'Cross-validation evaluation',
            self::PRODUCTION => 'Production performance monitoring',
            self::DRIFT_DETECTION => 'Model drift detection analysis',
            self::BENCHMARK => 'Benchmark comparison evaluation',
            self::A_B_TESTING => 'A/B testing comparison',
        };
    }

    public function getReliability(): string
    {
        return match($this) {
            self::VALIDATION => 'Medium',
            self::TESTING => 'High',
            self::CROSS_VALIDATION => 'Very High',
            self::PRODUCTION => 'Real-time',
            self::DRIFT_DETECTION => 'Diagnostic',
            self::BENCHMARK => 'Comparative',
            self::A_B_TESTING => 'Experimental',
        };
    }
}

/**
 * Dataset Type Enum
 */
enum DatasetType: string
{
    case TRAINING = 'training';
    case VALIDATION = 'validation';
    case TESTING = 'testing';
    case PRODUCTION = 'production';
    case SYNTHETIC = 'synthetic';
    case HISTORICAL = 'historical';
    case REAL_TIME = 'real_time';

    public function getDisplayName(): string
    {
        return match($this) {
            self::TRAINING => 'Training',
            self::VALIDATION => 'Validation',
            self::TESTING => 'Testing',
            self::PRODUCTION => 'Production',
            self::SYNTHETIC => 'Synthetic',
            self::HISTORICAL => 'Historical',
            self::REAL_TIME => 'Real-time',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::TRAINING => 'Dataset used for model training',
            self::VALIDATION => 'Dataset used for model validation',
            self::TESTING => 'Dataset used for model testing',
            self::PRODUCTION => 'Real production data',
            self::SYNTHETIC => 'Synthetically generated data',
            self::HISTORICAL => 'Historical archived data',
            self::REAL_TIME => 'Real-time streaming data',
        };
    }

    public function getQuality(): string
    {
        return match($this) {
            self::TRAINING => 'High',
            self::VALIDATION => 'High',
            self::TESTING => 'High',
            self::PRODUCTION => 'Variable',
            self::SYNTHETIC => 'Controlled',
            self::HISTORICAL => 'Archived',
            self::REAL_TIME => 'Current',
        };
    }
}
