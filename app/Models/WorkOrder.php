<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'type',
        'asset_id',
        'assigned_to',
        'created_by',
        'requested_by',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'actual_cost',
        'scheduled_date',
        'started_at',
        'completed_at',
        'closed_at',
        'location_id',
        'department_id',
        'notes',
        'completion_notes',
        'work_performed',
        'parts_used',
        'tools_used',
        'safety_precautions',
        'follow_up_required',
        'follow_up_date',
        'customer_satisfaction',
        'internal_notes',
        'maintenance_schedule_id',
        'inspection_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'follow_up_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'follow_up_required' => 'boolean',
        'customer_satisfaction' => 'integer',
        'priority' => WorkOrderPriority::class,
        'status' => WorkOrderStatus::class,
        'type' => WorkOrderType::class,
    ];

    /**
     * Get the asset that owns the work order.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user assigned to the work order.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the work order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who requested the work order.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the location for the work order.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the department for the work order.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the work order parts.
     */
    public function parts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    /**
     * Get the work order labor entries.
     */
    public function laborEntries()
    {
        return $this->hasMany(WorkOrderLabor::class);
    }

    /**
     * Get the work order attachments.
     */
    public function attachments()
    {
        return $this->hasMany(WorkOrderAttachment::class);
    }

    /**
     * Get the work order comments.
     */
    public function comments()
    {
        return $this->hasMany(WorkOrderComment::class);
    }

    /**
     * Get the work order history.
     */
    public function history()
    {
        return $this->hasMany(WorkOrderHistory::class);
    }

    /**
     * Get the maintenance schedule that generated this work order.
     */
    public function maintenanceSchedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    /**
     * Get the inspection that created this work order.
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Scope a query to only include work orders with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include work orders with specific priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include work orders for specific user.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope a query to only include overdue work orders.
     */
    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
                    ->whereNotIn('status', ['completed', 'closed']);
    }

    /**
     * Scope a query to only include work orders due today.
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('scheduled_date', today())
                    ->whereNotIn('status', ['completed', 'closed']);
    }

    /**
     * Scope a query to only include work orders due this week.
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->whereNotIn('status', ['completed', 'closed']);
    }

    /**
     * Get the work order's priority display name.
     */
    public function getPriorityDisplayNameAttribute()
    {
        $priority = $this->priority instanceof \BackedEnum ? $this->priority->value : (string) $this->priority;
        return match($priority) {
            'low' => 'LOW',
            'normal' => 'MEDIUM',
            'high' => 'HIGH',
            'urgent' => 'URGENT',
            'emergency' => 'EMERGENCY',
            default => strtoupper($priority),
        };
    }

    /**
     * Get the work order formatted for display in views/APIs.
     */
    public function getFormattedAttribute(): array
    {
        $type = $this->type instanceof \BackedEnum ? $this->type->value : (string) $this->type;
        return [
            'id' => $this->id,
            'title' => $this->title,
            'priority' => $this->priority_display_name,
            'type' => ucfirst(str_replace('_', ' ', $type)),
            'asset' => $this->asset?->name ?? 'Unassigned',
            'technician' => $this->assignedTo?->name ?? 'Unassigned',
            'dueDate' => $this->scheduled_date?->format('Y-m-d') ?? 'Not scheduled',
            'estimatedHours' => (string) $this->estimated_hours ?? 'N/A',
            'status' => $this->status instanceof \BackedEnum ? strtoupper($this->status->value) : strtoupper($this->status),
            'progress' => $this->calculateProgress(),
        ];
    }

    /**
     * Calculate work order progress based on status.
     */
    public function calculateProgress(): int
    {
        $sv = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;

        return match($sv) {
            'requested', 'approved' => 0,
            'assigned', 'scheduled' => 10,
            'in_progress' => 50,
            'on_hold' => 50,
            'completed' => 90,
            'closed' => 100,
            'cancelled' => 0,
            default => 0,
        };
    }

    /**
     * Get the work order's status display name.
     */
    public function getStatusDisplayNameAttribute()
    {
        return $this->status->getDisplayName();
    }

    /**
     * Get the work order's type display name.
     */
    public function getTypeDisplayNameAttribute()
    {
        return $this->type->getDisplayName();
    }

    /**
     * Get the work order's priority color.
     */
    public function getPriorityColorAttribute()
    {
        return $this->priority->getColor();
    }

    /**
     * Get the work order's status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->status->getColor();
    }

    /**
     * Check if work order is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_date && 
               $this->scheduled_date->isPast() && 
               !in_array($this->status->value, ['completed', 'closed']);
    }

    /**
     * Check if work order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === WorkOrderStatus::COMPLETED;
    }

    /**
     * Check if work order is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === WorkOrderStatus::IN_PROGRESS;
    }

    /**
     * Calculate work order duration in hours.
     */
    public function getDurationAttribute(): ?float
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInHours($this->completed_at);
        }
        
        if ($this->started_at) {
            return $this->started_at->diffInHours(now());
        }
        
        return null;
    }

    /**
     * Calculate cost variance.
     */
    public function getCostVarianceAttribute(): ?float
    {
        if ($this->estimated_cost && $this->actual_cost) {
            return $this->actual_cost - $this->estimated_cost;
        }
        
        return null;
    }

    /**
     * Calculate hours variance.
     */
    public function getHoursVarianceAttribute(): ?float
    {
        if ($this->estimated_hours && $this->actual_hours) {
            return $this->actual_hours - $this->estimated_hours;
        }
        
        return null;
    }

    /**
     * Get total parts cost.
     */
    public function getTotalPartsCostAttribute(): float
    {
        return $this->parts()->sum('total_cost');
    }

    /**
     * Get total labor cost.
     */
    public function getTotalLaborCostAttribute(): float
    {
        return $this->laborEntries()->sum('total_cost');
    }

    /**
     * Get total work order cost.
     */
    public function getTotalCostAttribute(): float
    {
        return $this->getTotalPartsCostAttribute() + $this->getTotalLaborCostAttribute();
    }
}

