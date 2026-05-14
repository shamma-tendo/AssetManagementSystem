<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkOrder extends Model
{
    use HasFactory, HasUuids;

    public function resolveRouteBinding($value, $field = null)
    {
        $workOrder = $this->where($field ?? $this->getRouteKeyName(), $value)->first();
        if (! $workOrder) {
            return null;
        }
        $user = auth()->user();
        if ($user && $user->organization_id) {
            $workOrder->loadMissing('asset');
            if (! $workOrder->asset || $workOrder->asset->organization_id !== $user->organization_id) {
                abort(404);
            }
        }

        return $workOrder;
    }

    protected $fillable = [
        'work_order_number', 'asset_id', 'type', 'status', 'assigned_to', 'description',
        'scheduled_date', 'started_date', 'completed_date', 'estimated_labor_hours',
        'actual_labor_hours', 'estimated_cost', 'actual_cost', 'created_by'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'scheduled_date' => 'datetime',
        'started_date' => 'datetime',
        'completed_date' => 'datetime',
        'estimated_labor_hours' => 'decimal:2',
        'actual_labor_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function spareParts(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'work_order_parts')
            ->withPivot('quantity_used', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
