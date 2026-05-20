<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\DB;

class Part extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'part_number',
        'manufacturer_part_number',
        'supplier_part_number',
        'category_id',
        'manufacturer_id',
        'supplier_id',
        'unit_of_measure',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'reorder_quantity',
        'unit_cost',
        'average_cost',
        'selling_price',
        'lead_time_days',
        'shelf_life_days',
        'storage_location',
        'bin_location',
        'warehouse_location',
        'barcode',
        'qr_code',
        'serial_number_required',
        'batch_number_required',
        'expiry_date_required',
        'hazardous_material',
        'safety_data_sheet_url',
        'specifications',
        'dimensions',
        'weight_kg',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_stock' => 'float',
        'minimum_stock' => 'float',
        'maximum_stock' => 'float',
        'reorder_point' => 'float',
        'reorder_quantity' => 'float',
        'unit_cost' => 'float',
        'average_cost' => 'float',
        'selling_price' => 'float',
        'lead_time_days' => 'integer',
        'shelf_life_days' => 'integer',
        'weight_kg' => 'float',
        'serial_number_required' => 'boolean',
        'batch_number_required' => 'boolean',
        'expiry_date_required' => 'boolean',
        'hazardous_material' => 'boolean',
        'is_active' => 'boolean',
        'specifications' => 'array',
        'dimensions' => 'array',
    ];

    /**
     * Get the category for this part.
     */
    public function category()
    {
        return $this->belongsTo(PartCategory::class);
    }

    /**
     * Get the manufacturer for this part.
     */
    public function manufacturer()
    {
        return $this->belongsTo(Supplier::class, 'manufacturer_id');
    }

    /**
     * Get the primary supplier for this part.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the user who created the part.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the part.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the inventory transactions for this part.
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get the stock locations for this part.
     */
    public function stockLocations()
    {
        return $this->hasMany(PartStockLocation::class);
    }

    /**
     * Get the purchase orders for this part.
     */
    public function purchaseOrders()
    {
        return $this->belongsToMany(PurchaseOrder::class)
            ->withPivot(['quantity', 'unit_cost', 'total_cost', 'received_quantity'])
            ->withTimestamps();
    }

    /**
     * Get the work orders that use this part.
     */
    public function workOrders()
    {
        return $this->belongsToMany(WorkOrder::class)
            ->withPivot(['quantity_used', 'unit_cost', 'total_cost'])
            ->withTimestamps();
    }

    /**
     * Get the maintenance schedules that require this part.
     */
    public function maintenanceSchedules()
    {
        return $this->belongsToMany(MaintenanceSchedule::class)
            ->withPivot(['required_quantity', 'notes'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active parts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include parts with low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= minimum_stock');
    }

    /**
     * Scope a query to only include parts that need reordering.
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereRaw('current_stock <= reorder_point');
    }

    /**
     * Scope a query to only include parts with hazardous materials.
     */
    public function scopeHazardous($query)
    {
        return $query->where('hazardous_material', true);
    }

    /**
     * Scope a query to only include parts that expire.
     */
    public function scopeExpiring($query)
    {
        return $query->where('expiry_date_required', true);
    }

    /**
     * Scope a query to only include parts from specific category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to only include parts from specific supplier.
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope a query to only include parts from specific manufacturer.
     */
    public function scopeByManufacturer($query, $manufacturerId)
    {
        return $query->where('manufacturer_id', $manufacturerId);
    }

    /**
     * Get the stock level status.
     */
    public function getStockLevelStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->current_stock <= $this->minimum_stock) {
            return 'low_stock';
        } elseif ($this->current_stock <= $this->reorder_point) {
            return 'reorder_point';
        } elseif ($this->current_stock >= $this->maximum_stock) {
            return 'overstock';
        } else {
            return 'normal';
        }
    }

    /**
     * Get the stock level status display.
     */
    public function getStockLevelStatusDisplayAttribute(): string
    {
        return match($this->stock_level_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'reorder_point' => 'Reorder Point',
            'overstock' => 'Overstock',
            'normal' => 'Normal Stock',
        };
    }

    /**
     * Get the stock level status color.
     */
    public function getStockLevelStatusColorAttribute(): string
    {
        return match($this->stock_level_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'orange',
            'reorder_point' => 'yellow',
            'overstock' => 'blue',
            'normal' => 'green',
        };
    }

    /**
     * Check if part is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Check if part has low stock.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Check if part needs reordering.
     */
    public function needsReordering(): bool
    {
        return $this->current_stock <= $this->reorder_point;
    }

    /**
     * Check if part is overstocked.
     */
    public function isOverstocked(): bool
    {
        return $this->current_stock >= $this->maximum_stock;
    }

    /**
     * Get the total inventory value.
     */
    public function getTotalInventoryValueAttribute(): float
    {
        return $this->current_stock * $this->average_cost;
    }

    /**
     * Get the reorder value.
     */
    public function getReorderValueAttribute(): float
    {
        return $this->reorder_quantity * $this->unit_cost;
    }

    /**
     * Get the available stock (excluding reserved stock).
     */
    public function getAvailableStockAttribute(): float
    {
        $reservedStock = $this->inventoryTransactions()
            ->where('transaction_type', 'reservation')
            ->where('status', 'active')
            ->sum('quantity');

        return max(0, $this->current_stock - $reservedStock);
    }

    /**
     * Get the reserved stock.
     */
    public function getReservedStockAttribute(): float
    {
        return $this->inventoryTransactions()
            ->where('transaction_type', 'reservation')
            ->where('status', 'active')
            ->sum('quantity');
    }

    /**
     * Update stock levels.
     */
    public function updateStock(float $quantity, string $transactionType, string $reference = null, array $metadata = []): InventoryTransaction
    {
        DB::beginTransaction();
        try {
            // Create inventory transaction
            $transaction = $this->inventoryTransactions()->create([
                'quantity' => $quantity,
                'transaction_type' => $transactionType,
                'reference' => $reference,
                'metadata' => $metadata,
                'performed_by' => auth()->id(),
                'performed_at' => now(),
            ]);

            // Update current stock using raw SQL to avoid decimal-cast issues
            DB::table('parts')
                ->where('id', $this->id)
                ->update([
                    'current_stock' => DB::raw('current_stock + ' . (float) $quantity),
                    'updated_at'    => now()->toDateTimeString(),
                ]);
            $this->refresh();

            // Update average cost if it's a purchase
            if ($transactionType === 'purchase' && $quantity > 0) {
                $this->updateAverageCost($metadata['unit_cost'] ?? 0, $quantity);
            }

            DB::commit();

            return $transaction;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update average cost.
     */
    private function updateAverageCost(float $newCost, float $quantity): void
    {
        $this->refresh();
        $currentStock = (float) ($this->getRawOriginal('current_stock') ?? 0);
        $currentCost  = (float) ($this->getRawOriginal('average_cost') ?? 0);

        $avgCost = $currentStock > 0
            ? (($currentCost * ($currentStock - $quantity)) + ($newCost * $quantity)) / $currentStock
            : $newCost;

        DB::table('parts')
            ->where('id', $this->id)
            ->update([
                'average_cost' => round($avgCost, 4),
                'updated_at'   => now()->toDateTimeString(),
            ]);
        $this->refresh();
    }

    /**
     * Reserve stock.
     */
    public function reserveStock(float $quantity, string $reference, array $metadata = []): InventoryTransaction
    {
        if ($this->available_stock < $quantity) {
            throw new \Exception('Insufficient stock available for reservation');
        }

        return $this->updateStock(-$quantity, 'reservation', $reference, $metadata);
    }

    /**
     * Release reserved stock.
     */
    public function releaseReservation(float $quantity, string $reference, array $metadata = []): InventoryTransaction
    {
        return $this->updateStock($quantity, 'release_reservation', $reference, $metadata);
    }

    /**
     * Issue stock.
     */
    public function issueStock(float $quantity, string $reference, array $metadata = []): InventoryTransaction
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception('Insufficient stock available');
        }

        return $this->updateStock(-$quantity, 'issue', $reference, $metadata);
    }

    /**
     * Receive stock.
     */
    public function receiveStock(float $quantity, string $reference, array $metadata = []): InventoryTransaction
    {
        return $this->updateStock($quantity, 'receive', $reference, $metadata);
    }

    /**
     * Adjust stock.
     */
    public function adjustStock(float $quantity, string $reason, array $metadata = []): InventoryTransaction
    {
        return $this->updateStock($quantity, 'adjustment', $reason, $metadata);
    }

    /**
     * Get stock usage statistics.
     */
    public function getStockUsageStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $issuedQuantity = $this->inventoryTransactions()
            ->where('transaction_type', 'issue')
            ->where('performed_at', '>=', $startDate)
            ->sum('quantity');

        $receivedQuantity = $this->inventoryTransactions()
            ->where('transaction_type', 'receive')
            ->where('performed_at', '>=', $startDate)
            ->sum('quantity');

        $averageDailyUsage = $days > 0 ? $issuedQuantity / $days : 0;
        $daysOfStock = $averageDailyUsage > 0 ? $this->current_stock / $averageDailyUsage : 999;

        return [
            'issued_quantity' => $issuedQuantity,
            'received_quantity' => $receivedQuantity,
            'average_daily_usage' => $averageDailyUsage,
            'days_of_stock' => $daysOfStock,
            'period_days' => $days,
        ];
    }

    /**
     * Get stock forecast.
     */
    public function getStockForecast(int $days = 90): array
    {
        $stats = $this->getStockUsageStats(30);
        $averageDailyUsage = $stats['average_daily_usage'];
        
        $forecast = [];
        $currentStock = $this->current_stock;
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->addDays($i);
            $currentStock -= $averageDailyUsage;
            
            $forecast[] = [
                'date' => $date->format('Y-m-d'),
                'projected_stock' => max(0, $currentStock),
                'stock_status' => $currentStock <= 0 ? 'out_of_stock' : 
                              ($currentStock <= $this->minimum_stock ? 'low_stock' : 'normal'),
            ];
        }

        return $forecast;
    }

    /**
     * Get part specifications formatted for display.
     */
    public function getFormattedSpecificationsAttribute(): array
    {
        $specs = $this->specifications ?? [];
        
        return [
            'dimensions' => $this->dimensions ?? [],
            'weight' => $this->weight_kg ? $this->weight_kg . ' kg' : null,
            'unit_of_measure' => $this->unit_of_measure,
            'lead_time' => $this->lead_time_days ? $this->lead_time_days . ' days' : null,
            'shelf_life' => $this->shelf_life_days ? $this->shelf_life_days . ' days' : null,
            'additional_specs' => $specs,
        ];
    }

    /**
     * Get part image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        // This would typically point to a stored image
        return null;
    }

    /**
     * Get part documents.
     */
    public function getDocumentsAttribute(): array
    {
        return [
            'safety_data_sheet' => $this->safety_data_sheet_url,
            'specification_sheet' => null, // Would be stored separately
            'user_manual' => null, // Would be stored separately
        ];
    }

    /**
     * Get part cross-references.
     */
    public function getCrossReferencesAttribute(): array
    {
        return [
            'manufacturer_part_number' => $this->manufacturer_part_number,
            'supplier_part_number' => $this->supplier_part_number,
            'alternative_parts' => [], // Would be stored in a separate table
            'compatible_parts' => [], // Would be stored in a separate table
        ];
    }

    /**
     * Recalculate total stock from all locations.
     */
    public function recalculateTotalStock(): void
    {
        $totalStock = $this->stockLocations()->sum('quantity');
        $this->update(['current_stock' => $totalStock]);
    }
}
