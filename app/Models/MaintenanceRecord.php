<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id', 'work_order_id', 'technician_id', 'type', 'description',
        'findings', 'actions_taken', 'maintenance_date', 'labor_hours', 'labor_cost',
        'parts_cost', 'total_cost', 'asset_operational'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'maintenance_date' => 'datetime',
        'labor_hours' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'asset_operational' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
