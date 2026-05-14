<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SparePart extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'part_number', 'part_name', 'description', 'supplier', 'unit_cost',
        'stock_quantity', 'reorder_point', 'reorder_quantity', 'unit_of_measure',
        'category_id', 'location_id'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function workOrders(): BelongsToMany
    {
        return $this->belongsToMany(WorkOrder::class, 'work_order_parts')
            ->withPivot('quantity_used', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    public function workOrderParts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->reorder_point;
    }
}
