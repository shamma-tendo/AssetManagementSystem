<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Inspection extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'inspection_type',
        'title',
        'description',
        'scheduled_date',
        'performed_date',
        'inspector_id',
        'supervisor_id',
        'status',
        'priority',
        'duration_minutes',
        'checklist_template_id',
        'checklist_items',
        'checklist_results',
        'overall_score',
        'max_score',
        'passing_score',
        'findings',
        'recommendations',
        'deficiencies',
        'corrective_actions_required',
        'next_inspection_date',
        'follow_up_required',
        'follow_up_date',
        'compliance_status',
        'risk_assessment',
        'safety_concerns',
        'environmental_concerns',
        'notes',
        'internal_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'performed_date' => 'date',
        'next_inspection_date' => 'date',
        'follow_up_date' => 'date',
        'duration_minutes' => 'integer',
        'checklist_items' => 'array',
        'checklist_results' => 'array',
        'overall_score' => 'decimal:4,2',
        'max_score' => 'decimal:4,2',
        'passing_score' => 'decimal:4,2',
        'findings' => 'array',
        'recommendations' => 'array',
        'deficiencies' => 'array',
        'corrective_actions_required' => 'boolean',
        'follow_up_required' => 'boolean',
        'compliance_status' => 'array',
        'risk_assessment' => 'array',
        'safety_concerns' => 'array',
        'environmental_concerns' => 'array',
        'inspection_type' => InspectionType::class,
        'status' => InspectionStatus::class,
        'priority' => InspectionPriority::class,
    ];

    /**
     * Get the asset for this inspection.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the inspector who performed the inspection.
     */
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * Get the supervisor who approved the inspection.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the user who created the inspection.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the inspection.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the checklist template used for this inspection.
     */
    public function checklistTemplate()
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    /**
     * Get the inspection attachments.
     */
    public function attachments()
    {
        return $this->hasMany(InspectionAttachment::class);
    }

    /**
     * Get the inspection comments.
     */
    public function comments()
    {
        return $this->hasMany(InspectionComment::class);
    }

    /**
     * Get the inspection history.
     */
    public function history()
    {
        return $this->hasMany(InspectionHistory::class);
    }

    /**
     * Get related work orders.
     */
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Scope a query to only include inspections with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include inspections with specific priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include inspections for specific inspector.
     */
    public function scopeByInspector($query, $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }

    /**
     * Scope a query to only include overdue inspections.
     */
    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope a query to only include inspections due today.
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('scheduled_date', today())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope a query to only include inspections due this week.
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope a query to only include inspections requiring follow-up.
     */
    public function scopeRequiresFollowUp($query)
    {
        return $query->where('follow_up_required', true);
    }

    /**
     * Scope a query to only include inspections with deficiencies.
     */
    public function scopeWithDeficiencies($query)
    {
        return $query->where('corrective_actions_required', true);
    }

    /**
     * Get the inspection's priority display name.
     */
    public function getPriorityDisplayNameAttribute()
    {
        return $this->priority->getDisplayName();
    }

    /**
     * Get the inspection's status display name.
     */
    public function getStatusDisplayNameAttribute()
    {
        return $this->status->getDisplayName();
    }

    /**
     * Get the inspection's type display name.
     */
    public function getTypeDisplayNameAttribute()
    {
        return $this->inspection_type->getDisplayName();
    }

    /**
     * Get the inspection's priority color.
     */
    public function getPriorityColorAttribute()
    {
        return $this->priority->getColor();
    }

    /**
     * Get the inspection's status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->status->getColor();
    }

    /**
     * Check if inspection is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_date && 
               $this->scheduled_date->isPast() && 
               !in_array($this->status->value, ['completed', 'cancelled']);
    }

    /**
     * Check if inspection is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === InspectionStatus::COMPLETED;
    }

    /**
     * Check if inspection passed.
     */
    public function isPassed(): bool
    {
        return $this->overall_score >= $this->passing_score;
    }

    /**
     * Check if inspection failed.
     */
    public function isFailed(): bool
    {
        return $this->overall_score < $this->passing_score;
    }

    /**
     * Get compliance percentage.
     */
    public function getCompliancePercentageAttribute(): ?float
    {
        if (!$this->max_score || $this->max_score == 0) {
            return null;
        }
        
        return ($this->overall_score / $this->max_score) * 100;
    }

    /**
     * Get inspection duration in hours.
     */
    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->duration_minutes) {
            return null;
        }
        
        return $this->duration_minutes / 60;
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->scheduled_date) {
            return null;
        }
        
        return now()->diffInDays($this->scheduled_date, false);
    }

    /**
     * Get risk level based on findings.
     */
    public function getRiskLevelAttribute(): string
    {
        if (!$this->risk_assessment) {
            return 'low';
        }

        $riskScore = $this->risk_assessment['overall_score'] ?? 1;
        
        return match(true) {
            $riskScore >= 8 => 'critical',
            $riskScore >= 6 => 'high',
            $riskScore >= 4 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get inspection status display.
     */
    public function getStatusDisplayAttribute(): string
    {
        if ($this->isCompleted()) {
            return $this->isPassed() ? 'Passed' : 'Failed';
        }
        
        return $this->status->getDisplayName();
    }

    /**
     * Calculate checklist completion percentage.
     */
    public function getChecklistCompletionPercentageAttribute(): ?float
    {
        if (!$this->checklist_items || empty($this->checklist_items)) {
            return null;
        }

        $totalItems = count($this->checklist_items);
        $completedItems = 0;

        foreach ($this->checklist_results as $result) {
            if (isset($result['completed']) && $result['completed']) {
                $completedItems++;
            }
        }

        return ($completedItems / $totalItems) * 100;
    }

    /**
     * Get deficiency count.
     */
    public function getDeficiencyCountAttribute(): int
    {
        return count($this->deficiencies ?? []);
    }

    /**
     * Get finding count.
     */
    public function getFindingCountAttribute(): int
    {
        return count($this->findings ?? []);
    }

    /**
     * Get recommendation count.
     */
    public function getRecommendationCountAttribute(): int
    {
        return count($this->recommendations ?? []);
    }

    /**
     * Create work order from inspection findings.
     */
    public function createWorkOrderFromFindings(): WorkOrder
    {
        $workOrder = WorkOrder::create([
            'title' => "Corrective Actions - {$this->title}",
            'description' => "Work order created from inspection findings: " . implode(', ', array_column($this->deficiencies, 'description')),
            'priority' => $this->getWorkOrderPriorityFromRisk(),
            'type' => 'corrective_maintenance',
            'asset_id' => $this->asset_id,
            'assigned_to' => $this->inspector_id,
            'scheduled_date' => now()->addDays(7), // Schedule for next week
            'created_by' => $this->created_by,
            'inspection_id' => $this->id,
        ]);

        return $workOrder;
    }

    /**
     * Get work order priority based on inspection risk.
     */
    private function getWorkOrderPriorityFromRisk(): string
    {
        $riskLevel = $this->risk_level;
        
        return match($riskLevel) {
            'critical' => 'emergency',
            'high' => 'urgent',
            'medium' => 'high',
            default => 'normal',
        };
    }

    /**
     * Generate inspection report summary.
     */
    public function getReportSummaryAttribute(): array
    {
        return [
            'inspection_id' => $this->id,
            'title' => $this->title,
            'inspection_type' => $this->getTypeDisplayName(),
            'status' => $this->getStatusDisplayAttribute(),
            'priority' => $this->getPriorityDisplayNameAttribute(),
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'performed_date' => $this->performed_date?->format('Y-m-d'),
            'inspector' => $this->inspector?->full_name,
            'asset' => $this->asset?->name,
            'overall_score' => $this->overall_score,
            'max_score' => $this->max_score,
            'passing_score' => $this->passing_score,
            'compliance_percentage' => $this->compliance_percentage,
            'duration_minutes' => $this->duration_minutes,
            'deficiency_count' => $this->deficiency_count,
            'finding_count' => $this->finding_count,
            'recommendation_count' => $this->recommendation_count,
            'risk_level' => $this->risk_level,
            'follow_up_required' => $this->follow_up_required,
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d'),
        ];
    }
}

/**
 * Inspection Type Enum
 */
enum InspectionType: string
{
    case ROUTINE = 'routine';
    case PERIODIC = 'periodic';
    case SPECIAL = 'special';
    case EMERGENCY = 'emergency';
    case PREVENTIVE = 'preventive';
    case COMPLIANCE = 'compliance';
    case SAFETY = 'safety';
    case ENVIRONMENTAL = 'environmental';
    case QUALITY = 'quality';
    case OPERATIONAL = 'operational';
    case ACCEPTANCE = 'acceptance';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ROUTINE => 'Routine',
            self::PERIODIC => 'Periodic',
            self::SPECIAL => 'Special',
            self::EMERGENCY => 'Emergency',
            self::PREVENTIVE => 'Preventive',
            self::COMPLIANCE => 'Compliance',
            self::SAFETY => 'Safety',
            self::ENVIRONMENTAL => 'Environmental',
            self::QUALITY => 'Quality',
            self::OPERATIONAL => 'Operational',
            self::ACCEPTANCE => 'Acceptance',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::ROUTINE => 'repeat',
            self::PERIODIC => 'calendar',
            self::SPECIAL => 'star',
            self::EMERGENCY => 'alert-triangle',
            self::PREVENTIVE => 'shield-check',
            self::COMPLIANCE => 'clipboard-check',
            self::SAFETY => 'shield',
            self::ENVIRONMENTAL => 'leaf',
            self::QUALITY => 'award',
            self::OPERATIONAL => 'settings',
            self::ACCEPTANCE => 'check-circle',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ROUTINE => 'blue',
            self::PERIODIC => 'indigo',
            self::SPECIAL => 'purple',
            self::EMERGENCY => 'red',
            self::PREVENTIVE => 'green',
            self::COMPLIANCE => 'yellow',
            self::SAFETY => 'orange',
            self::ENVIRONMENTAL => 'teal',
            self::QUALITY => 'pink',
            self::OPERATIONAL => 'gray',
            self::ACCEPTANCE => 'emerald',
        };
    }
}

