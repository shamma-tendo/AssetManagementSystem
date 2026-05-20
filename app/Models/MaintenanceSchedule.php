<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceSchedule extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'maintenance_type',
        'frequency_type',
        'frequency_interval',
        'frequency_months',
        'frequency_days',
        'frequency_hours',
        'last_performed_date',
        'next_due_date',
        'due_date_based_on',
        'auto_create_work_order',
        'work_order_priority',
        'assigned_technician_id',
        'estimated_duration_hours',
        'estimated_cost',
        'required_parts',
        'required_tools',
        'safety_requirements',
        'checklist_items',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_performed_date' => 'date',
        'next_due_date' => 'date',
        'due_date_based_on' => 'date',
        'auto_create_work_order' => 'boolean',
        'estimated_duration_hours' => 'decimal:4',
        'estimated_cost' => 'decimal:8,2',
        'required_parts' => 'array',
        'required_tools' => 'array',
        'safety_requirements' => 'array',
        'checklist_items' => 'array',
        'is_active' => 'boolean',
        'maintenance_type' => MaintenanceType::class,
        'frequency_type' => FrequencyType::class,
    ];

    /**
     * Get the asset for this maintenance schedule.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the assigned technician.
     */
    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /**
     * Get the user who created the schedule.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the schedule.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the generated work orders from this schedule.
     */
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'maintenance_schedule_id');
    }

    /**
     * Get the maintenance history for this schedule.
     */
    public function maintenanceHistory()
    {
        return $this->hasMany(MaintenanceHistory::class);
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include schedules due soon.
     */
    public function scopeDueSoon($query, $days = 30)
    {
        return $query->where('next_due_date', '<=', now()->addDays($days))
                    ->where('next_due_date', '>=', now());
    }

    /**
     * Scope a query to only include overdue schedules.
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_due_date', '<', now())
                    ->where('is_active', true);
    }

    /**
     * Scope a query to only include schedules that auto-create work orders.
     */
    public function scopeAutoCreate($query)
    {
        return $query->where('auto_create_work_order', true);
    }

    /**
     * Scope a query to only include schedules for specific asset.
     */
    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Calculate the next due date based on frequency.
     */
    public function calculateNextDueDate(): \Carbon\Carbon
    {
        $baseDate = $this->due_date_based_on ?? $this->last_performed_date ?? now();
        
        return match($this->frequency_type) {
            FrequencyType::DAILY => $baseDate->addDays($this->frequency_days),
            FrequencyType::WEEKLY => $baseDate->addWeeks($this->frequency_interval),
            FrequencyType::MONTHLY => $baseDate->addMonths($this->frequency_months),
            FrequencyType::YEARLY => $baseDate->addYears($this->frequency_interval),
            FrequencyType::HOURLY => $baseDate->addHours($this->frequency_hours),
            FrequencyType::CUSTOM => $this->calculateCustomDueDate($baseDate),
        };
    }

    /**
     * Calculate custom due date based on complex rules.
     */
    private function calculateCustomDueDate(\Carbon\Carbon $baseDate): \Carbon\Carbon
    {
        // Custom logic for complex scheduling
        // This could include business days, seasonal adjustments, etc.
        return $baseDate->addMonths($this->frequency_months ?? 1);
    }

    /**
     * Update the next due date.
     */
    public function updateNextDueDate(): bool
    {
        $this->next_due_date = $this->calculateNextDueDate();
        return $this->save();
    }

    /**
     * Mark maintenance as performed and update dates.
     */
    public function markAsPerformed(\Carbon\Carbon $performedDate = null): bool
    {
        $this->last_performed_date = $performedDate ?? now();
        $this->next_due_date = $this->calculateNextDueDate();
        
        return $this->save();
    }

    /**
     * Check if maintenance is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast() && $this->is_active;
    }

    /**
     * Check if maintenance is due soon.
     */
    public function isDueSoon(int $days = 30): bool
    {
        return $this->next_due_date && 
               $this->next_due_date->between(now(), now()->addDays($days)) && 
               $this->is_active;
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->next_due_date) {
            return null;
        }
        
        return now()->diffInDays($this->next_due_date, false);
    }

    /**
     * Get maintenance priority level.
     */
    public function getPriorityLevelAttribute(): string
    {
        $daysUntilDue = $this->days_until_due;
        
        if ($daysUntilDue < 0) {
            return 'overdue';
        } elseif ($daysUntilDue <= 7) {
            return 'urgent';
        } elseif ($daysUntilDue <= 30) {
            return 'high';
        } elseif ($daysUntilDue <= 60) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get maintenance status display.
     */
    public function getStatusDisplayAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        
        if ($this->isOverdue()) {
            return 'Overdue';
        }
        
        if ($this->isDueSoon()) {
            return 'Due Soon';
        }
        
        return 'Scheduled';
    }

    /**
     * Create a work order from this schedule.
     */
    public function createWorkOrder(): WorkOrder
    {
        $workOrder = WorkOrder::create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->work_order_priority ?? 'normal',
            'type' => 'preventive_maintenance',
            'asset_id' => $this->asset_id,
            'assigned_to' => $this->assigned_technician_id,
            'estimated_hours' => $this->estimated_duration_hours,
            'estimated_cost' => $this->estimated_cost,
            'scheduled_date' => $this->next_due_date,
            'parts_used' => $this->required_parts,
            'tools_used' => $this->required_tools,
            'safety_precautions' => $this->safety_requirements,
            'created_by' => $this->created_by,
            'maintenance_schedule_id' => $this->id,
        ]);

        return $workOrder;
    }

    /**
     * Get maintenance compliance percentage.
     */
    public function getComplianceRateAttribute(): float
    {
        $totalScheduled = $this->maintenanceHistory()->count();
        $totalCompleted = $this->maintenanceHistory()->where('completed_on_time', true)->count();
        
        return $totalScheduled > 0 ? ($totalCompleted / $totalScheduled) * 100 : 100;
    }

    /**
     * Get average completion time.
     */
    public function getAverageCompletionTimeAttribute(): ?float
    {
        return $this->maintenanceHistory()
            ->whereNotNull('actual_duration_hours')
            ->avg('actual_duration_hours');
    }
}

