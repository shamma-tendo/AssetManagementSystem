<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseOrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'purchase_order_id',
        'part_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'received_quantity',
        'notes',
        'specifications',
        'expected_delivery_date',
        'actual_delivery_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:10,4',
        'unit_cost' => 'decimal:10,4',
        'total_cost' => 'decimal:15,2',
        'received_quantity' => 'decimal:10,4',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'specifications' => 'array',
    ];

    /**
     * Get the purchase order for this item.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the part for this item.
     */
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the remaining quantity to receive.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity - $this->received_quantity;
    }

    /**
     * Get the receive percentage.
     */
    public function getReceivePercentageAttribute(): float
    {
        return $this->quantity > 0 ? ($this->received_quantity / $this->quantity) * 100 : 0;
    }

    /**
     * Check if item is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Check if item is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->received_quantity > 0 && $this->received_quantity < $this->quantity;
    }

    /**
     * Get the receive status.
     */
    public function getReceiveStatusAttribute(): string
    {
        if ($this->isFullyReceived()) {
            return 'fully_received';
        } elseif ($this->isPartiallyReceived()) {
            return 'partially_received';
        } else {
            return 'not_received';
        }
    }

    /**
     * Get the formatted unit cost.
     */
    public function getFormattedUnitCostAttribute(): string
    {
        return number_format($this->unit_cost, 4);
    }

    /**
     * Get the formatted total cost.
     */
    public function getFormattedTotalCostAttribute(): string
    {
        return number_format($this->total_cost, 2);
    }

    /**
     * Receive quantity.
     */
    public function receiveQuantity(float $quantity, array $metadata = []): void
    {
        $newReceivedQuantity = min($quantity, $this->remaining_quantity);
        
        $this->received_quantity += $newReceivedQuantity;
        
        if ($newReceivedQuantity > 0) {
            // Create inventory transaction
            $this->part->receiveStock($newReceivedQuantity, $this->purchaseOrder->order_number, $metadata);
        }
        
        $this->save();
        
        // Update purchase order totals
        $this->purchaseOrder->calculateTotals();
    }

    /**
     * Get the item summary.
     */
    public function getItemSummaryAttribute(): array
    {
        return [
            'part_name' => $this->part->name,
            'part_number' => $this->part->part_number,
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'unit_cost' => $this->formatted_unit_cost,
            'total_cost' => $this->formatted_total_cost,
            'receive_percentage' => $this->receive_percentage,
            'receive_status' => $this->receive_status,
        ];
    }
}