/**
 * Inspection Status Enum
 */
enum InspectionStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case POSTPONED = 'postponed';
    case FAILED = 'failed';
    case PASSED = 'passed';

    public function getDisplayName(): string
    {
        return match($this) {
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::POSTPONED => 'Postponed',
            self::FAILED => 'Failed',
            self::PASSED => 'Passed',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::SCHEDULED => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'gray',
            self::CANCELLED => 'red',
            self::POSTPONED => 'orange',
            self::FAILED => 'red',
            self::PASSED => 'green',
        };
    }

    public function canTransitionTo(InspectionStatus $newStatus): bool
    {
        $validTransitions = [
            self::SCHEDULED => [self::IN_PROGRESS, self::CANCELLED, self::POSTPONED],
            self::IN_PROGRESS => [self::COMPLETED, self::FAILED, self::PASSED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
            self::POSTPONED => [self::SCHEDULED, self::CANCELLED],
            self::FAILED => [],
            self::PASSED => [],
        ];

        return in_array($newStatus, $validTransitions[$this] ?? []);
    }
}

/**
 * Inspection Priority Enum
 */
enum InspectionPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';
    case CRITICAL = 'critical';

    public function getDisplayName(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
            self::CRITICAL => 'Critical',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::LOW => 'gray',
            self::NORMAL => 'blue',
            self::HIGH => 'yellow',
            self::URGENT => 'orange',
            self::CRITICAL => 'red',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::URGENT => 4,
            self::CRITICAL => 5,
        };
    }
}