/**
 * Maintenance Type Enum
 */
enum MaintenanceType: string
{
    case PREVENTIVE = 'preventive';
    case PREDICTIVE = 'predictive';
    case CORRECTIVE = 'corrective';
    case CONDITION_BASED = 'condition_based';
    case EMERGENCY = 'emergency';
    case ROUTINE = 'routine';
    case INSPECTION = 'inspection';
    case CALIBRATION = 'calibration';
    case LUBRICATION = 'lubrication';
    case CLEANING = 'cleaning';
    case TESTING = 'testing';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PREVENTIVE => 'Preventive',
            self::PREDICTIVE => 'Predictive',
            self::CORRECTIVE => 'Corrective',
            self::CONDITION_BASED => 'Condition-Based',
            self::EMERGENCY => 'Emergency',
            self::ROUTINE => 'Routine',
            self::INSPECTION => 'Inspection',
            self::CALIBRATION => 'Calibration',
            self::LUBRICATION => 'Lubrication',
            self::CLEANING => 'Cleaning',
            self::TESTING => 'Testing',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PREVENTIVE => 'shield-check',
            self::PREDICTIVE => 'trending-up',
            self::CORRECTIVE => 'wrench',
            self::CONDITION_BASED => 'activity',
            self::EMERGENCY => 'alert-triangle',
            self::ROUTINE => 'repeat',
            self::INSPECTION => 'search',
            self::CALIBRATION => 'settings',
            self::LUBRICATION => 'droplet',
            self::CLEANING => 'sparkles',
            self::TESTING => 'clipboard-check',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PREVENTIVE => 'blue',
            self::PREDICTIVE => 'purple',
            self::CORRECTIVE => 'orange',
            self::CONDITION_BASED => 'green',
            self::EMERGENCY => 'red',
            self::ROUTINE => 'gray',
            self::INSPECTION => 'indigo',
            self::CALIBRATION => 'yellow',
            self::LUBRICATION => 'cyan',
            self::CLEANING => 'teal',
            self::TESTING => 'pink',
        };
    }
}

/**
 * Frequency Type Enum
 */
enum FrequencyType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case HOURLY = 'hourly';
    case CUSTOM = 'custom';

    public function getDisplayName(): string
    {
        return match($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
            self::HOURLY => 'Hourly',
            self::CUSTOM => 'Custom',
        };
    }

    public function getUnit(): string
    {
        return match($this) {
            self::DAILY => 'days',
            self::WEEKLY => 'weeks',
            self::MONTHLY => 'months',
            self::YEARLY => 'years',
            self::HOURLY => 'hours',
            self::CUSTOM => 'custom',
        };
    }
}
