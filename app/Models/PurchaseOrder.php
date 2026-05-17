<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'status',
        'priority',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'currency',
        'payment_terms',
        'delivery_terms',
        'notes',
        'internal_notes',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:15,2',
        'tax_amount' => 'decimal:15,2',
        'shipping_cost' => 'decimal:15,2',
        'total_amount' => 'decimal:15,2',
        'status' => PurchaseOrderStatus::class,
        'priority' => PurchaseOrderPriority::class,
    ];

    /**
     * Get the supplier for this purchase order.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created the purchase order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the purchase order.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved the purchase order.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the purchase order items.
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the parts in this purchase order.
     */
    public function parts()
    {
        return $this->belongsToMany(Part::class)
            ->withPivot(['quantity', 'unit_cost', 'total_cost', 'received_quantity'])
            ->withTimestamps();
    }

    /**
     * Get the inventory transactions related to this purchase order.
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class)
            ->where('reference_type', 'purchase_order');
    }

    /**
     * Scope a query to only include orders with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include orders from specific supplier.
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope a query to only include orders within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include overdue orders.
     */
    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
                    ->whereNotIn('status', ['received', 'cancelled']);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return $this->status->getDisplayName();
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    /**
     * Get the priority display name.
     */
    public function getPriorityDisplayNameAttribute(): string
    {
        return $this->priority->getDisplayName();
    }

    /**
     * Get the priority color.
     */
    public function getPriorityColorAttribute(): string
    {
        return $this->priority->getColor();
    }

    /**
     * Check if order is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->expected_delivery_date && 
               $this->expected_delivery_date->isPast() && 
               !in_array($this->status->value, ['received', 'cancelled']);
    }

    /**
     * Check if order is fully received.
     */
    public function isFullyReceived(): bool
    {
        $totalQuantity = $this->items()->sum('quantity');
        $receivedQuantity = $this->items()->sum('received_quantity');
        
        return $totalQuantity > 0 && $receivedQuantity >= $totalQuantity;
    }

    /**
     * Check if order is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        $totalQuantity = $this->items()->sum('quantity');
        $receivedQuantity = $this->items()->sum('received_quantity');
        
        return $receivedQuantity > 0 && $receivedQuantity < $totalQuantity;
    }

    /**
     * Get the receive status.
     */
    public function getReceiveStatusAttribute(): string
    {
        if ($this->status === PurchaseOrderStatus::RECEIVED) {
            return 'fully_received';
        } elseif ($this->isPartiallyReceived()) {
            return 'partially_received';
        } elseif ($this->items()->sum('received_quantity') > 0) {
            return 'partially_received';
        } else {
            return 'not_received';
        }
    }

    /**
     * Get the receive status display.
     */
    public function getReceiveStatusDisplayAttribute(): string
    {
        return match($this->receive_status) {
            'fully_received' => 'Fully Received',
            'partially_received' => 'Partially Received',
            'not_received' => 'Not Received',
        };
    }

    /**
     * Get the total quantity ordered.
     */
    public function getTotalQuantityAttribute(): float
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Get the total quantity received.
     */
    public function getTotalReceivedQuantityAttribute(): float
    {
        return $this->items()->sum('received_quantity');
    }

    /**
     * Get the remaining quantity to receive.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->total_quantity - $this->total_received_quantity;
    }

    /**
     * Get the receive percentage.
     */
    public function getReceivePercentageAttribute(): float
    {
        if ($this->total_quantity == 0) {
            return 0;
        }
        
        return ($this->total_received_quantity / $this->total_quantity) * 100;
    }

    /**
     * Get the days until expected delivery.
     */
    public function getDaysUntilDeliveryAttribute(): ?int
    {
        if (!$this->expected_delivery_date) {
            return null;
        }
        
        return now()->diffInDays($this->expected_delivery_date, false);
    }

    /**
     * Get the order summary.
     */
    public function getOrderSummaryAttribute(): array
    {
        return [
            'order_number' => $this->order_number,
            'supplier_name' => $this->supplier->name,
            'status' => $this->status_display_name,
            'priority' => $this->priority_display_name,
            'order_date' => $this->order_date->format('Y-m-d'),
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'total_items' => $this->items()->count(),
            'total_quantity' => $this->total_quantity,
            'total_received_quantity' => $this->total_received_quantity,
            'receive_percentage' => $this->receive_percentage,
            'is_overdue' => $this->isOverdue(),
            'days_until_delivery' => $this->days_until_delivery,
        ];
    }

    /**
     * Approve the purchase order.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => PurchaseOrderStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancel the purchase order.
     */
    public function cancel(): void
    {
        $this->update(['status' => PurchaseOrderStatus::CANCELLED]);
    }

    /**
     * Mark as received.
     */
    public function markAsReceived(): void
    {
        $this->update([
            'status' => PurchaseOrderStatus::RECEIVED,
            'actual_delivery_date' => now(),
        ]);
    }

    /**
     * Calculate order totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_cost');
        $taxAmount = $subtotal * 0.1; // 10% tax rate
        $totalAmount = $subtotal + $taxAmount + ($this->shipping_cost ?? 0);

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }
}

/**
 * Purchase Order Status Enum
 */
enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ORDERED = 'ordered';
    case SHIPPED = 'shipped';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::ORDERED => 'Ordered',
            self::SHIPPED => 'Shipped',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::APPROVED => 'blue',
            self::ORDERED => 'indigo',
            self::SHIPPED => 'purple',
            self::RECEIVED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(PurchaseOrderStatus $newStatus): bool
    {
        $validTransitions = [
            self::DRAFT => [self::PENDING, self::CANCELLED],
            self::PENDING => [self::APPROVED, self::CANCELLED],
            self::APPROVED => [self::ORDERED, self::CANCELLED],
            self::ORDERED => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::RECEIVED, self::CANCELLED],
            self::RECEIVED => [],
            self::CANCELLED => [],
        ];

        return in_array($newStatus, $validTransitions[$this] ?? []);
    }
}

/**
 * Purchase Order Priority Enum
 */
enum PurchaseOrderPriority: string
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
