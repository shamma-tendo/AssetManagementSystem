<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockCount extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'part_stock_location_id',
        'old_quantity',
        'new_quantity',
        'variance',
        'variance_percentage',
        'reason',
        'counted_by',
        'counted_at',
        'notes',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'old_quantity' => 'decimal:10,4',
        'new_quantity' => 'decimal:10,4',
        'variance' => 'decimal:10,4',
        'variance_percentage' => 'decimal:8,4',
        'counted_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    /**
     * Get the stock location for this count.
     */
    public function stockLocation()
    {
        return $this->belongsTo(PartStockLocation::class);
    }

    /**
     * Get the user who performed the count.
     */
    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /**
     * Get the part for this stock count.
     */
    public function part()
    {
        return $this->stockLocation->part;
    }

    /**
     * Scope a query to only include counts within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('counted_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include counts with significant variance.
     */
    public function scopeWithSignificantVariance($query, $threshold = 5.0)
    {
        return $query->whereRaw('ABS(variance_percentage) >= ?', [$threshold]);
    }

    /**
     * Get the variance status.
     */
    public function getVarianceStatusAttribute(): string
    {
        $absVariance = abs($this->variance_percentage);
        
        if ($absVariance >= 20) {
            return 'high_variance';
        } elseif ($absVariance >= 10) {
            return 'medium_variance';
        } elseif ($absVariance >= 5) {
            return 'low_variance';
        } else {
            return 'no_variance';
        }
    }

    /**
     * Get the variance status display.
     */
    public function getVarianceStatusDisplayAttribute(): string
    {
        return match($this->variance_status) {
            'high_variance' => 'High Variance',
            'medium_variance' => 'Medium Variance',
            'low_variance' => 'Low Variance',
            'no_variance' => 'No Variance',
        };
    }

    /**
     * Get the variance status color.
     */
    public function getVarianceStatusColorAttribute(): string
    {
        return match($this->variance_status) {
            'high_variance' => 'red',
            'medium_variance' => 'orange',
            'low_variance' => 'yellow',
            'no_variance' => 'green',
        };
    }

    /**
     * Check if there's a significant variance.
     */
    public function hasSignificantVariance(float $threshold = 5.0): bool
    {
        return abs($this->variance_percentage) >= $threshold;
    }

    /**
     * Get the formatted variance.
     */
    public function getFormattedVarianceAttribute(): string
    {
        $sign = $this->variance >= 0 ? '+' : '';
        return $sign . number_format($this->variance, 4);
    }

    /**
     * Get the formatted variance percentage.
     */
    public function getFormattedVariancePercentageAttribute(): string
    {
        $sign = $this->variance_percentage >= 0 ? '+' : '';
        return $sign . number_format($this->variance_percentage, 2) . '%';
    }

    /**
     * Get the count summary.
     */
    public function getCountSummaryAttribute(): array
    {
        return [
            'part_name' => $this->part->name,
            'location_name' => $this->stockLocation->location->name,
            'bin_location' => $this->stockLocation->bin_location,
            'old_quantity' => $this->old_quantity,
            'new_quantity' => $this->new_quantity,
            'variance' => $this->formatted_variance,
            'variance_percentage' => $this->formatted_variance_percentage,
            'variance_status' => $this->variance_status_display,
            'variance_status_color' => $this->variance_status_color,
            'reason' => $this->reason,
            'counted_by' => $this->counter->full_name,
            'counted_at' => $this->counted_at->format('Y-m-d H:i'),
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
        ];
    }
}
