<?php

namespace App\Services;

use App\Models\Part;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PartStockLocation;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryService
{
    /**
     * Process automatic reordering for parts that need it.
     */
    public function processAutomaticReordering(): array
    {
        $results = [
            'processed' => 0,
            'created_orders' => 0,
            'errors' => [],
        ];

        // Get parts that need reordering
        $partsNeedingReorder = Part::needsReorder()
            ->where('reorder_quantity', '>', 0)
            ->with(['supplier'])
            ->get();

        foreach ($partsNeedingReorder as $part) {
            try {
                $this->createReorderPurchaseOrder($part);
                $results['created_orders']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'part_id' => $part->id,
                    'part_name' => $part->name,
                    'error' => $e->getMessage(),
                ];
            }
            $results['processed']++;
        }

        return $results;
    }

    /**
     * Create a reorder purchase order for a part.
     */
    private function createReorderPurchaseOrder(Part $part): PurchaseOrder
    {
        if (!$part->supplier) {
            throw new \Exception("Part {$part->name} has no supplier assigned");
        }

        DB::beginTransaction();
        try {
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Create purchase order
            $order = PurchaseOrder::create([
                'order_number' => $orderNumber,
                'supplier_id' => $part->supplier_id,
                'status' => 'draft',
                'priority' => $this->determineReorderPriority($part),
                'order_date' => today(),
                'expected_delivery_date' => now()->addDays($part->lead_time_days ?? 30),
                'created_by' => 1, // System user
            ]);

            // Create order item
            $unitCost = $part->unit_cost ?? $part->average_cost ?? 0;
            $totalCost = $part->reorder_quantity * $unitCost;

            $order->items()->create([
                'part_id' => $part->id,
                'quantity' => $part->reorder_quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'expected_delivery_date' => $order->expected_delivery_date,
            ]);

            // Update order totals
            $taxAmount = $totalCost * 0.1; // 10% tax rate
            $order->update([
                'subtotal' => $totalCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalCost + $taxAmount,
            ]);

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Determine reorder priority based on stock level.
     */
    private function determineReorderPriority(Part $part): string
    {
        if ($part->current_stock <= 0) {
            return 'critical';
        } elseif ($part->current_stock <= $part->minimum_stock) {
            return 'urgent';
        } elseif ($part->current_stock <= $part->reorder_point * 0.5) {
            return 'high';
        } else {
            return 'normal';
        }
    }

    /**
     * Generate order number.
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ym');
        $sequence = PurchaseOrder::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Calculate inventory valuation.
     */
    public function calculateInventoryValuation(): array
    {
        $totalValue = Part::sum(DB::raw('current_stock * average_cost'));
        
        $valuationByCategory = Part::join('part_categories', 'parts.category_id', '=', 'part_categories.id')
            ->select('part_categories.name as category_name')
            ->selectRaw('SUM(parts.current_stock * parts.average_cost) as category_value')
            ->groupBy('part_categories.id', 'part_categories.name')
            ->orderBy('category_value', 'desc')
            ->get();

        $valuationByLocation = PartStockLocation::join('locations', 'part_stock_locations.location_id', '=', 'locations.id')
            ->join('parts', 'part_stock_locations.part_id', '=', 'parts.id')
            ->select('locations.name as location_name')
            ->selectRaw('SUM(part_stock_locations.quantity * parts.average_cost) as location_value')
            ->groupBy('locations.id', 'locations.name')
            ->orderBy('location_value', 'desc')
            ->get();

        $lowStockValue = Part::lowStock()->sum(DB::raw('current_stock * average_cost'));
        $overstockValue = Part::whereRaw('current_stock >= maximum_stock')->sum(DB::raw('current_stock * average_cost'));

        return [
            'total_value' => $totalValue,
            'by_category' => $valuationByCategory,
            'by_location' => $valuationByLocation,
            'low_stock_value' => $lowStockValue,
            'overstock_value' => $overstockValue,
            'valuation_date' => now()->toISOString(),
        ];
    }

    /**
     * Generate inventory turnover analysis.
     */
    public function generateInventoryTurnoverAnalysis(): array
    {
        $turnoverData = Part::select([
            'parts.id',
            'parts.name',
            'parts.part_number',
            'parts.current_stock',
            'parts.average_cost',
            'parts.unit_cost',
        ])
        ->selectRaw('COALESCE(SUM(CASE WHEN inventory_transactions.transaction_type = \'issue\' THEN ABS(inventory_transactions.quantity) ELSE 0 END), 0) as annual_usage')
        ->selectRaw('COALESCE(parts.current_stock * parts.average_cost, 0) as current_value')
        ->leftJoin('inventory_transactions', function ($join) {
            $join->on('parts.id', '=', 'inventory_transactions.part_id')
                 ->where('inventory_transactions.performed_at', '>=', now()->subYear())
                 ->whereIn('inventory_transactions.transaction_type', ['issue', 'damage', 'loss']);
        })
        ->groupBy('parts.id', 'parts.name', 'parts.part_number', 'parts.current_stock', 'parts.average_cost', 'parts.unit_cost')
        ->having('annual_usage', '>', 0)
        ->get();

        $turnoverAnalysis = $turnoverData->map(function ($part) {
            $currentValue = $part->current_value;
            $annualUsageValue = $part->annual_usage * $part->average_cost;
            
            $turnoverRatio = $currentValue > 0 ? $annualUsageValue / $currentValue : 0;
            $daysOfInventory = $turnoverRatio > 0 ? 365 / $turnoverRatio : 999;

            return [
                'part_id' => $part->id,
                'part_name' => $part->name,
                'part_number' => $part->part_number,
                'current_stock' => $part->current_stock,
                'current_value' => $currentValue,
                'annual_usage' => $part->annual_usage,
                'annual_usage_value' => $annualUsageValue,
                'turnover_ratio' => round($turnoverRatio, 2),
                'days_of_inventory' => round($daysOfInventory, 0),
                'turnover_classification' => $this->classifyTurnover($turnoverRatio),
            ];
        });

        return [
            'parts' => $turnoverAnalysis,
            'summary' => $this->calculateTurnoverSummary($turnoverAnalysis),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Classify inventory turnover.
     */
    private function classifyTurnover(float $turnoverRatio): string
    {
        if ($turnoverRatio >= 12) {
            return 'fast_moving';
        } elseif ($turnoverRatio >= 4) {
            return 'normal_moving';
        } elseif ($turnoverRatio >= 1) {
            return 'slow_moving';
        } else {
            return 'non_moving';
        }
    }

    /**
     * Calculate turnover summary.
     */
    private function calculateTurnoverSummary($turnoverData): array
    {
        $summary = [
            'fast_moving' => ['count' => 0, 'value' => 0],
            'normal_moving' => ['count' => 0, 'value' => 0],
            'slow_moving' => ['count' => 0, 'value' => 0],
            'non_moving' => ['count' => 0, 'value' => 0],
        ];

        foreach ($turnoverData as $part) {
            $classification = $part['turnover_classification'];
            $summary[$classification]['count']++;
            $summary[$classification]['value'] += $part['current_value'];
        }

        return $summary;
    }

    /**
     * Identify obsolete stock.
     */
    public function identifyObsoleteStock(): array
    {
        $obsoleteStock = Part::select([
            'parts.id',
            'parts.name',
            'parts.part_number',
            'parts.current_stock',
            'parts.average_cost',
            'parts.last_transaction_date',
        ])
        ->selectRaw('COALESCE(MAX(inventory_transactions.performed_at), parts.created_at) as last_activity')
        ->selectRaw('COALESCE(parts.current_stock * parts.average_cost, 0) as current_value')
        ->leftJoin('inventory_transactions', 'parts.id', '=', 'inventory_transactions.part_id')
        ->where('parts.current_stock', '>', 0)
        ->groupBy('parts.id', 'parts.name', 'parts.part_number', 'parts.current_stock', 'parts.average_cost', 'parts.last_transaction_date')
        ->get();

        $obsoleteItems = $obsoleteStock->filter(function ($part) {
            $daysSinceLastActivity = now()->diffInDays($part->last_activity);
            return $daysSinceLastActivity > 365; // No activity for over a year
        })->map(function ($part) {
            $daysSinceLastActivity = now()->diffInDays($part->last_activity);
            
            return [
                'part_id' => $part->id,
                'part_name' => $part->name,
                'part_number' => $part->part_number,
                'current_stock' => $part->current_stock,
                'current_value' => $part->current_value,
                'last_activity' => $part->last_activity,
                'days_since_last_activity' => $daysSinceLastActivity,
                'obsolescence_risk' => $this->assessObsolescenceRisk($daysSinceLastActivity),
            ];
        });

        return [
            'obsolete_items' => $obsoleteItems->values(),
            'total_obsolete_value' => $obsoleteItems->sum('current_value'),
            'total_obsolete_count' => $obsoleteItems->count(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Assess obsolescence risk.
     */
    private function assessObsolescenceRisk(int $daysSinceLastActivity): string
    {
        if ($daysSinceLastActivity > 1095) { // 3+ years
            return 'high';
        } elseif ($daysSinceLastActivity > 730) { // 2+ years
            return 'medium';
        } elseif ($daysSinceLastActivity > 365) { // 1+ year
            return 'low';
        } else {
            return 'minimal';
        }
    }

    /**
     * Generate stock movement report.
     */
    public function generateStockMovementReport(array $filters = []): array
    {
        $query = InventoryTransaction::with(['part', 'performer']);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('performed_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('performed_at', '<=', $filters['date_to']);
        }
        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (isset($filters['part_id'])) {
            $query->where('part_id', $filters['part_id']);
        }

        $transactions = $query->orderBy('performed_at', 'desc')->get();

        $movementsByType = $transactions->groupBy('transaction_type');
        $movementsByDay = $transactions->groupBy(function ($transaction) {
            return $transaction->performed_at->format('Y-m-d');
        });

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_quantity_moved' => $transactions->sum('quantity'),
            'total_value' => $transactions->sum('total_cost'),
            'by_type' => $movementsByType->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'quantity' => $group->sum('quantity'),
                    'value' => $group->sum('total_cost'),
                ];
            }),
            'by_day' => $movementsByDay->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'quantity' => $group->sum('quantity'),
                    'value' => $group->sum('total_cost'),
                ];
            }),
        ];

        return [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Perform stock count for a location.
     */
    public function performStockCount(int $locationId, User $counter): array
    {
        $results = [
            'counted' => 0,
            'variances_found' => 0,
            'variances' => [],
        ];

        $stockLocations = PartStockLocation::where('location_id', $locationId)->get();

        foreach ($stockLocations as $stockLocation) {
            try {
                // Simulate counting process (in real implementation, this would be from user input)
                $countedQuantity = $stockLocation->quantity; // Simulated count
                $variance = $countedQuantity - $stockLocation->quantity;
                
                $stockLocation->updateQuantity($countedQuantity, 'Physical stock count', $counter);
                
                $results['counted']++;
                
                if (abs($variance) > 0) {
                    $results['variances_found']++;
                    $results['variances'][] = [
                        'part_id' => $stockLocation->part_id,
                        'part_name' => $stockLocation->part->name,
                        'expected_quantity' => $stockLocation->quantity - $variance,
                        'counted_quantity' => $countedQuantity,
                        'variance' => $variance,
                        'variance_percentage' => $stockLocation->quantity > 0 ? ($variance / $stockLocation->quantity) * 100 : 0,
                    ];
                }
            } catch (\Exception $e) {
                $results['variances'][] = [
                    'part_id' => $stockLocation->part_id,
                    'part_name' => $stockLocation->part->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Generate inventory optimization recommendations.
     */
    public function generateOptimizationRecommendations(): array
    {
        $recommendations = [];

        // Low stock recommendations
        $lowStockParts = Part::lowStock()->limit(10)->get();
        foreach ($lowStockParts as $part) {
            $recommendations[] = [
                'type' => 'stock_level',
                'priority' => 'high',
                'part_id' => $part->id,
                'part_name' => $part->name,
                'message' => "Part {$part->name} is below minimum stock level",
                'action' => 'Create purchase order or adjust minimum stock',
                'potential_impact' => 'Prevents stockouts',
            ];
        }

        // Overstock recommendations
        $overstockParts = Part::whereRaw('current_stock >= maximum_stock')->limit(10)->get();
        foreach ($overstockParts as $part) {
            $recommendations[] = [
                'type' => 'stock_level',
                'priority' => 'medium',
                'part_id' => $part->id,
                'part_name' => $part->name,
                'message' => "Part {$part->name} is overstocked",
                'action' => 'Reduce order quantities or adjust maximum stock',
                'potential_impact' => 'Reduces carrying costs',
            ];
        }

        // Slow-moving stock recommendations
        $slowMovingParts = Part::whereHas('inventoryTransactions', function ($query) {
            $query->where('performed_at', '<', now()->subMonths(6))
                  ->whereIn('transaction_type', ['issue', 'damage', 'loss']);
        })->where('current_stock', '>', 0)->limit(10)->get();

        foreach ($slowMovingParts as $part) {
            $recommendations[] = [
                'type' => 'turnover',
                'priority' => 'low',
                'part_id' => $part->id,
                'part_name' => $part->name,
                'message' => "Part {$part->name} has slow turnover",
                'action' => 'Review demand forecasting or consider obsolescence',
                'potential_impact' => 'Optimizes inventory investment',
            ];
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

    /**
     * Calculate ABC analysis for inventory.
     */
    public function calculateABCAnalysis(): array
    {
        $annualUsageByPart = Part::select([
            'parts.id',
            'parts.name',
            'parts.part_number',
        ])
        ->selectRaw('COALESCE(SUM(CASE WHEN inventory_transactions.transaction_type IN (\'issue\', \'damage\', \'loss\') THEN ABS(inventory_transactions.quantity) ELSE 0 END), 0) as annual_usage')
        ->selectRaw('COALESCE(SUM(CASE WHEN inventory_transactions.transaction_type IN (\'issue\', \'damage\', \'loss\') THEN ABS(inventory_transactions.quantity) * parts.average_cost ELSE 0 END), 0) as annual_usage_value')
        ->leftJoin('inventory_transactions', function ($join) {
            $join->on('parts.id', '=', 'inventory_transactions.part_id')
                 ->where('inventory_transactions.performed_at', '>=', now()->subYear())
                 ->whereIn('inventory_transactions.transaction_type', ['issue', 'damage', 'loss']);
        })
        ->where('parts.current_stock', '>', 0)
        ->groupBy('parts.id', 'parts.name', 'parts.part_number')
        ->having('annual_usage_value', '>', 0)
        ->orderBy('annual_usage_value', 'desc')
        ->get();

        $totalValue = $annualUsageByPart->sum('annual_usage_value');
        $cumulativePercentage = 0;

        $abcAnalysis = $annualUsageByPart->map(function ($part) use ($totalValue, &$cumulativePercentage) {
            $percentage = ($part->annual_usage_value / $totalValue) * 100;
            $cumulativePercentage += $percentage;
            
            $abcClass = $cumulativePercentage <= 80 ? 'A' : 
                       ($cumulativePercentage <= 95 ? 'B' : 'C');

            return [
                'part_id' => $part->id,
                'part_name' => $part->name,
                'part_number' => $part->part_number,
                'annual_usage' => $part->annual_usage,
                'annual_usage_value' => $part->annual_usage_value,
                'percentage' => round($percentage, 2),
                'cumulative_percentage' => round($cumulativePercentage, 2),
                'abc_class' => $abcClass,
            ];
        });

        return [
            'parts' => $abcAnalysis,
            'summary' => [
                'total_parts' => $abcAnalysis->count(),
                'total_value' => $totalValue,
                'class_a' => $abcAnalysis->where('abc_class', 'A')->count(),
                'class_b' => $abcAnalysis->where('abc_class', 'B')->count(),
                'class_c' => $abcAnalysis->where('abc_class', 'C')->count(),
                'class_a_value' => $abcAnalysis->where('abc_class', 'A')->sum('annual_usage_value'),
                'class_b_value' => $abcAnalysis->where('abc_class', 'B')->sum('annual_usage_value'),
                'class_c_value' => $abcAnalysis->where('abc_class', 'C')->sum('annual_usage_value'),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
}
