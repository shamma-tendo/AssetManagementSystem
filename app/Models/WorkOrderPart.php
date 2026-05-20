<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkOrderPart extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'work_order_id',
        'part_name',
        'part_number',
        'quantity_used',
        'unit_cost',
        'total_cost',
        'supplier',
        'vendor_part_number',
        'notes',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:4',
        'unit_cost' => 'float',
        'total_cost' => 'float',
    ];

    /**
     * Get the work order that owns the part.
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Calculate total cost before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($part) {
            if ($part->quantity_used && $part->unit_cost) {
                $part->total_cost = $part->quantity_used * $part->unit_cost;
            }
        });
    }
}

