<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Prediction extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'predictive_model_id',
        'asset_id',
        'prediction_type',
        'predicted_value',
        'confidence_score',
        'probability_distribution',
        'feature_values',
        'prediction_date',
        'target_date',
        'time_horizon_days',
        'risk_level',
        'recommendations',
        'uncertainty_bounds',
        'model_version',
        'prediction_metadata',
        'validation_status',
        'actual_value',
        'actual_date',
        'accuracy_score',
        'error_margin',
        'created_at',
    ];

    protected $casts = [
        'predicted_value' => 'decimal:15,6',
        'confidence_score' => 'decimal:5,4',
        'probability_distribution' => 'array',
        'feature_values' => 'array',
        'prediction_date' => 'date',
        'target_date' => 'date',
        'time_horizon_days' => 'integer',
        'risk_level' => RiskLevel::class,
        'recommendations' => 'array',
        'uncertainty_bounds' => 'array',
        'prediction_metadata' => 'array',
        'validation_status' => ValidationStatus::class,
        'actual_value' => 'decimal:15,6',
        'actual_date' => 'date',
        'accuracy_score' => 'decimal:5,4',
        'error_margin' => 'decimal:15,6',
        'prediction_type' => PredictionType::class,
    ];

    /**
     * Get the predictive model for this prediction.
     */
    public function predictiveModel()
    {
        return $this->belongsTo(PredictiveModel::class);
    }

    /**
     * Get the asset for this prediction.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the maintenance recommendations for this prediction.
     */
    public function maintenanceRecommendations()
    {
        return $this->hasMany(MaintenanceRecommendation::class);
    }

    /**
     * Scope a query to only include predictions of specific type.
     */
    public function scopeByType($query, $predictionType)
    {
        return $query->where('prediction_type', $predictionType);
    }

    /**
     * Scope a query to only include predictions for specific risk level.
     */
    public function scopeByRiskLevel($query, $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Scope a query to only include predictions for specific target date range.
     */
    public function scopeTargetDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('target_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include predictions that need validation.
     */
    public function scopeNeedsValidation($query)
    {
        return $query->where('validation_status', ValidationStatus::PENDING)
                    ->where('target_date', '<=', now());
    }

    /**
     * Scope a query to only include validated predictions.
     */
    public function scopeValidated($query)
    {
        return $query->whereNotNull('actual_value');
    }

    /**
     * Scope a query to only include high-risk predictions.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', RiskLevel::HIGH)
                    ->orWhere('risk_level', RiskLevel::CRITICAL);
    }

    /**
     * Check if prediction is high risk.
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [RiskLevel::HIGH, RiskLevel::CRITICAL]);
    }

    /**
     * Check if prediction is overdue for validation.
     */
    public function isOverdueForValidation(): bool
    {
        return $this->target_date && $this->target_date <= now() && 
               $this->validation_status === ValidationStatus::PENDING;
    }

    /**
     * Check if prediction has been validated.
     */
    public function isValidated(): bool
    {
        return $this->validation_status === ValidationStatus::VALIDATED;
    }

    /**
     * Check if prediction is accurate.
     */
    public function isAccurate(float $tolerance = 0.1): bool
    {
        if (!$this->isValidated()) {
            return false;
        }

        if ($this->predicted_value == 0) {
            return abs($this->error_margin) <= $tolerance;
        }

        $errorPercentage = abs($this->error_margin / $this->predicted_value);
        return $errorPercentage <= $tolerance;
    }

    /**
     * Get the days until target date.
     */
    public function getDaysUntilTargetAttribute(): int
    {
        if (!$this->target_date) {
            return 0;
        }

        return now()->diffInDays($this->target_date, false);
    }

    /**
     * Get the prediction status.
     */
    public function getPredictionStatusAttribute(): string
    {
        if ($this->isValidated()) {
            return 'validated';
        } elseif ($this->isOverdueForValidation()) {
            return 'overdue';
        } elseif ($this->days_until_target <= 0) {
            return 'expired';
        } elseif ($this->days_until_target <= 7) {
            return 'imminent';
        } else {
            return 'pending';
        }
    }

    /**
     * Get the prediction status display.
     */
    public function getPredictionStatusDisplayAttribute(): string
    {
        return match($this->prediction_status) {
            'validated' => 'Validated',
            'overdue' => 'Overdue',
            'expired' => 'Expired',
            'imminent' => 'Imminent',
            'pending' => 'Pending',
        };
    }

    /**
     * Get the prediction status color.
     */
    public function getPredictionStatusColorAttribute(): string
    {
        return match($this->prediction_status) {
            'validated' => 'green',
            'overdue' => 'red',
            'expired' => 'gray',
            'imminent' => 'orange',
            'pending' => 'blue',
        };
    }

    /**
     * Get the risk level display.
     */
    public function getRiskLevelDisplayAttribute(): string
    {
        return $this->risk_level->getDisplayName();
    }

    /**
     * Get the risk level color.
     */
    public function getRiskLevelColorAttribute(): string
    {
        return $this->risk_level->getColor();
    }

    /**
     * Get the prediction type display.
     */
    public function getPredictionTypeDisplayAttribute(): string
    {
        return $this->prediction_type->getDisplayName();
    }

    /**
     * Get the formatted predicted value.
     */
    public function getFormattedPredictedValueAttribute(): string
    {
        return number_format($this->predicted_value, 2);
    }

    /**
     * Get the formatted confidence score.
     */
    public function getFormattedConfidenceScoreAttribute(): string
    {
        return number_format($this->confidence_score * 100, 1) . '%';
    }

    /**
     * Get the uncertainty range.
     */
    public function getUncertaintyRangeAttribute(): array
    {
        if (!$this->uncertainty_bounds) {
            return [];
        }

        return [
            'lower_bound' => $this->uncertainty_bounds['lower_bound'] ?? null,
            'upper_bound' => $this->uncertainty_bounds['upper_bound'] ?? null,
            'range' => ($this->uncertainty_bounds['upper_bound'] ?? 0) - ($this->uncertainty_bounds['lower_bound'] ?? 0),
        ];
    }

    /**
     * Validate the prediction with actual value.
     */
    public function validate(float $actualValue, Carbon $actualDate = null): void
    {
        $this->update([
            'actual_value' => $actualValue,
            'actual_date' => $actualDate ?? now(),
            'error_margin' => $actualValue - $this->predicted_value,
            'accuracy_score' => $this->calculateAccuracy($actualValue),
            'validation_status' => ValidationStatus::VALIDATED,
        ]);
    }

    /**
     * Calculate accuracy score.
     */
    private function calculateAccuracy(float $actualValue): float
    {
        if ($this->predicted_value == 0) {
            return 1.0 - min(abs($actualValue), 1.0);
        }

        $error = abs($actualValue - $this->predicted_value);
        $relativeError = $error / abs($this->predicted_value);
        
        return max(0, 1 - $relativeError);
    }

    /**
     * Get prediction summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'asset_name' => $this->asset->name,
            'model_name' => $this->predictiveModel->name,
            'prediction_type' => $this->prediction_type_display,
            'predicted_value' => $this->formatted_predicted_value,
            'confidence_score' => $this->formatted_confidence_score,
            'risk_level' => $this->risk_level_display,
            'risk_color' => $this->risk_level_color,
            'prediction_date' => $this->prediction_date->format('Y-m-d'),
            'target_date' => $this->target_date->format('Y-m-d'),
            'days_until_target' => $this->days_until_target,
            'status' => $this->prediction_status_display,
            'status_color' => $this->prediction_status_color,
            'is_validated' => $this->isValidated(),
            'actual_value' => $this->actual_value ? number_format($this->actual_value, 2) : null,
            'accuracy_score' => $this->accuracy_score ? number_format($this->accuracy_score * 100, 1) . '%' : null,
            'recommendations' => $this->recommendations,
        ];
    }

    /**
     * Generate maintenance recommendations based on prediction.
     */
    public function generateMaintenanceRecommendations(): array
    {
        $recommendations = [];

        if ($this->isHighRisk()) {
            $urgency = $this->risk_level === RiskLevel::CRITICAL ? 'immediate' : 'urgent';
            
            $recommendations[] = [
                'type' => 'inspection',
                'urgency' => $urgency,
                'description' => 'Schedule immediate inspection due to high-risk prediction',
                'estimated_cost' => $this->estimateInspectionCost(),
                'recommended_date' => now()->addDays($this->risk_level === RiskLevel::CRITICAL ? 1 : 3),
            ];

            $recommendations[] = [
                'type' => 'preventive_maintenance',
                'urgency' => $urgency,
                'description' => 'Perform preventive maintenance to prevent predicted failure',
                'estimated_cost' => $this->estimateMaintenanceCost(),
                'recommended_date' => now()->addDays($this->risk_level === RiskLevel::CRITICAL ? 3 : 7),
            ];
        }

        if ($this->prediction_type === PredictionType::FAILURE_PREDICTION && 
            $this->predicted_value > 0.7) {
            $recommendations[] = [
                'type' => 'replacement',
                'urgency' => 'medium',
                'description' => 'Plan for equipment replacement based on failure prediction',
                'estimated_cost' => $this->estimateReplacementCost(),
                'recommended_date' => now()->addDays($this->days_until_target),
            ];
        }

        if ($this->prediction_type === PredictionType::REMAINING_USEFUL_LIFE && 
            $this->predicted_value < 30) {
            $recommendations[] = [
                'type' => 'replacement_planning',
                'urgency' => 'medium',
                'description' => 'Begin planning for equipment replacement',
                'estimated_cost' => $this->estimateReplacementCost(),
                'recommended_date' => now()->addDays(30),
            ];
        }

        return $recommendations;
    }

    /**
     * Estimate inspection cost.
     */
    private function estimateInspectionCost(): float
    {
        return match($this->risk_level) {
            RiskLevel::CRITICAL => 500.0,
            RiskLevel::HIGH => 300.0,
            RiskLevel::MEDIUM => 200.0,
            RiskLevel::LOW => 100.0,
            RiskLevel::VERY_LOW => 50.0,
        };
    }

    /**
     * Estimate maintenance cost.
     */
    private function estimateMaintenanceCost(): float
    {
        $baseCost = $this->asset->purchase_cost * 0.05; // 5% of asset value
        
        return match($this->risk_level) {
            RiskLevel::CRITICAL => $baseCost * 2,
            RiskLevel::HIGH => $baseCost * 1.5,
            RiskLevel::MEDIUM => $baseCost,
            RiskLevel::LOW => $baseCost * 0.7,
            RiskLevel::VERY_LOW => $baseCost * 0.5,
        };
    }

    /**
     * Estimate replacement cost.
     */
    private function estimateReplacementCost(): float
    {
        return $this->asset->purchase_cost * 0.8; // 80% of original cost
    }
}

/**
 * Prediction Type Enum
 */
enum PredictionType: string
{
    case FAILURE_PROBABILITY = 'failure_probability';
    case TIME_TO_FAILURE = 'time_to_failure';
    case REMAINING_USEFUL_LIFE = 'remaining_useful_life';
    case MAINTENANCE_NEEDED = 'maintenance_needed';
    case PERFORMANCE_DEGRADATION = 'performance_degradation';
    case ENERGY_CONSUMPTION = 'energy_consumption';
    case ANOMALY_LIKELIHOOD = 'anomaly_likelihood';
    case OPTIMAL_MAINTENANCE_INTERVAL = 'optimal_maintenance_interval';

    public function getDisplayName(): string
    {
        return match($this) {
            self::FAILURE_PROBABILITY => 'Failure Probability',
            self::TIME_TO_FAILURE => 'Time to Failure',
            self::REMAINING_USEFUL_LIFE => 'Remaining Useful Life',
            self::MAINTENANCE_NEEDED => 'Maintenance Needed',
            self::PERFORMANCE_DEGRADATION => 'Performance Degradation',
            self::ENERGY_CONSUMPTION => 'Energy Consumption',
            self::ANOMALY_LIKELIHOOD => 'Anomaly Likelihood',
            self::OPTIMAL_MAINTENANCE_INTERVAL => 'Optimal Maintenance Interval',
        };
    }

    public function getUnit(): string
    {
        return match($this) {
            self::FAILURE_PROBABILITY => '%',
            self::TIME_TO_FAILURE => 'days',
            self::REMAINING_USEFUL_LIFE => 'days',
            self::MAINTENANCE_NEEDED => 'boolean',
            self::PERFORMANCE_DEGRADATION => '%',
            self::ENERGY_CONSUMPTION => 'kWh',
            self::ANOMALY_LIKELIHOOD => '%',
            self::OPTIMAL_MAINTENANCE_INTERVAL => 'days',
        };
    }
}

/**
 * Risk Level Enum
 */
enum RiskLevel: string
{
    case VERY_LOW = 'very_low';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getDisplayName(): string
    {
        return match($this) {
            self::VERY_LOW => 'Very Low',
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::VERY_LOW => 'green',
            self::LOW => 'blue',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }

    public function getNumericValue(): int
    {
        return match($this) {
            self::VERY_LOW => 1,
            self::LOW => 2,
            self::MEDIUM => 3,
            self::HIGH => 4,
            self::CRITICAL => 5,
        };
    }

    public function requiresImmediateAction(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL]);
    }

    public function getActionTimeframe(): string
    {
        return match($this) {
            self::VERY_LOW => '6 months',
            self::LOW => '3 months',
            self::MEDIUM => '1 month',
            self::HIGH => '1 week',
            self::CRITICAL => '24 hours',
        };
    }
}

/**
 * Validation Status Enum
 */
enum ValidationStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case INVALIDATED = 'invalidated';
    case EXPIRED = 'expired';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::VALIDATED => 'Validated',
            self::INVALIDATED => 'Invalidated',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'blue',
            self::VALIDATED => 'green',
            self::INVALIDATED => 'red',
            self::EXPIRED => 'gray',
        };
    }

    public function isComplete(): bool
    {
        return in_array($this, [self::VALIDATED, self::INVALIDATED]);
    }
}
