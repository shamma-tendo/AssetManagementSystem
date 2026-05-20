<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssetDepreciation extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'depreciation_method_id',
        'purchase_cost',
        'salvage_value',
        'useful_life_years',
        'useful_life_hours',
        'depreciation_start_date',
        'depreciation_end_date',
        'current_book_value',
        'accumulated_depreciation',
        'annual_depreciation',
        'monthly_depreciation',
        'depreciation_rate',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_cost' => 'decimal:15,2',
        'salvage_value' => 'decimal:15,2',
        'useful_life_years' => 'integer',
        'useful_life_hours' => 'integer',
        'depreciation_start_date' => 'date',
        'depreciation_end_date' => 'date',
        'current_book_value' => 'decimal:15,2',
        'accumulated_depreciation' => 'decimal:15,2',
        'annual_depreciation' => 'decimal:15,2',
        'monthly_depreciation' => 'decimal:15,2',
        'depreciation_rate' => 'decimal:5,4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the asset for this depreciation schedule.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the depreciation method.
     */
    public function depreciationMethod()
    {
        return $this->belongsTo(DepreciationMethod::class);
    }

    /**
     * Get the user who created the depreciation schedule.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the depreciation schedule.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the depreciation entries for this schedule.
     */
    public function depreciationEntries()
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include schedules for specific method.
     */
    public function scopeByMethod($query, $methodId)
    {
        return $query->where('depreciation_method_id', $methodId);
    }

    /**
     * Scope a query to only include schedules that are fully depreciated.
     */
    public function scopeFullyDepreciated($query)
    {
        return $query->where('current_book_value', '<=', 'salvage_value');
    }

    /**
     * Scope a query to only include schedules that are partially depreciated.
     */
    public function scopePartiallyDepreciated($query)
    {
        return $query->where('current_book_value', '>', 'salvage_value')
                    ->where('accumulated_depreciation', '>', 0);
    }

    /**
     * Scope a query to only include schedules that haven't started depreciation.
     */
    public function scopeNotStarted($query)
    {
        return $query->where('accumulated_depreciation', 0)
                    ->where('depreciation_start_date', '>', now());
    }

    /**
     * Check if the asset is fully depreciated.
     */
    public function isFullyDepreciated(): bool
    {
        return $this->current_book_value <= $this->salvage_value;
    }

    /**
     * Check if depreciation has started.
     */
    public function hasDepreciationStarted(): bool
    {
        return $this->depreciation_start_date && $this->depreciation_start_date <= now();
    }

    /**
     * Check if depreciation has ended.
     */
    public function hasDepreciationEnded(): bool
    {
        return $this->depreciation_end_date && $this->depreciation_end_date < now();
    }

    /**
     * Get the depreciation status.
     */
    public function getDepreciationStatusAttribute(): string
    {
        if (!$this->hasDepreciationStarted()) {
            return 'not_started';
        } elseif ($this->isFullyDepreciated()) {
            return 'fully_depreciated';
        } elseif ($this->hasDepreciationEnded()) {
            return 'ended';
        } elseif ($this->accumulated_depreciation > 0) {
            return 'in_progress';
        } else {
            return 'not_started';
        }
    }

    /**
     * Get the depreciation status display.
     */
    public function getDepreciationStatusDisplayAttribute(): string
    {
        return match($this->depreciation_status) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'fully_depreciated' => 'Fully Depreciated',
            'ended' => 'Ended',
        };
    }

    /**
     * Get the depreciation status color.
     */
    public function getDepreciationStatusColorAttribute(): string
    {
        return match($this->depreciation_status) {
            'not_started' => 'gray',
            'in_progress' => 'blue',
            'fully_depreciated' => 'green',
            'ended' => 'orange',
        };
    }

    /**
     * Get the depreciation percentage.
     */
    public function getDepreciationPercentageAttribute(): float
    {
        if ($this->purchase_cost == 0) {
            return 0;
        }

        return ($this->accumulated_depreciation / $this->purchase_cost) * 100;
    }

    /**
     * Get the remaining depreciation.
     */
    public function getRemainingDepreciationAttribute(): float
    {
        return $this->current_book_value - $this->salvage_value;
    }

    /**
     * Get the remaining depreciation percentage.
     */
    public function getRemainingDepreciationPercentageAttribute(): float
    {
        if ($this->purchase_cost == 0) {
            return 0;
        }

        return ($this->remaining_depreciation / $this->purchase_cost) * 100;
    }

    /**
     * Get the years elapsed.
     */
    public function getYearsElapsedAttribute(): float
    {
        if (!$this->depreciation_start_date) {
            return 0;
        }

        $endDate = $this->depreciation_end_date ?? now();
        return $this->depreciation_start_date->diffInDays($endDate) / 365.25;
    }

    /**
     * Get the remaining years.
     */
    public function getRemainingYearsAttribute(): float
    {
        return max(0, $this->useful_life_years - $this->years_elapsed);
    }

    /**
     * Calculate depreciation for a specific period.
     */
    public function calculateDepreciationForPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        $method = $this->depreciationMethod->code;
        
        return match($method) {
            'straight_line' => $this->calculateStraightLineDepreciation($startDate, $endDate),
            'declining_balance' => $this->calculateDecliningBalanceDepreciation($startDate, $endDate),
            'sum_of_years' => $this->calculateSumOfYearsDepreciation($startDate, $endDate),
            'units_of_production' => $this->calculateUnitsOfProductionDepreciation($startDate, $endDate),
            default => 0,
        };
    }

    /**
     * Calculate straight-line depreciation.
     */
    private function calculateStraightLineDepreciation(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        if ($this->isFullyDepreciated() || !$this->hasDepreciationStarted()) {
            return 0;
        }

        $annualDepreciation = $this->annual_depreciation;
        $daysInYear = 365.25;
        $daysInPeriod = $startDate->diffInDays($endDate);
        
        return ($annualDepreciation / $daysInYear) * $daysInPeriod;
    }

    /**
     * Calculate declining balance depreciation.
     */
    private function calculateDecliningBalanceDepreciation(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        if ($this->isFullyDepreciated() || !$this->hasDepreciationStarted()) {
            return 0;
        }

        $rate = $this->depreciation_rate;
        $bookValue = $this->current_book_value;
        $salvageValue = $this->salvage_value;
        
        // Calculate depreciation for the period
        $periodDepreciation = $bookValue * $rate * ($startDate->diffInDays($endDate) / 365.25);
        
        // Don't depreciate below salvage value
        return max(0, min($periodDepreciation, $bookValue - $salvageValue));
    }

    /**
     * Calculate sum-of-years digits depreciation.
     */
    private function calculateSumOfYearsDepreciation(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        if ($this->isFullyDepreciated() || !$this->hasDepreciationStarted()) {
            return 0;
        }

        $usefulLife = $this->useful_life_years;
        $currentYear = min($usefulLife, ceil($this->years_elapsed));
        
        // Calculate sum of years digits
        $sumOfYears = ($usefulLife * ($usefulLife + 1)) / 2;
        $yearFraction = ($usefulLife - $currentYear + 1) / $sumOfYears;
        
        $totalDepreciable = $this->purchase_cost - $this->salvage_value;
        $annualDepreciation = $totalDepreciable * $yearFraction;
        
        $daysInYear = 365.25;
        $daysInPeriod = $startDate->diffInDays($endDate);
        
        return ($annualDepreciation / $daysInYear) * $daysInPeriod;
    }

    /**
     * Calculate units of production depreciation.
     */
    private function calculateUnitsOfProductionDepreciation(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        if ($this->isFullyDepreciated() || !$this->hasDepreciationStarted()) {
            return 0;
        }

        // This would require actual usage data from the asset
        // For now, return the monthly depreciation as a placeholder
        return $this->monthly_depreciation * $startDate->diffInMonths($endDate);
    }

    /**
     * Create depreciation entry for a period.
     */
    public function createDepreciationEntry(\Carbon\Carbon $periodDate, float $amount, string $description = null): DepreciationEntry
    {
        return $this->depreciationEntries()->create([
            'period_date' => $periodDate,
            'depreciation_amount' => $amount,
            'book_value_before' => $this->current_book_value,
            'book_value_after' => $this->current_book_value - $amount,
            'accumulated_depreciation_before' => $this->accumulated_depreciation,
            'accumulated_depreciation_after' => $this->accumulated_depreciation + $amount,
            'description' => $description ?? "Depreciation for {$periodDate->format('F Y')}",
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Process monthly depreciation.
     */
    public function processMonthlyDepreciation(): ?DepreciationEntry
    {
        if ($this->isFullyDepreciated() || !$this->hasDepreciationStarted()) {
            return null;
        }

        $periodDate = now()->startOfMonth();
        
        // Check if depreciation already processed for this period
        if ($this->depreciationEntries()->where('period_date', $periodDate)->exists()) {
            return null;
        }

        $startDate = $periodDate->copy();
        $endDate = $periodDate->copy()->endOfMonth();
        
        $depreciationAmount = $this->calculateDepreciationForPeriod($startDate, $endDate);
        
        if ($depreciationAmount <= 0) {
            return null;
        }

        // Create depreciation entry
        $entry = $this->createDepreciationEntry($periodDate, $depreciationAmount);
        
        // Update depreciation schedule
        $this->update([
            'accumulated_depreciation' => $this->accumulated_depreciation + $depreciationAmount,
            'current_book_value' => $this->current_book_value - $depreciationAmount,
        ]);

        return $entry;
    }

    /**
     * Recalculate depreciation schedule.
     */
    public function recalculateDepreciation(): void
    {
        $method = $this->depreciationMethod->code;
        
        match($method) {
            'straight_line' => $this->calculateStraightLineSchedule(),
            'declining_balance' => $this->calculateDecliningBalanceSchedule(),
            'sum_of_years' => $this->calculateSumOfYearsSchedule(),
            'units_of_production' => $this->calculateUnitsOfProductionSchedule(),
        };
    }

    /**
     * Calculate straight-line depreciation schedule.
     */
    private function calculateStraightLineSchedule(): void
    {
        $depreciableAmount = $this->purchase_cost - $this->salvage_value;
        $this->annual_depreciation = $depreciableAmount / $this->useful_life_years;
        $this->monthly_depreciation = $this->annual_depreciation / 12;
        $this->depreciation_rate = 1 / $this->useful_life_years;
    }

    /**
     * Calculate declining balance depreciation schedule.
     */
    private function calculateDecliningBalanceSchedule(): void
    {
        // Default to double declining balance (2x straight line rate)
        $this->depreciation_rate = (2 / $this->useful_life_years);
        $this->annual_depreciation = $this->purchase_cost * $this->depreciation_rate;
        $this->monthly_depreciation = $this->annual_depreciation / 12;
    }

    /**
     * Calculate sum-of-years depreciation schedule.
     */
    private function calculateSumOfYearsSchedule(): void
    {
        $depreciableAmount = $this->purchase_cost - $this->salvage_value;
        $usefulLife = $this->useful_life_years;
        
        // First year depreciation (highest)
        $sumOfYears = ($usefulLife * ($usefulLife + 1)) / 2;
        $this->annual_depreciation = $depreciableAmount * ($usefulLife / $sumOfYears);
        $this->monthly_depreciation = $this->annual_depreciation / 12;
        $this->depreciation_rate = $usefulLife / $sumOfYears;
    }

    /**
     * Calculate units of production depreciation schedule.
     */
    private function calculateUnitsOfProductionSchedule(): void
    {
        $depreciableAmount = $this->purchase_cost - $this->salvage_value;
        
        // This would require total estimated production units
        // For now, use straight-line as fallback
        $this->annual_depreciation = $depreciableAmount / $this->useful_life_years;
        $this->monthly_depreciation = $this->annual_depreciation / 12;
        $this->depreciation_rate = 1 / $this->useful_life_years;
    }

    /**
     * Get depreciation summary.
     */
    public function getDepreciationSummaryAttribute(): array
    {
        return [
            'method' => $this->depreciationMethod->name,
            'purchase_cost' => $this->purchase_cost,
            'salvage_value' => $this->salvage_value,
            'current_book_value' => $this->current_book_value,
            'accumulated_depreciation' => $this->accumulated_depreciation,
            'depreciation_percentage' => $this->depreciation_percentage,
            'remaining_depreciation' => $this->remaining_depreciation,
            'remaining_depreciation_percentage' => $this->remaining_depreciation_percentage,
            'annual_depreciation' => $this->annual_depreciation,
            'monthly_depreciation' => $this->monthly_depreciation,
            'useful_life_years' => $this->useful_life_years,
            'years_elapsed' => $this->years_elapsed,
            'remaining_years' => $this->remaining_years,
            'status' => $this->depreciation_status_display,
            'status_color' => $this->depreciation_status_color,
            'start_date' => $this->depreciation_start_date?->format('Y-m-d'),
            'end_date' => $this->depreciation_end_date?->format('Y-m-d'),
        ];
    }
}
