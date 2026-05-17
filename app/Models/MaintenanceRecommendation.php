<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceRecommendation extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'prediction_id',
        'asset_id',
        'recommendation_type',
        'urgency',
        'description',
        'detailed_description',
        'estimated_cost',
        'estimated_duration_hours',
        'recommended_date',
        'deadline_date',
        'required_parts',
        'required_skills',
        'safety_requirements',
        'impact_assessment',
        'cost_benefit_analysis',
        'alternative_options',
        'implementation_plan',
        'status',
        'assigned_to',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'work_order_id',
        'completed_at',
        'completion_notes',
        'actual_cost',
        'actual_duration_hours',
        'effectiveness_rating',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:10,2',
        'estimated_duration_hours' => 'decimal:5,2',
        'recommended_date' => 'date',
        'deadline_date' => 'date',
        'required_parts' => 'array',
        'required_skills' => 'array',
        'safety_requirements' => 'array',
        'impact_assessment' => 'array',
        'cost_benefit_analysis' => 'array',
        'alternative_options' => 'array',
        'implementation_plan' => 'array',
        'status' => RecommendationStatus::class,
        'urgency' => Urgency::class,
        'recommendation_type' => RecommendationType::class,
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'actual_cost' => 'decimal:10,2',
        'actual_duration_hours' => 'decimal:5,2',
        'effectiveness_rating' => 'decimal:3,2',
    ];

    /**
     * Get the prediction for this recommendation.
     */
    public function prediction()
    {
        return $this->belongsTo(Prediction::class);
    }

    /**
     * Get the asset for this recommendation.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user who created the recommendation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the recommendation.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user assigned to this recommendation.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who approved this recommendation.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this recommendation.
     */
    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the work order for this recommendation.
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Scope a query to only include recommendations of specific type.
     */
    public function scopeByType($query, $recommendationType)
    {
        return $query->where('recommendation_type', $recommendationType);
    }

    /**
     * Scope a query to only include recommendations with specific urgency.
     */
    public function scopeByUrgency($query, $urgency)
    {
        return $query->where('urgency', $urgency);
    }

    /**
     * Scope a query to only include recommendations with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending recommendations.
     */
    public function scopePending($query)
    {
        return $query->where('status', RecommendationStatus::PENDING);
    }

    /**
     * Scope a query to only include approved recommendations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', RecommendationStatus::APPROVED);
    }

    /**
     * Scope a query to only include rejected recommendations.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', RecommendationStatus::REJECTED);
    }

    /**
     * Scope a query to only include completed recommendations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', RecommendationStatus::COMPLETED);
    }

    /**
     * Scope a query to only include overdue recommendations.
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_date', '<', now())
                    ->whereIn('status', [RecommendationStatus::PENDING, RecommendationStatus::APPROVED]);
    }

    /**
     * Scope a query to only include urgent recommendations.
     */
    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', [Urgency::CRITICAL, Urgency::HIGH]);
    }

    /**
     * Check if recommendation is urgent.
     */
    public function isUrgent(): bool
    {
        return in_array($this->urgency, [Urgency::CRITICAL, Urgency::HIGH]);
    }

    /**
     * Check if recommendation is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->deadline_date && $this->deadline_date < now() && 
               in_array($this->status, [RecommendationStatus::PENDING, RecommendationStatus::APPROVED]);
    }

    /**
     * Check if recommendation is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === RecommendationStatus::APPROVED;
    }

    /**
     * Check if recommendation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === RecommendationStatus::COMPLETED;
    }

    /**
     * Get the days until deadline.
     */
    public function getDaysUntilDeadlineAttribute(): int
    {
        if (!$this->deadline_date) {
            return 0;
        }

        return now()->diffInDays($this->deadline_date, false);
    }

    /**
     * Get the recommendation status display.
     */
    public function getStatusDisplayAttribute(): string
    {
        return $this->status->getDisplayName();
    }

    /**
     * Get the recommendation status color.
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    /**
     * Get the urgency display.
     */
    public function getUrgencyDisplayAttribute(): string
    {
        return $this->urgency->getDisplayName();
    }

    /**
     * Get the urgency color.
     */
    public function getUrgencyColorAttribute(): string
    {
        return $this->urgency->getColor();
    }

    /**
     * Get the recommendation type display.
     */
    public function getRecommendationTypeDisplayAttribute(): string
    {
        return $this->recommendation_type->getDisplayName();
    }

    /**
     * Get the formatted estimated cost.
     */
    public function getFormattedEstimatedCostAttribute(): string
    {
        return number_format($this->estimated_cost, 2);
    }

    /**
     * Get the formatted actual cost.
     */
    public function getFormattedActualCostAttribute(): string
    {
        return $this->actual_cost ? number_format($this->actual_cost, 2) : 'N/A';
    }

    /**
     * Get the cost variance.
     */
    public function getCostVarianceAttribute(): ?float
    {
        if (!$this->actual_cost) {
            return null;
        }

        return $this->actual_cost - $this->estimated_cost;
    }

    /**
     * Get the cost variance percentage.
     */
    public function getCostVariancePercentageAttribute(): ?float
    {
        if (!$this->actual_cost || $this->estimated_cost == 0) {
            return null;
        }

        return (($this->actual_cost - $this->estimated_cost) / $this->estimated_cost) * 100;
    }

    /**
     * Get the duration variance.
     */
    public function getDurationVarianceAttribute(): ?float
    {
        if (!$this->actual_duration_hours) {
            return null;
        }

        return $this->actual_duration_hours - $this->estimated_duration_hours;
    }

    /**
     * Get the duration variance percentage.
     */
    public function getDurationVariancePercentageAttribute(): ?float
    {
        if (!$this->actual_duration_hours || $this->estimated_duration_hours == 0) {
            return null;
        }

        return (($this->actual_duration_hours - $this->estimated_duration_hours) / $this->estimated_duration_hours) * 100;
    }

    /**
     * Approve the recommendation.
     */
    public function approve(User $approver, string $notes = null): void
    {
        $this->update([
            'status' => RecommendationStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'updated_by' => $approver->id,
        ]);
    }

    /**
     * Reject the recommendation.
     */
    public function reject(User $rejecter, string $reason): void
    {
        $this->update([
            'status' => RecommendationStatus::REJECTED,
            'rejected_by' => $rejecter->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'updated_by' => $rejecter->id,
        ]);
    }

    /**
     * Complete the recommendation.
     */
    public function complete(User $user, array $completionData): void
    {
        $this->update([
            'status' => RecommendationStatus::COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $completionData['notes'] ?? null,
            'actual_cost' => $completionData['actual_cost'] ?? null,
            'actual_duration_hours' => $completionData['actual_duration_hours'] ?? null,
            'effectiveness_rating' => $completionData['effectiveness_rating'] ?? null,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Create work order from recommendation.
     */
    public function createWorkOrder(User $creator): WorkOrder
    {
        $workOrder = WorkOrder::create([
            'asset_id' => $this->asset_id,
            'title' => $this->generateWorkOrderTitle(),
            'description' => $this->detailed_description,
            'priority' => $this->mapUrgencyToPriority(),
            'estimated_duration' => $this->estimated_duration_hours,
            'estimated_cost' => $this->estimated_cost,
            'required_parts' => $this->required_parts,
            'assigned_to' => $this->assigned_to,
            'scheduled_date' => $this->recommended_date,
            'due_date' => $this->deadline_date,
            'created_by' => $creator->id,
        ]);

        $this->update(['work_order_id' => $workOrder->id]);

        return $workOrder;
    }

    /**
     * Generate work order title.
     */
    private function generateWorkOrderTitle(): string
    {
        return match($this->recommendation_type) {
            RecommendationType::INSPECTION => "Predictive Maintenance Inspection: {$this->asset->name}",
            RecommendationType::PREVENTIVE_MAINTENANCE => "Preventive Maintenance: {$this->asset->name}",
            RecommendationType::CORRECTIVE_MAINTENANCE => "Corrective Maintenance: {$this->asset->name}",
            RecommendationType::REPLACEMENT => "Equipment Replacement: {$this->asset->name}",
            RecommendationType::UPGRADE => "Equipment Upgrade: {$this->asset->name}",
            RecommendationType::CALIBRATION => "Calibration: {$this->asset->name}",
            RecommendationType::CLEANING => "Cleaning: {$this->asset->name}",
            RecommendationType::LUBRICATION => "Lubrication: {$this->asset->name}",
            RecommendationType::ADJUSTMENT => "Adjustment: {$this->asset->name}",
            RecommendationType::REPAIR => "Repair: {$this->asset->name}",
            RecommendationType::OVERHAUL => "Overhaul: {$this->asset->name}",
        };
    }

    /**
     * Map urgency to work order priority.
     */
    private function mapUrgencyToPriority(): string
    {
        return match($this->urgency) {
            Urgency::CRITICAL => 'critical',
            Urgency::HIGH => 'high',
            Urgency::MEDIUM => 'medium',
            Urgency::LOW => 'low',
            Urgency::ROUTINE => 'low',
        };
    }

    /**
     * Get recommendation summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'asset_name' => $this->asset->name,
            'recommendation_type' => $this->recommendation_type_display,
            'urgency' => $this->urgency_display,
            'urgency_color' => $this->urgency_color,
            'description' => $this->description,
            'estimated_cost' => $this->formatted_estimated_cost,
            'actual_cost' => $this->formatted_actual_cost,
            'cost_variance' => $this->cost_variance ? number_format($this->cost_variance, 2) : null,
            'estimated_duration' => $this->estimated_duration_hours,
            'actual_duration' => $this->actual_duration_hours,
            'duration_variance' => $this->duration_variance ? number_format($this->duration_variance, 2) : null,
            'recommended_date' => $this->recommended_date?->format('Y-m-d'),
            'deadline_date' => $this->deadline_date?->format('Y-m-d'),
            'days_until_deadline' => $this->days_until_deadline,
            'status' => $this->status_display,
            'status_color' => $this->status_color,
            'is_overdue' => $this->isOverdue(),
            'assigned_to' => $this->assignedTo?->full_name,
            'work_order_id' => $this->work_order_id,
            'effectiveness_rating' => $this->effectiveness_rating ? number_format($this->effectiveness_rating, 1) : null,
        ];
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetricsAttribute(): array
    {
        if (!$this->isCompleted()) {
            return [];
        }

        return [
            'cost_accuracy' => $this->cost_variance_percentage ? 100 - abs($this->cost_variance_percentage) : null,
            'duration_accuracy' => $this->duration_variance_percentage ? 100 - abs($this->duration_variance_percentage) : null,
            'effectiveness_rating' => $this->effectiveness_rating,
            'completed_on_time' => $this->completed_at && $this->deadline_date ? $this->completed_at <= $this->deadline_date : null,
            'cost_performance' => $this->estimated_cost > 0 ? $this->actual_cost / $this->estimated_cost : null,
            'duration_performance' => $this->estimated_duration_hours > 0 ? $this->actual_duration_hours / $this->estimated_duration_hours : null,
        ];
    }
}

/**
 * Recommendation Type Enum
 */
enum RecommendationType: string
{
    case INSPECTION = 'inspection';
    case PREVENTIVE_MAINTENANCE = 'preventive_maintenance';
    case CORRECTIVE_MAINTENANCE = 'corrective_maintenance';
    case REPLACEMENT = 'replacement';
    case UPGRADE = 'upgrade';
    case CALIBRATION = 'calibration';
    case CLEANING = 'cleaning';
    case LUBRICATION = 'lubrication';
    case ADJUSTMENT = 'adjustment';
    case REPAIR = 'repair';
    case OVERHAUL = 'overhaul';

    public function getDisplayName(): string
    {
        return match($this) {
            self::INSPECTION => 'Inspection',
            self::PREVENTIVE_MAINTENANCE => 'Preventive Maintenance',
            self::CORRECTIVE_MAINTENANCE => 'Corrective Maintenance',
            self::REPLACEMENT => 'Replacement',
            self::UPGRADE => 'Upgrade',
            self::CALIBRATION => 'Calibration',
            self::CLEANING => 'Cleaning',
            self::LUBRICATION => 'Lubrication',
            self::ADJUSTMENT => 'Adjustment',
            self::REPAIR => 'Repair',
            self::OVERHAUL => 'Overhaul',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::INSPECTION => 'Detailed inspection to assess equipment condition',
            self::PREVENTIVE_MAINTENANCE => 'Scheduled maintenance to prevent failures',
            self::CORRECTIVE_MAINTENANCE => 'Repairs to fix identified issues',
            self::REPLACEMENT => 'Complete equipment replacement',
            self::UPGRADE => 'Equipment upgrade or modernization',
            self::CALIBRATION => 'Calibration to ensure accuracy',
            self::CLEANING => 'Cleaning to maintain performance',
            self::LUBRICATION => 'Lubrication to reduce wear',
            self::ADJUSTMENT => 'Adjustments to optimize performance',
            self::REPAIR => 'Repair of faulty components',
            self::OVERHAUL => 'Complete equipment overhaul',
        };
    }

    public function getTypicalDurationHours(): float
    {
        return match($this) {
            self::INSPECTION => 2.0,
            self::PREVENTIVE_MAINTENANCE => 4.0,
            self::CORRECTIVE_MAINTENANCE => 8.0,
            self::REPLACEMENT => 16.0,
            self::UPGRADE => 24.0,
            self::CALIBRATION => 1.5,
            self::CLEANING => 1.0,
            self::LUBRICATION => 0.5,
            self::ADJUSTMENT => 1.0,
            self::REPAIR => 6.0,
            self::OVERHAUL => 32.0,
        };
    }

    public function getTypicalCostMultiplier(): float
    {
        return match($this) {
            self::INSPECTION => 0.01,
            self::PREVENTIVE_MAINTENANCE => 0.05,
            self::CORRECTIVE_MAINTENANCE => 0.15,
            self::REPLACEMENT => 0.80,
            self::UPGRADE => 0.60,
            self::CALIBRATION => 0.02,
            self::CLEANING => 0.005,
            self::LUBRICATION => 0.003,
            self::ADJUSTMENT => 0.01,
            self::REPAIR => 0.10,
            self::OVERHAUL => 0.40,
        };
    }
}

/**
 * Urgency Enum
 */
enum Urgency: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case ROUTINE = 'routine';

    public function getDisplayName(): string
    {
        return match($this) {
            self::CRITICAL => 'Critical',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::ROUTINE => 'Routine',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::CRITICAL => 'red',
            self::HIGH => 'orange',
            self::MEDIUM => 'yellow',
            self::LOW => 'blue',
            self::ROUTINE => 'green',
        };
    }

    public function getNumericValue(): int
    {
        return match($this) {
            self::CRITICAL => 5,
            self::HIGH => 4,
            self::MEDIUM => 3,
            self::LOW => 2,
            self::ROUTINE => 1,
        };
    }

    public function getResponseTime(): string
    {
        return match($this) {
            self::CRITICAL => 'Immediately',
            self::HIGH => 'Within 24 hours',
            self::MEDIUM => 'Within 3 days',
            self::LOW => 'Within 1 week',
            self::ROUTINE => 'Within 2 weeks',
        };
    }

    public function getPriorityLevel(): int
    {
        return match($this) {
            self::CRITICAL => 1,
            self::HIGH => 2,
            self::MEDIUM => 3,
            self::LOW => 4,
            self::ROUTINE => 5,
        };
    }
}

/**
 * Recommendation Status Enum
 */
enum RecommendationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'blue',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::IN_PROGRESS => 'orange',
            self::COMPLETED => 'green',
            self::CANCELLED => 'gray',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED, self::IN_PROGRESS]);
    }

    public function isComplete(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED]);
    }
}
