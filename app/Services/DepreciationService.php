<?php

namespace App\Services;

use App\Models\AssetDepreciation;
use App\Models\DepreciationEntry;
use App\Models\User;
use Carbon\Carbon;

class DepreciationService
{
    /**
     * Process monthly depreciation for all active assets.
     */
    public function processMonthlyDepreciation(): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'entries_created' => 0,
            'errors' => [],
        ];

        $periodDate = now()->startOfMonth();
        
        // Get all active depreciation schedules
        $depreciations = AssetDepreciation::active()
            ->where('depreciation_start_date', '<=', now())
            ->get();

        foreach ($depreciations as $depreciation) {
            try {
                // Check if depreciation already processed for this period
                if ($depreciation->depreciationEntries()->where('period_date', $periodDate)->exists()) {
                    $results['skipped']++;
                    continue;
                }

                // Process monthly depreciation
                $entry = $depreciation->processMonthlyDepreciation();
                
                if ($entry) {
                    $results['entries_created']++;
                }
                
                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'asset_id' => $depreciation->asset_id,
                    'asset_name' => $depreciation->asset->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Calculate depreciation projection for an asset.
     */
    public function calculateDepreciationProjection(AssetDepreciation $depreciation, int $years = 5): array
    {
        $projection = [];
        $currentBookValue = $depreciation->current_book_value;
        $currentAccumulated = $depreciation->accumulated_depreciation;
        $method = $depreciation->depreciationMethod->code;

        for ($year = 1; $year <= $years; $year++) {
            $yearDate = now()->addYears($year);
            $yearDepreciation = $this->calculateYearlyDepreciation($depreciation, $year, $method);
            
            // Don't depreciate below salvage value
            $maxDepreciable = $currentBookValue - $depreciation->salvage_value;
            $actualDepreciation = min($yearDepreciation, max(0, $maxDepreciable));
            
            $newBookValue = max($depreciation->salvage_value, $currentBookValue - $actualDepreciation);
            $newAccumulated = $currentAccumulated + $actualDepreciation;

            $projection[] = [
                'year' => now()->year + $year,
                'projected_depreciation' => $actualDepreciation,
                'projected_book_value' => $newBookValue,
                'projected_accumulated_depreciation' => $newAccumulated,
                'projected_depreciation_percentage' => $depreciation->purchase_cost > 0 
                    ? ($newAccumulated / $depreciation->purchase_cost) * 100 
                    : 0,
                'remaining_depreciation' => max(0, $newBookValue - $depreciation->salvage_value),
                'is_fully_depreciated' => $newBookValue <= $depreciation->salvage_value,
            ];

            $currentBookValue = $newBookValue;
            $currentAccumulated = $newAccumulated;

            // Stop if fully depreciated
            if ($newBookValue <= $depreciation->salvage_value) {
                break;
            }
        }

        return $projection;
    }

    /**
     * Calculate yearly depreciation for a specific year.
     */
    private function calculateYearlyDepreciation(AssetDepreciation $depreciation, int $yearOffset, string $method): float
    {
        return match($method) {
            'straight_line' => $depreciation->annual_depreciation,
            'declining_balance' => $this->calculateDecliningBalanceYearly($depreciation, $yearOffset),
            'sum_of_years' => $this->calculateSumOfYearsYearly($depreciation, $yearOffset),
            'units_of_production' => $depreciation->annual_depreciation, // Fallback
            default => 0,
        };
    }

    /**
     * Calculate declining balance depreciation for a specific year.
     */
    private function calculateDecliningBalanceYearly(AssetDepreciation $depreciation, int $yearOffset): float
    {
        $rate = $depreciation->depreciation_rate;
        $bookValue = $depreciation->current_book_value;
        
        // Calculate book value after yearOffset years
        for ($i = 0; $i < $yearOffset; $i++) {
            $yearDepreciation = $bookValue * $rate;
            $bookValue = max($depreciation->salvage_value, $bookValue - $yearDepreciation);
        }
        
        // Calculate depreciation for the target year
        $depreciation = $bookValue * $rate;
        $maxDepreciable = $bookValue - $depreciation->salvage_value;
        
        return max(0, min($depreciation, $maxDepreciable));
    }

    /**
     * Calculate sum-of-years depreciation for a specific year.
     */
    private function calculateSumOfYearsYearly(AssetDepreciation $depreciation, int $yearOffset): float
    {
        $usefulLife = $depreciation->useful_life_years;
        $currentYear = $depreciation->years_elapsed + $yearOffset;
        
        if ($currentYear > $usefulLife) {
            return 0;
        }
        
        // Calculate sum of years digits
        $sumOfYears = ($usefulLife * ($usefulLife + 1)) / 2;
        $yearFraction = ($usefulLife - $currentYear + 1) / $sumOfYears;
        
        $totalDepreciable = $depreciation->purchase_cost - $depreciation->salvage_value;
        
        return $totalDepreciable * $yearFraction;
    }

    /**
     * Generate depreciation schedule for an asset.
     */
    public function generateDepreciationSchedule(AssetDepreciation $depreciation): array
    {
        $schedule = [];
        $currentBookValue = $depreciation->purchase_cost;
        $accumulatedDepreciation = 0;
        $method = $depreciation->depreciationMethod->code;

        for ($year = 1; $year <= $depreciation->useful_life_years; $year++) {
            $yearDepreciation = $this->calculateYearlyDepreciation($depreciation, $year, $method);
            
            // Don't depreciate below salvage value
            $maxDepreciable = $currentBookValue - $depreciation->salvage_value;
            $actualDepreciation = min($yearDepreciation, max(0, $maxDepreciable));
            
            $currentBookValue = max($depreciation->salvage_value, $currentBookValue - $actualDepreciation);
            $accumulatedDepreciation += $actualDepreciation;

            $schedule[] = [
                'year' => $depreciation->depreciation_start_date->year + $year - 1,
                'beginning_book_value' => $currentBookValue + $actualDepreciation,
                'depreciation_expense' => $actualDepreciation,
                'accumulated_depreciation' => $accumulatedDepreciation,
                'ending_book_value' => $currentBookValue,
                'depreciation_rate' => $depreciation->purchase_cost > 0 
                    ? ($actualDepreciation / $depreciation->purchase_cost) * 100 
                    : 0,
            ];

            // Stop if fully depreciated
            if ($currentBookValue <= $depreciation->salvage_value) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Calculate tax depreciation (MACRS).
     */
    public function calculateTaxDepreciation(AssetDepreciation $depreciation, int $taxYear): array
    {
        // Simplified MACRS calculation (would need actual tax tables for production)
        $macrsRates = [
            1 => 0.2,    // 20%
            2 => 0.32,   // 32%
            3 => 0.192,  // 19.2%
            4 => 0.1152, // 11.52%
            5 => 0.1152, // 11.52%
            6 => 0.0576, // 5.76%
        ];

        $yearIndex = min($taxYear, 6);
        $rate = $macrsRates[$yearIndex] ?? 0;
        
        $taxDepreciation = $depreciation->purchase_cost * $rate;
        $accumulatedTaxDepreciation = $depreciation->purchase_cost * array_sum(array_slice($macrsRates, 0, $yearIndex));

        return [
            'tax_year' => $taxYear,
            'macrs_rate' => $rate * 100,
            'tax_depreciation' => $taxDepreciation,
            'accumulated_tax_depreciation' => $accumulatedTaxDepreciation,
            'remaining_tax_basis' => $depreciation->purchase_cost - $accumulatedTaxDepreciation,
        ];
    }

    /**
     * Generate depreciation comparison between book and tax methods.
     */
    public function generateDepreciationComparison(AssetDepreciation $depreciation): array
    {
        $bookSchedule = $this->generateDepreciationSchedule($depreciation);
        $comparison = [];

        foreach ($bookSchedule as $yearIndex => $bookYear) {
            $taxYear = $yearIndex + 1;
            $taxDepreciation = $this->calculateTaxDepreciation($depreciation, $taxYear);

            $comparison[] = [
                'year' => $bookYear['year'],
                'book_depreciation' => $bookYear['depreciation_expense'],
                'book_accumulated' => $bookYear['accumulated_depreciation'],
                'book_book_value' => $bookYear['ending_book_value'],
                'tax_depreciation' => $taxDepreciation['tax_depreciation'],
                'tax_accumulated' => $taxDepreciation['accumulated_tax_depreciation'],
                'tax_book_value' => $taxDepreciation['remaining_tax_basis'],
                'difference' => $bookYear['depreciation_expense'] - $taxDepreciation['tax_depreciation'],
                'accumulated_difference' => $bookYear['accumulated_depreciation'] - $taxDepreciation['accumulated_tax_depreciation'],
            ];
        }

        return $comparison;
    }

    /**
     * Calculate depreciation for disposal.
     */
    public function calculateDepreciationOnDisposal(AssetDepreciation $depreciation, Carbon $disposalDate): array
    {
        if (!$depreciation->hasDepreciationStarted()) {
            return [
                'depreciation_to_date' => 0,
                'book_value_at_disposal' => $depreciation->purchase_cost,
                'gain_loss' => 0,
                'gain_loss_amount' => 0,
            ];
        }

        // Calculate depreciation up to disposal date
        $startDate = $depreciation->depreciation_start_date;
        $yearsElapsed = $startDate->diffInDays($disposalDate) / 365.25;
        
        $depreciationToDate = $depreciation->annual_depreciation * $yearsElapsed;
        $maxDepreciable = $depreciation->purchase_cost - $depreciation->salvage_value;
        $actualDepreciationToDate = min($depreciationToDate, $maxDepreciable);
        
        $bookValueAtDisposal = max($depreciation->salvage_value, 
            $depreciation->purchase_cost - $actualDepreciationToDate);

        // Calculate gain/loss (assuming disposal proceeds)
        $disposalProceeds = $depreciation->salvage_value; // Default to salvage value
        $gainLoss = $disposalProceeds - $bookValueAtDisposal;
        
        return [
            'depreciation_to_date' => $actualDepreciationToDate,
            'book_value_at_disposal' => $bookValueAtDisposal,
            'disposal_proceeds' => $disposalProceeds,
            'gain_loss' => $gainLoss >= 0 ? 'gain' : 'loss',
            'gain_loss_amount' => abs($gainLoss),
            'years_depreciated' => $yearsElapsed,
        ];
    }

    /**
     * Analyze depreciation efficiency.
     */
    public function analyzeDepreciationEfficiency(): array
    {
        $depreciations = AssetDepreciation::with(['asset', 'depreciationMethod'])->get();

        $analysis = [
            'total_assets' => $depreciations->count(),
            'average_depreciation_rate' => $depreciations->avg('depreciation_rate') * 100,
            'average_useful_life' => $depreciations->avg('useful_life_years'),
            'fully_depreciated_percentage' => ($depreciations->where('current_book_value', '<=', 'salvage_value')->count() / $depreciations->count()) * 100,
            'by_method' => [],
            'by_age' => [],
            'by_value' => [],
        ];

        // Analysis by method
        $byMethod = $depreciations->groupBy('depreciationMethod.name');
        foreach ($byMethod as $methodName => $group) {
            $analysis['by_method'][$methodName] = [
                'count' => $group->count(),
                'average_rate' => $group->avg('depreciation_rate') * 100,
                'average_life' => $group->avg('useful_life_years'),
                'total_value' => $group->sum('current_book_value'),
                'fully_depreciated_count' => $group->where('current_book_value', '<=', 'salvage_value')->count(),
            ];
        }

        // Analysis by age
        $ageGroups = [
            '0-2 years' => $depreciations->where('years_elapsed', '<=', 2),
            '2-5 years' => $depreciations->where('years_elapsed', '>', 2)->where('years_elapsed', '<=', 5),
            '5-10 years' => $depreciations->where('years_elapsed', '>', 5)->where('years_elapsed', '<=', 10),
            '10+ years' => $depreciations->where('years_elapsed', '>', 10),
        ];

        foreach ($ageGroups as $ageRange => $group) {
            $analysis['by_age'][$ageRange] = [
                'count' => $group->count(),
                'total_value' => $group->sum('current_book_value'),
                'average_depreciation_percentage' => $group->avg(function ($item) {
                    return $item->purchase_cost > 0 ? ($item->accumulated_depreciation / $item->purchase_cost) * 100 : 0;
                }),
                'fully_depreciated_count' => $group->where('current_book_value', '<=', 'salvage_value')->count(),
            ];
        }

        // Analysis by value
        $valueRanges = [
            'Low (< $1,000)' => $depreciations->where('current_book_value', '<', 1000),
            'Medium ($1,000 - $10,000)' => $depreciations->where('current_book_value', '>=', 1000)->where('current_book_value', '<', 10000),
            'High ($10,000 - $100,000)' => $depreciations->where('current_book_value', '>=', 10000)->where('current_book_value', '<', 100000),
            'Very High (> $100,000)' => $depreciations->where('current_book_value', '>=', 100000),
        ];

        foreach ($valueRanges as $valueRange => $group) {
            $analysis['by_value'][$valueRange] = [
                'count' => $group->count(),
                'total_value' => $group->sum('current_book_value'),
                'average_depreciation_percentage' => $group->avg(function ($item) {
                    return $item->purchase_cost > 0 ? ($item->accumulated_depreciation / $item->purchase_cost) * 100 : 0;
                }),
                'fully_depreciated_count' => $group->where('current_book_value', '<=', 'salvage_value')->count(),
            ];
        }

        return $analysis;
    }

    /**
     * Generate depreciation recommendations.
     */
    public function generateDepreciationRecommendations(): array
    {
        $recommendations = [];
        $depreciations = AssetDepreciation::with(['asset', 'depreciationMethod'])->get();

        // Check for assets that might need method changes
        foreach ($depreciations as $depreciation) {
            $agePercentage = ($depreciation->years_elapsed / $depreciation->useful_life_years) * 100;
            $depreciationPercentage = $depreciation->depreciation_percentage;

            // Recommend method changes for certain patterns
            if ($depreciation->depreciationMethod->code === 'straight_line' && $agePercentage > 50 && $depreciationPercentage < 30) {
                $recommendations[] = [
                    'type' => 'method_change',
                    'priority' => 'medium',
                    'asset_id' => $depreciation->asset_id,
                    'asset_name' => $depreciation->asset->name,
                    'message' => "Consider switching to accelerated depreciation for {$depreciation->asset->name}",
                    'reason' => 'Asset is aging but has low depreciation percentage',
                    'suggested_method' => 'declining_balance',
                    'potential_impact' => 'Higher depreciation deductions in early years',
                ];
            }

            // Check for fully depreciated assets still in use
            if ($depreciation->isFullyDepreciated() && $depreciation->asset->status === 'active') {
                $recommendations[] = [
                    'type' => 'asset_review',
                    'priority' => 'high',
                    'asset_id' => $depreciation->asset_id,
                    'asset_name' => $depreciation->asset->name,
                    'message' => "Fully depreciated asset {$depreciation->asset->name} is still active",
                    'reason' => 'Asset may need replacement or continued use evaluation',
                    'suggested_action' => 'Evaluate asset condition and replacement needs',
                    'potential_impact' => 'Optimal asset replacement timing',
                ];
            }

            // Check for assets with unusual depreciation patterns
            if ($depreciation->years_elapsed > 0 && $depreciationPercentage > 95) {
                $recommendations[] = [
                    'type' => 'depreciation_review',
                    'priority' => 'low',
                    'asset_id' => $depreciation->asset_id,
                    'asset_name' => $depreciation->asset->name,
                    'message' => "Asset {$depreciation->asset->name} has high depreciation percentage",
                    'reason' => 'May indicate incorrect useful life or salvage value',
                    'suggested_action' => 'Review depreciation parameters',
                    'potential_impact' => 'More accurate financial reporting',
                ];
            }
        }

        return [
            'recommendations' => $recommendations,
            'summary' => [
                'total_recommendations' => count($recommendations),
                'by_priority' => collect($recommendations)->groupBy('priority')->map->count(),
                'by_type' => collect($recommendations)->groupBy('type')->map->count(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
}
