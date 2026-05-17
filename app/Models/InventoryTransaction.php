<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InventoryTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'part_id',
        'quantity',
        'transaction_type',
        'reference',
        'reference_type',
        'status',
        'unit_cost',
        'total_cost',
        'batch_number',
        'expiry_date',
        'serial_numbers',
        'location_from',
        'location_to',
        'performed_by',
        'performed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:10,4',
        'unit_cost' => 'decimal:10,4',
        'total_cost' => 'decimal:15,2',
        'expiry_date' => 'date',
        'performed_at' => 'datetime',
        'serial_numbers' => 'array',
        'metadata' => 'array',
        'transaction_type' => TransactionType::class,
        'status' => TransactionStatus::class,
    ];

    /**
     * Get the part for this transaction.
     */
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the user who performed the transaction.
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the work order if this transaction is related to one.
     */
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'reference_id')
            ->where('reference_type', 'work_order');
    }

    /**
     * Get the purchase order if this transaction is related to one.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'reference_id')
            ->where('reference_type', 'purchase_order');
    }

    /**
     * Scope a query to only include transactions of specific type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to only include transactions with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include transactions within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include transactions for specific reference.
     */
    public function scopeByReference($query, $reference, $referenceType = null)
    {
        $query->where('reference', $reference);
        
        if ($referenceType) {
            $query->where('reference_type', $referenceType);
        }
        
        return $query;
    }

    /**
     * Get the transaction type display name.
     */
    public function getTransactionTypeDisplayNameAttribute(): string
    {
        return $this->transaction_type->getDisplayName();
    }

    /**
     * Get the transaction type color.
     */
    public function getTransactionTypeColorAttribute(): string
    {
        return $this->transaction_type->getColor();
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
     * Check if transaction is positive (increases stock).
     */
    public function isPositive(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Check if transaction is negative (decreases stock).
     */
    public function isNegative(): bool
    {
        return $this->quantity < 0;
    }

    /**
     * Get the absolute quantity.
     */
    public function getAbsoluteQuantityAttribute(): float
    {
        return abs($this->quantity);
    }

    /**
     * Get the quantity with sign for display.
     */
    public function getQuantityWithSignAttribute(): string
    {
        $sign = $this->quantity >= 0 ? '+' : '';
        return $sign . number_format($this->quantity, 4);
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
     * Get the transaction description.
     */
    public function getDescriptionAttribute(): string
    {
        $description = $this->transaction_type->getDisplayName();
        
        if ($this->reference) {
            $description .= " - {$this->reference}";
        }
        
        if ($this->notes) {
            $description .= " ({$this->notes})";
        }
        
        return $description;
    }

    /**
     * Get the location information.
     */
    public function getLocationInfoAttribute(): array
    {
        return [
            'from' => $this->location_from,
            'to' => $this->location_to,
            'movement_type' => $this->location_from && $this->location_to ? 'transfer' : 
                           ($this->location_from ? 'from' : 'to'),
        ];
    }

    /**
     * Get the tracking information.
     */
    public function getTrackingInfoAttribute(): array
    {
        return [
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'serial_numbers' => $this->serial_numbers,
            'has_tracking' => !empty($this->batch_number) || !empty($this->serial_numbers) || $this->expiry_date,
        ];
    }

    /**
     * Check if transaction has tracking information.
     */
    public function hasTracking(): bool
    {
        return !empty($this->batch_number) || 
               !empty($this->serial_numbers) || 
               !empty($this->expiry_date);
    }

    /**
     * Get the reference object.
     */
    public function getReferenceObjectAttribute(): ?Model
    {
        if (!$this->reference || !$this->reference_type) {
            return null;
        }

        return match($this->reference_type) {
            'work_order' => $this->workOrder,
            'purchase_order' => $this->purchaseOrder,
            default => null,
        };
    }
}

/**
 * Transaction Type Enum
 */
enum TransactionType: string
{
    case PURCHASE = 'purchase';
    case RECEIVE = 'receive';
    case ISSUE = 'issue';
    case RETURN = 'return';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case RESERVATION = 'reservation';
    case RELEASE_RESERVATION = 'release_reservation';
    case DAMAGE = 'damage';
    case LOSS = 'loss';
    case EXPIRED = 'expired';
    case RECALL = 'recall';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PURCHASE => 'Purchase',
            self::RECEIVE => 'Receive',
            self::ISSUE => 'Issue',
            self::RETURN => 'Return',
            self::TRANSFER => 'Transfer',
            self::ADJUSTMENT => 'Adjustment',
            self::RESERVATION => 'Reservation',
            self::RELEASE_RESERVATION => 'Release Reservation',
            self::DAMAGE => 'Damage',
            self::LOSS => 'Loss',
            self::EXPIRED => 'Expired',
            self::RECALL => 'Recall',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PURCHASE => 'blue',
            self::RECEIVE => 'green',
            self::ISSUE => 'orange',
            self::RETURN => 'purple',
            self::TRANSFER => 'indigo',
            self::ADJUSTMENT => 'yellow',
            self::RESERVATION => 'gray',
            self::RELEASE_RESERVATION => 'gray',
            self::DAMAGE => 'red',
            self::LOSS => 'red',
            self::EXPIRED => 'red',
            self::RECALL => 'red',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PURCHASE => 'shopping-cart',
            self::RECEIVE => 'package',
            self::ISSUE => 'send',
            self::RETURN => 'rotate-ccw',
            self::TRANSFER => 'arrow-right-left',
            self::ADJUSTMENT => 'settings',
            self::RESERVATION => 'bookmark',
            self::RELEASE_RESERVATION => 'bookmark-off',
            self::DAMAGE => 'alert-triangle',
            self::LOSS => 'x-circle',
            self::EXPIRED => 'clock',
            self::RECALL => 'alert-circle',
        };
    }

    public function isPositive(): bool
    {
        return in_array($this, [self::PURCHASE, self::RECEIVE, self::RETURN, self::ADJUSTMENT, self::RELEASE_RESERVATION]);
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::ISSUE, self::TRANSFER, self::DAMAGE, self::LOSS, self::EXPIRED, self::RECALL]);
    }

    public function isNeutral(): bool
    {
        return in_array($this, [self::RESERVATION]);
    }
}

/**
 * Transaction Status Enum
 */
enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'gray',
            self::FAILED => 'red',
            self::ACTIVE => 'blue',
            self::EXPIRED => 'orange',
        };
    }
}
