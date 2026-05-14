<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\DepreciationRecord;
use Illuminate\Support\Facades\DB;

class DepreciationService
{
    public function calculateStraightLine(Asset $asset): float
    {
        $cost = $asset->purchase_cost;
        $salvage = $asset->salvage_value;
        $life = $asset->useful_life_years;
        $years = $asset->purchase_date->diffInYears(now());

        $currentValue = $cost - (($cost - $salvage) / $life * $years);
        $currentValue = max($currentValue, $salvage);

        return round($currentValue, 2);
    }

    public function calculateDecliningBalance(Asset $asset): float
    {
        $cost = $asset->purchase_cost;
        $salvage = $asset->salvage_value;
        $life = $asset->useful_life_years;
        $years = $asset->purchase_date->diffInYears(now());

        $currentValue = $cost * pow(1 - (2 / $life), $years);
        $currentValue = max($currentValue, $salvage);

        return round($currentValue, 2);
    }

    public function calculate(Asset $asset, string $method = 'straight_line'): float
    {
        $currentValue = match($method) {
            'straight_line' => $this->calculateStraightLine($asset),
            'declining_balance' => $this->calculateDecliningBalance($asset),
            default => throw new \InvalidArgumentException('Unknown depreciation method'),
        };

        $asset->update(['current_value' => $currentValue]);

        // Record depreciation for current year
        $year = now()->year;
        $lastRecord = $asset->depreciationRecords()
            ->where('year', $year)
            ->first();

        if (!$lastRecord) {
            DepreciationRecord::create([
                'asset_id' => $asset->id,
                'year' => $year,
                'method' => $method,
                'beginning_book_value' => $asset->purchase_cost,
                'depreciation_expense' => $asset->purchase_cost - $currentValue,
                'book_value' => $currentValue,
                'accumulated_depreciation' => $asset->purchase_cost - $currentValue,
            ]);
        }

        return $currentValue;
    }

    public function calculateTotalCostOfOwnership(Asset $asset): array
    {
        $maintenanceCost = (float) $asset->maintenanceRecords()->sum('total_cost');
        $workOrderIds = $asset->workOrders()->pluck('id');
        $replacementParts = (float) DB::table('work_order_parts')
            ->whereIn('work_order_id', $workOrderIds)
            ->sum('total_cost');

        $totalCost = (float) $asset->purchase_cost + $maintenanceCost + $replacementParts;

        return [
            'purchase_cost' => $asset->purchase_cost,
            'maintenance_cost' => $maintenanceCost,
            'parts_cost' => $replacementParts,
            'total_cost' => $totalCost,
            'current_value' => $asset->current_value,
            'years_owned' => $asset->purchase_date->diffInYears(now()),
            'average_annual_cost' => $totalCost / max($asset->purchase_date->diffInYears(now()), 1),
        ];
    }

    public function getAssetDepreciationTrend(Asset $asset, int $years = 5): array
    {
        $trend = [];
        for ($i = 0; $i < $years; $i++) {
            $year = now()->year - $i;
            $record = $asset->depreciationRecords()
                ->where('year', $year)
                ->first();

            if ($record) {
                $trend[$year] = $record->book_value;
            }
        }

        return array_reverse($trend);
    }
}
