<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceHistory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'maintenance_schedule_id',
        'work_order_id',
        'asset_id',
        'performed_by',
        'performed_date',
        'actual_duration_hours',
        'estimated_duration_hours',
        'actual_cost',
        'estimated_cost',
        'completion_status',
        'notes',
        'findings',
        'recommendations',
        'parts_used',
        'tools_used',
        'checklist_completed',
        'checklist_items',
        'next_due_date',
        'completed_on_time',
        'performance_rating',
        'issues_found',
        'follow_up_required',
        'follow_up_date',
        'created_by',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'next_due_date' => 'date',
        'follow_up_date' => 'date',
        'actual_duration_hours' => 'decimal:4',
        'estimated_duration_hours' => 'decimal:4',
        'actual_cost' => 'decimal:8,2',
        'estimated_cost' => 'decimal:8,2',
        'completed_on_time' => 'boolean',
        'performance_rating' => 'integer',
        'follow_up_required' => 'boolean',
        'checklist_completed' => 'boolean',
        'checklist_items' => 'array',
        'parts_used' => 'array',
        'tools_used' => 'array',
        'issues_found' => 'array',
    ];

    /**
     * Get the maintenance schedule.
     */
    public function maintenanceSchedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    /**
     * Get the work order.
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the asset.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user who performed the maintenance.
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who created the history record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include completed maintenance.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    /**
     * Scope a query to only include maintenance performed in date range.
     */
    public function scopePerformedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include overdue maintenance.
     */
    public function scopeOverdue($query)
    {
        return $query->where('completed_on_time', false);
    }

    /**
     * Scope a query to only include maintenance with issues.
     */
    public function scopeWithIssues($query)
    {
        return $query->whereNotNull('issues_found')
                    ->where('issues_found', '!=', '[]');
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
     * Calculate duration variance.
     */
    public function getDurationVarianceAttribute(): ?float
    {
        if ($this->estimated_duration_hours && $this->actual_duration_hours) {
            return $this->actual_duration_hours - $this->estimated_duration_hours;
        }
        
        return null;
    }

    /**
     * Get performance rating display.
     */
    public function getPerformanceRatingDisplayAttribute(): string
    {
        return match($this->performance_rating) {
            5 => 'Excellent',
            4 => 'Good',
            3 => 'Average',
            2 => 'Poor',
            1 => 'Very Poor',
            default => 'Not Rated',
        };
    }

    /**
     * Check if maintenance was completed on time.
     */
    public function isCompletedOnTime(): bool
    {
        return $this->completed_on_time ?? true;
    }

    /**
     * Get completion status display.
     */
    public function getCompletionStatusDisplayAttribute(): string
    {
        return match($this->completion_status) {
            'completed' => 'Completed',
            'partially_completed' => 'Partially Completed',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
            default => 'Unknown',
        };
    }
}
