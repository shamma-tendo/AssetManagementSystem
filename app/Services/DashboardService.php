<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\Part;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get all dashboard statistics.
     */
    public function getDashboardStats(int $period = 30): array
    {
        $totalAssets = max(1, Asset::count());
        $operational = Asset::where('status', 'active')->count();
        $inRepair = Asset::where('status', 'under_maintenance')->count();
        $uptimeAvg = round($operational / $totalAssets * 100, 1);

        return [
            'totalAssets'             => $totalAssets,
            'criticalAlerts'          => $this->getCriticalAlerts(),
            'activeWorkOrders'        => $this->getActiveWorkOrders(),
            'lowStockSkus'            => $this->getLowStockParts(),
            'assetUtilization'        => $this->getAssetUtilization(),
            'recentActivity'          => $this->getRecentActivity(),
            'highPriorityMaintenance' => $this->getHighPriorityMaintenance(),
            'trends'                  => $this->getKpiTrends($period),
            'bars'                    => [
                'totalAssets' => 100,
                'criticalAlerts' => min(100, $this->getCriticalAlerts() * 10),
                'activeWorkOrders' => min(100, $this->getActiveWorkOrders() * 5),
                'lowStockSkus' => min(100, $this->getLowStockParts() * 10),
            ],
        ];
    }

    /**
     * Get critical alerts count.
     */
    public function getCriticalAlerts(): int
    {
        $terminal = ['completed', 'closed', 'cancelled'];
        return WorkOrder::whereNotIn('status', $terminal)
            ->whereIn('priority', ['urgent', 'emergency'])
            ->count();
    }

    /**
     * Get active work orders count.
     */
    public function getActiveWorkOrders(): int
    {
        return WorkOrder::whereNotIn('status', ['completed', 'closed', 'cancelled'])->count();
    }

    /**
     * Get low stock parts count.
     */
    public function getLowStockParts(): int
    {
        return Part::whereRaw('current_stock <= minimum_stock')->count();
    }

    /**
     * Get asset utilization data.
     */
    public function getAssetUtilization(): array
    {
        $total = max(1, Asset::count());
        $activeBase = Asset::where('status', 'active')->count();
        $baseUtil = max(50, (int) round(($activeBase / $total) * 100));

        $allWOs = WorkOrder::whereNotIn('status', ['cancelled'])
            ->where('created_at', '>=', now()->subWeeks(2)->startOfDay())
            ->get(['created_at', 'completed_at']);

        $labels = [];
        $current = [];
        $previous = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $prevDate = $date->copy()->subWeek();

            $labels[] = strtoupper($date->format('D'));

            $open = $allWOs->filter(fn($wo) =>
                $wo->created_at->lte($date->copy()->endOfDay()) &&
                ($wo->completed_at === null || $wo->completed_at->gte($date->copy()->startOfDay()))
            )->count();

            $current[] = max(55, min(100, $baseUtil - min(15, (int) round($open / $total * 100))));

            $prevOpen = $allWOs->filter(fn($wo) =>
                $wo->created_at->lte($prevDate->copy()->endOfDay()) &&
                ($wo->completed_at === null || $wo->completed_at->gte($prevDate->copy()->startOfDay()))
            )->count();

            $previous[] = max(55, min(100, $baseUtil - min(15, (int) round($prevOpen / $total * 100))));
        }

        return compact('labels', 'current', 'previous');
    }

    /**
     * Get recent activity items.
     */
    public function getRecentActivity(): array
    {
        return WorkOrder::with(['asset', 'assignedTo'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(function ($wo) {
                $sv = $wo->status instanceof \BackedEnum ? $wo->status->value : (string) $wo->status;

                [$title, $description, $color] = match ($sv) {
                    'completed', 'closed' => [
                        'Work order completed',
                        ($wo->title ?? 'Maintenance') . ' on ' . ($wo->asset?->name ?? 'asset') . ' finished',
                        'green',
                    ],
                    'in_progress' => [
                        'Work order in progress',
                        ($wo->assignedTo?->name ?? 'Technician') . ' working on ' . ($wo->asset?->serial_number ?? 'asset'),
                        'yellow',
                    ],
                    'cancelled' => [
                        'Work order cancelled',
                        $wo->title ?? 'Work order was cancelled',
                        'red',
                    ],
                    default => [
                        'Work order ' . str_replace('_', ' ', $sv),
                        ($wo->title ?? 'Work order') . ' — ' . ($wo->asset?->name ?? 'asset'),
                        'blue',
                    ],
                };

                return [
                    'type' => $sv,
                    'title' => $title,
                    'description' => $description,
                    'time' => $wo->updated_at->diffForHumans(),
                    'color' => $color,
                ];
            })
            ->toArray();
    }

    /**
     * Get high priority maintenance tasks.
     */
    public function getHighPriorityMaintenance(): array
    {
        return WorkOrder::with(['asset' => fn ($q) => $q->withMax('maintenanceHistories', 'performed_date')])
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->whereIn('priority', ['emergency', 'urgent', 'high'])
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get()
            ->map(function ($wo) {
                $sv = $wo->status instanceof \BackedEnum ? $wo->status->value : (string) $wo->status;
                $pv = $wo->priority instanceof \BackedEnum ? $wo->priority->value : (string) $wo->priority;
                $tv = $wo->type instanceof \BackedEnum ? $wo->type->value : (string) $wo->type;

                $isOverdue = $wo->scheduled_date && $wo->scheduled_date->isPast();
                $statusLabel = $isOverdue ? 'OVERDUE' : strtoupper(str_replace('_', ' ', $sv));
                $dotColor = match (true) {
                    $isOverdue || $pv === 'emergency' => 'red',
                    $pv === 'urgent' => 'orange',
                    default => 'yellow',
                };

                $badgeClass = match ($statusLabel) {
                    'OVERDUE' => 'bg-red-100/80 text-red-700 border-red-200/50',
                    'IN PROGRESS' => 'bg-blue-100/80 text-blue-700 border-blue-200/50',
                    'SCHEDULED' => 'bg-green-100/80 text-green-700 border-green-200/50',
                    default => 'bg-yellow-100/80 text-yellow-700 border-yellow-200/50',
                };

                $health = $wo->asset ? $this->computeAssetHealth($wo->asset) : 50;

                return [
                    'asset_id' => $wo->asset?->serial_number ?? 'N/A',
                    'type' => ucwords(str_replace('_', ' ', $tv)),
                    'health' => $health,
                    'due_date' => $wo->scheduled_date?->format('Y-m-d') ?? 'TBD',
                    'status' => $statusLabel,
                    'dot_color' => $dotColor,
                    'badge_class' => $badgeClass,
                ];
            })
            ->toArray();
    }

    /**
     * Compute asset health score.
     */
    private function computeAssetHealth(Asset $asset): int
    {
        $sv = $asset->status instanceof \BackedEnum ? $asset->status->value : (string) $asset->status;

        if ($sv === 'retired') return 20;
        if ($sv === 'disposed') return 0;
        if (in_array($sv, ['ordered', 'received'])) return 100;

        $statusScore = $sv === 'under_maintenance' ? 60 : 100;
        $usefulLife = max(1, $asset->useful_life_years ?? 10);
        $ageYears = $asset->purchase_date
            ? $asset->purchase_date->diffInDays(now()) / 365.25
            : $usefulLife * 0.5;
        $ageScore = (int) round(max(0, (1 - min(1.0, $ageYears / $usefulLife)) * 100));

        $lastMaintDate = $asset->maintenance_histories_max_performed_date;
        $daysSince = $lastMaintDate
            ? now()->diffInDays(Carbon::parse($lastMaintDate))
            : ($asset->purchase_date ? $asset->purchase_date->diffInDays(now()) : 730);

        $maintenanceScore = match (true) {
            $daysSince <= 30 => 100,
            $daysSince <= 90 => 85,
            $daysSince <= 180 => 70,
            $daysSince <= 365 => 50,
            $daysSince <= 730 => 30,
            default => 10,
        };

        return (int) max(0, min(100, round(($ageScore * 0.4) + ($maintenanceScore * 0.4) + ($statusScore * 0.2))));
    }

    /**
     * Get KPI trends for the specified period.
     */
    public function getKpiTrends(int $period): array
    {
        $terminal = ['completed', 'closed', 'cancelled'];
        $periodStart = now()->subDays($period);
        $prevStart = now()->subDays($period * 2);

        $currAssets = Asset::where('created_at', '>=', $periodStart)->count();
        $prevAssets = Asset::whereBetween('created_at', [$prevStart, $periodStart])->count();

        $currCrit = WorkOrder::whereNotIn('status', $terminal)
            ->whereIn('priority', ['urgent', 'emergency'])
            ->where('created_at', '>=', $periodStart)->count();
        $prevCrit = WorkOrder::whereIn('priority', ['urgent', 'emergency'])
            ->whereBetween('created_at', [$prevStart, $periodStart])->count();

        $currWOs = WorkOrder::whereNotIn('status', $terminal)->where('created_at', '>=', $periodStart)->count();
        $prevWOs = WorkOrder::whereNotIn('status', $terminal)->whereBetween('created_at', [$prevStart, $periodStart])->count();

        $currLow = Part::whereRaw('current_stock <= minimum_stock')
            ->where('created_at', '>=', $periodStart)->count();
        $prevLow = Part::whereRaw('current_stock <= minimum_stock')
            ->whereBetween('created_at', [$prevStart, $periodStart])->count();

        return [
            'totalAssets' => [
                'current' => $currAssets,
                'previous' => $prevAssets,
                'change' => $prevAssets > 0 ? round((($currAssets - $prevAssets) / $prevAssets) * 100, 1) : 0,
                'color' => $currAssets >= $prevAssets ? 'text-green-600' : 'text-red-600',
                'label' => $currAssets >= $prevAssets ? 'Up' : 'Down',
            ],
            'criticalAlerts' => [
                'current' => $currCrit,
                'previous' => $prevCrit,
                'change' => $prevCrit > 0 ? round((($currCrit - $prevCrit) / $prevCrit) * 100, 1) : 0,
                'color' => $currCrit <= $prevCrit ? 'text-green-600' : 'text-red-600',
                'label' => $currCrit <= $prevCrit ? 'Down' : 'Up',
            ],
            'activeWorkOrders' => [
                'current' => $currWOs,
                'previous' => $prevWOs,
                'change' => $prevWOs > 0 ? round((($currWOs - $prevWOs) / $prevWOs) * 100, 1) : 0,
                'color' => $currWOs >= $prevWOs ? 'text-green-600' : 'text-red-600',
                'label' => $currWOs >= $prevWOs ? 'Up' : 'Down',
            ],
            'lowStockSkus' => [
                'current' => $currLow,
                'previous' => $prevLow,
                'change' => $prevLow > 0 ? round((($currLow - $prevLow) / $prevLow) * 100, 1) : 0,
                'color' => $currLow <= $prevLow ? 'text-green-600' : 'text-red-600',
                'label' => $currLow <= $prevLow ? 'Down' : 'Up',
            ],
        ];
    }
}
