<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ModelTrainingHistory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'predictive_model_id',
        'training_version',
        'training_started_at',
        'training_completed_at',
        'training_duration_seconds',
        'training_samples',
        'validation_samples',
        'test_samples',
        'training_loss',
        'validation_loss',
        'test_loss',
        'training_accuracy',
        'validation_accuracy',
        'test_accuracy',
        'training_precision',
        'validation_precision',
        'test_precision',
        'training_recall',
        'validation_recall',
        'test_recall',
        'training_f1_score',
        'validation_f1_score',
        'test_f1_score',
        'training_mse',
        'validation_mse',
        'test_mse',
        'training_rmse',
        'validation_rmse',
        'test_rmse',
        'training_mae',
        'validation_mae',
        'test_mae',
        'training_r2_score',
        'validation_r2_score',
        'test_r2_score',
        'feature_importance',
        'confusion_matrix',
        'classification_report',
        'hyperparameters_used',
        'training_data_info',
        'validation_data_info',
        'model_metrics',
        'training_log',
        'improvement_over_previous',
        'training_status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'training_started_at' => 'datetime',
        'training_completed_at' => 'datetime',
        'training_duration_seconds' => 'integer',
        'training_samples' => 'integer',
        'validation_samples' => 'integer',
        'test_samples' => 'integer',
        'training_loss' => 'decimal:10,8',
        'validation_loss' => 'decimal:10,8',
        'test_loss' => 'decimal:10,8',
        'training_accuracy' => 'decimal:5,4',
        'validation_accuracy' => 'decimal:5,4',
        'test_accuracy' => 'decimal:5,4',
        'training_precision' => 'decimal:5,4',
        'validation_precision' => 'decimal:5,4',
        'test_precision' => 'decimal:5,4',
        'training_recall' => 'decimal:5,4',
        'validation_recall' => 'decimal:5,4',
        'test_recall' => 'decimal:5,4',
        'training_f1_score' => 'decimal:5,4',
        'validation_f1_score' => 'decimal:5,4',
        'test_f1_score' => 'decimal:5,4',
        'training_mse' => 'decimal:15,6',
        'validation_mse' => 'decimal:15,6',
        'test_mse' => 'decimal:15,6',
        'training_rmse' => 'decimal:10,6',
        'validation_rmse' => 'decimal:10,6',
        'test_rmse' => 'decimal:10,6',
        'training_mae' => 'decimal:10,6',
        'validation_mae' => 'decimal:10,6',
        'test_mae' => 'decimal:10,6',
        'training_r2_score' => 'decimal:5,4',
        'validation_r2_score' => 'decimal:5,4',
        'test_r2_score' => 'decimal:5,4',
        'feature_importance' => 'array',
        'confusion_matrix' => 'array',
        'classification_report' => 'array',
        'hyperparameters_used' => 'array',
        'training_data_info' => 'array',
        'validation_data_info' => 'array',
        'model_metrics' => 'array',
        'training_log' => 'array',
        'improvement_over_previous' => 'array',
        'training_status' => TrainingStatus::class,
    ];

    /**
     * Get the predictive model for this training history.
     */
    public function predictiveModel()
    {
        return $this->belongsTo(PredictiveModel::class);
    }

    /**
     * Get the user who created the training history.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include successful trainings.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('training_status', TrainingStatus::COMPLETED);
    }

    /**
     * Scope a query to only include failed trainings.
     */
    public function scopeFailed($query)
    {
        return $query->where('training_status', TrainingStatus::FAILED);
    }

    /**
     * Scope a query to only include in-progress trainings.
     */
    public function scopeInProgress($query)
    {
        return $query->where('training_status', TrainingStatus::IN_PROGRESS);
    }

    /**
     * Check if training was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->training_status === TrainingStatus::COMPLETED;
    }

    /**
     * Check if training failed.
     */
    public function isFailed(): bool
    {
        return $this->training_status === TrainingStatus::FAILED;
    }

    /**
     * Check if training is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->training_status === TrainingStatus::IN_PROGRESS;
    }

    /**
     * Get the training status display.
     */
    public function getTrainingStatusDisplayAttribute(): string
    {
        return $this->training_status->getDisplayName();
    }

    /**
     * Get the training status color.
     */
    public function getTrainingStatusColorAttribute(): string
    {
        return $this->training_status->getColor();
    }

    /**
     * Get the training duration.
     */
    public function getTrainingDurationAttribute(): string
    {
        if (!$this->training_duration_seconds) {
            return 'N/A';
        }

        $hours = floor($this->training_duration_seconds / 3600);
        $minutes = floor(($this->training_duration_seconds % 3600) / 60);
        $seconds = $this->training_duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Get the best accuracy score.
     */
    public function getBestAccuracyAttribute(): float
    {
        return max(
            $this->training_accuracy ?? 0,
            $this->validation_accuracy ?? 0,
            $this->test_accuracy ?? 0
        );
    }

    /**
     * Get the best F1 score.
     */
    public function getBestF1ScoreAttribute(): float
    {
        return max(
            $this->training_f1_score ?? 0,
            $this->validation_f1_score ?? 0,
            $this->test_f1_score ?? 0
        );
    }

    /**
     * Get the best R2 score.
     */
    public function getBestR2ScoreAttribute(): float
    {
        return max(
            $this->training_r2_score ?? 0,
            $this->validation_r2_score ?? 0,
            $this->test_r2_score ?? 0
        );
    }

    /**
     * Get the lowest loss.
     */
    public function getLowestLossAttribute(): float
    {
        return min(
            $this->training_loss ?? PHP_FLOAT_MAX,
            $this->validation_loss ?? PHP_FLOAT_MAX,
            $this->test_loss ?? PHP_FLOAT_MAX
        );
    }

    /**
     * Get the lowest MSE.
     */
    public function getLowestMseAttribute(): float
    {
        return min(
            $this->training_mse ?? PHP_FLOAT_MAX,
            $this->validation_mse ?? PHP_FLOAT_MAX,
            $this->test_mse ?? PHP_FLOAT_MAX
        );
    }

    /**
     * Get the training summary.
     */
    public function getTrainingSummaryAttribute(): array
    {
        return [
            'version' => $this->training_version,
            'status' => $this->training_status_display,
            'status_color' => $this->training_status_color,
            'duration' => $this->training_duration,
            'samples' => [
                'training' => $this->training_samples,
                'validation' => $this->validation_samples,
                'test' => $this->test_samples,
            ],
            'metrics' => [
                'best_accuracy' => $this->best_accuracy,
                'best_f1_score' => $this->best_f1_score,
                'best_r2_score' => $this->best_r2_score,
                'lowest_loss' => $this->lowest_loss,
                'lowest_mse' => $this->lowest_mse,
            ],
            'improvement' => $this->improvement_over_previous,
            'started_at' => $this->training_started_at?->toISOString(),
            'completed_at' => $this->training_completed_at?->toISOString(),
            'has_error' => $this->isFailed() && $this->error_message,
        ];
    }

    /**
     * Get the performance comparison.
     */
    public function getPerformanceComparisonAttribute(): array
    {
        return [
            'training' => [
                'accuracy' => $this->training_accuracy,
                'precision' => $this->training_precision,
                'recall' => $this->training_recall,
                'f1_score' => $this->training_f1_score,
                'mse' => $this->training_mse,
                'rmse' => $this->training_rmse,
                'mae' => $this->training_mae,
                'r2_score' => $this->training_r2_score,
            ],
            'validation' => [
                'accuracy' => $this->validation_accuracy,
                'precision' => $this->validation_precision,
                'recall' => $this->validation_recall,
                'f1_score' => $this->validation_f1_score,
                'mse' => $this->validation_mse,
                'rmse' => $this->validation_rmse,
                'mae' => $this->validation_mae,
                'r2_score' => $this->validation_r2_score,
            ],
            'test' => [
                'accuracy' => $this->test_accuracy,
                'precision' => $this->test_precision,
                'recall' => $this->test_recall,
                'f1_score' => $this->test_f1_score,
                'mse' => $this->test_mse,
                'rmse' => $this->test_rmse,
                'mae' => $this->test_mae,
                'r2_score' => $this->test_r2_score,
            ],
        ];
    }

    /**
     * Calculate improvement over previous training.
     */
    public function calculateImprovement(ModelTrainingHistory $previous): array
    {
        $improvement = [];

        // Calculate accuracy improvement
        if ($previous->validation_accuracy && $this->validation_accuracy) {
            $improvement['accuracy'] = [
                'absolute' => $this->validation_accuracy - $previous->validation_accuracy,
                'percentage' => (($this->validation_accuracy - $previous->validation_accuracy) / $previous->validation_accuracy) * 100,
            ];
        }

        // Calculate F1 score improvement
        if ($previous->validation_f1_score && $this->validation_f1_score) {
            $improvement['f1_score'] = [
                'absolute' => $this->validation_f1_score - $previous->validation_f1_score,
                'percentage' => (($this->validation_f1_score - $previous->validation_f1_score) / $previous->validation_f1_score) * 100,
            ];
        }

        // Calculate MSE improvement (lower is better)
        if ($previous->validation_mse && $this->validation_mse) {
            $improvement['mse'] = [
                'absolute' => $previous->validation_mse - $this->validation_mse,
                'percentage' => (($previous->validation_mse - $this->validation_mse) / $previous->validation_mse) * 100,
            ];
        }

        return $improvement;
    }
}

/**
 * Training Status Enum
 */
enum TrainingStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'blue',
            self::IN_PROGRESS => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS]);
    }

    public function isComplete(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }
}
