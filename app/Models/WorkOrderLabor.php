<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkOrderLabor extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'work_order_id',
        'technician_id',
        'hours_worked',
        'hourly_rate',
        'total_cost',
        'work_description',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'hours_worked' => 'decimal:4',
        'hourly_rate' => 'float',
        'total_cost' => 'float',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the work order that owns the labor entry.
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the technician who performed the labor.
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Calculate total cost before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($labor) {
            if ($labor->hours_worked && $labor->hourly_rate) {
                $labor->total_cost = $labor->hours_worked * $labor->hourly_rate;
            }
        });
    }

    /**
     * Get the duration in hours from start and end times.
     */
    public function getDurationAttribute(): ?float
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInHours($this->end_time);
        }
        
        return null;
    }
}

