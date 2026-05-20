<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DepreciationEntry extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'asset_depreciation_id',
        'period_date',
        'depreciation_amount',
        'book_value_before',
        'book_value_after',
        'accumulated_depreciation_before',
        'accumulated_depreciation_after',
        'description',
        'created_by',
    ];

    protected $casts = [
        'period_date' => 'date',
        'depreciation_amount' => 'decimal:15,2',
        'book_value_before' => 'decimal:15,2',
        'book_value_after' => 'decimal:15,2',
        'accumulated_depreciation_before' => 'decimal:15,2',
        'accumulated_depreciation_after' => 'decimal:15,2',
    ];

    /**
     * Get the asset depreciation schedule for this entry.
     */
    public function assetDepreciation()
    {
        return $this->belongsTo(AssetDepreciation::class);
    }

    /**
     * Get the asset for this entry.
     */
    public function asset()
    {
        return $this->assetDepreciation->asset;
    }

    /**
     * Get the user who created the entry.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include entries for specific period.
     */
    public function scopeByPeriod($query, $year, $month = null)
    {
        if ($month) {
            $query->whereYear('period_date', $year)->whereMonth('period_date', $month);
        } else {
            $query->whereYear('period_date', $year);
        }
    }

    /**
     * Scope a query to only include entries within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_date', [$startDate, $endDate]);
    }

    /**
     * Get the formatted depreciation amount.
     */
    public function getFormattedDepreciationAmountAttribute(): string
    {
        return number_format($this->depreciation_amount, 2);
    }

    /**
     * Get the formatted book values.
     */
    public function getFormattedBookValueBeforeAttribute(): string
    {
        return number_format($this->book_value_before, 2);
    }

    public function getFormattedBookValueAfterAttribute(): string
    {
        return number_format($this->book_value_after, 2);
    }

    /**
     * Get the formatted accumulated depreciation.
     */
    public function getFormattedAccumulatedDepreciationBeforeAttribute(): string
    {
        return number_format($this->accumulated_depreciation_before, 2);
    }

    public function getFormattedAccumulatedDepreciationAfterAttribute(): string
    {
        return number_format($this->accumulated_depreciation_after, 2);
    }

    /**
     * Get the period display.
     */
    public function getPeriodDisplayAttribute(): string
    {
        return $this->period_date->format('F Y');
    }

    /**
     * Get the year and month.
     */
    public function getYearMonthAttribute(): string
    {
        return $this->period_date->format('Y-m');
    }

    /**
     * Get the entry summary.
     */
    public function getEntrySummaryAttribute(): array
    {
        return [
            'asset_name' => $this->asset->name,
            'asset_id' => $this->asset->id,
            'period' => $this->period_display,
            'depreciation_amount' => $this->formatted_depreciation_amount,
            'book_value_before' => $this->formatted_book_value_before,
            'book_value_after' => $this->formatted_book_value_after,
            'accumulated_depreciation_before' => $this->formatted_accumulated_depreciation_before,
            'accumulated_depreciation_after' => $this->formatted_accumulated_depreciation_after,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