/**
 * Work Order Priority Enum
 */
enum WorkOrderPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';
    case EMERGENCY = 'emergency';

    public function getDisplayName(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
            self::EMERGENCY => 'Emergency',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::LOW => 'gray',
            self::NORMAL => 'blue',
            self::HIGH => 'yellow',
            self::URGENT => 'orange',
            self::EMERGENCY => 'red',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::URGENT => 4,
            self::EMERGENCY => 5,
        };
    }
}

/**
 * Work Order Status Enum
 */
enum WorkOrderStatus: string
{
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case ASSIGNED = 'assigned';
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string
    {
        return match($this) {
            self::REQUESTED => 'Requested',
            self::APPROVED => 'Approved',
            self::ASSIGNED => 'Assigned',
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::REQUESTED => 'gray',
            self::APPROVED => 'blue',
            self::ASSIGNED => 'indigo',
            self::SCHEDULED => 'purple',
            self::IN_PROGRESS => 'yellow',
            self::ON_HOLD => 'orange',
            self::COMPLETED => 'green',
            self::CLOSED => 'emerald',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(WorkOrderStatus $newStatus): bool
    {
        $validTransitions = [
            self::REQUESTED => [self::APPROVED, self::CANCELLED],
            self::APPROVED => [self::ASSIGNED, self::CANCELLED],
            self::ASSIGNED => [self::SCHEDULED, self::IN_PROGRESS, self::CANCELLED],
            self::SCHEDULED => [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED],
            self::IN_PROGRESS => [self::ON_HOLD, self::COMPLETED, self::CANCELLED],
            self::ON_HOLD => [self::IN_PROGRESS, self::CANCELLED],
            self::COMPLETED => [self::CLOSED],
            self::CLOSED => [],
            self::CANCELLED => [],
        ];

        return in_array($newStatus, $validTransitions[$this] ?? []);
    }
}

/**
 * Work Order Type Enum
 */
enum WorkOrderType: string
{
    case PREVENTIVE_MAINTENANCE = 'preventive_maintenance';
    case CORRECTIVE_MAINTENANCE = 'corrective_maintenance';
    case EMERGENCY_MAINTENANCE = 'emergency_maintenance';
    case INSPECTION = 'inspection';
    case CALIBRATION = 'calibration';
    case INSTALLATION = 'installation';
    case REMOVAL = 'removal';
    case UPGRADE = 'upgrade';
    case REPAIR = 'repair';
    case OTHER = 'other';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PREVENTIVE_MAINTENANCE => 'Preventive Maintenance',
            self::CORRECTIVE_MAINTENANCE => 'Corrective Maintenance',
            self::EMERGENCY_MAINTENANCE => 'Emergency Maintenance',
            self::INSPECTION => 'Inspection',
            self::CALIBRATION => 'Calibration',
            self::INSTALLATION => 'Installation',
            self::REMOVAL => 'Removal',
            self::UPGRADE => 'Upgrade',
            self::REPAIR => 'Repair',
            self::OTHER => 'Other',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PREVENTIVE_MAINTENANCE => 'shield-check',
            self::CORRECTIVE_MAINTENANCE => 'wrench',
            self::EMERGENCY_MAINTENANCE => 'alert-triangle',
            self::INSPECTION => 'search',
            self::CALIBRATION => 'settings',
            self::INSTALLATION => 'plus-circle',
            self::REMOVAL => 'minus-circle',
            self::UPGRADE => 'arrow-up-circle',
            self::REPAIR => 'tool',
            self::OTHER => 'more-horizontal',
        };
    }
}
