<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PartStockLocation extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'part_id',
        'location_id',
        'bin_location',
        'quantity',
        'minimum_quantity',
        'maximum_quantity',
        'last_counted_at',
        'last_counted_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:10,4',
        'minimum_quantity' => 'decimal:10,4',
        'maximum_quantity' => 'decimal:10,4',
        'last_counted_at' => 'datetime',
    ];

    /**
     * Get the part for this stock location.
     */
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the location for this stock.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who last counted this stock.
     */
    public function lastCounter()
    {
        return $this->belongsTo(User::class, 'last_counted_by');
    }

    /**
     * Get the stock count history.
     */
    public function stockCounts()
    {
        return $this->hasMany(StockCount::class);
    }

    /**
     * Scope a query to only include stock at specific location.
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope a query to only include stock with low quantity.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= minimum_quantity');
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }

    /**
     * Check if stock is overstocked.
     */
    public function isOverstocked(): bool
    {
        return $this->quantity >= $this->maximum_quantity;
    }

    /**
     * Get the stock status.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->quantity <= $this->minimum_quantity) {
            return 'low_stock';
        } elseif ($this->quantity >= $this->maximum_quantity) {
            return 'overstock';
        } else {
            return 'normal';
        }
    }

    /**
     * Get the stock status display.
     */
    public function getStockStatusDisplayAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'overstock' => 'Overstock',
            'normal' => 'Normal',
        };
    }

    /**
     * Get the stock status color.
     */
    public function getStockStatusColorAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'orange',
            'overstock' => 'blue',
            'normal' => 'green',
        };
    }

    /**
     * Update stock quantity.
     */
    public function updateQuantity(float $newQuantity, string $reason, User $counter): void
    {
        $oldQuantity = $this->quantity;
        
        $this->update([
            'quantity' => $newQuantity,
            'last_counted_at' => now(),
            'last_counted_by' => $counter->id,
        ]);

        // Create stock count record
        $this->stockCounts()->create([
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'variance' => $newQuantity - $oldQuantity,
            'reason' => $reason,
            'counted_by' => $counter->id,
            'counted_at' => now(),
        ]);

        // Update part's total stock
        $this->part->recalculateTotalStock();
    }

    /**
     * Get the location summary.
     */
    public function getLocationSummaryAttribute(): array
    {
        return [
            'location_name' => $this->location->name,
            'bin_location' => $this->bin_location,
            'quantity' => $this->quantity,
            'minimum_quantity' => $this->minimum_quantity,
            'maximum_quantity' => $this->maximum_quantity,
            'stock_status' => $this->stock_status_display,
            'stock_status_color' => $this->stock_status_color,
            'last_counted_at' => $this->last_counted_at?->format('Y-m-d H:i'),
            'last_counted_by' => $this->lastCounter?->full_name,
        ];
    }
}
