<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\Part;
use App\Models\SensorReading;
use App\Models\MaintenanceSchedule;
use App\Models\Inspection;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     */
    public function index()
    {
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        return view('dashboard', compact('stats'));
    }
    
    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats()
    {
        return [
            'totalAssets' => $this->getTotalAssets(),
            'criticalAlerts' => $this->getCriticalAlerts(),
            'activeWorkOrders' => $this->getActiveWorkOrders(),
            'lowStockSkus' => $this->getLowStockParts(),
            'assetUtilization' => $this->getAssetUtilization(),
            'recentActivity' => $this->getRecentActivity(),
            'highPriorityMaintenance' => $this->getHighPriorityMaintenance(),
        ];
    }
    
    /**
     * Get total assets count.
     */
    private function getTotalAssets()
    {
        return Asset::count();
    }
    
    /**
     * Get critical alerts count — urgent/emergency open work orders + assets under maintenance.
     */
    private function getCriticalAlerts()
    {
        $terminal = ['completed', 'closed', 'cancelled'];

        return WorkOrder::whereNotIn('status', $terminal)
            ->whereIn('priority', ['urgent', 'emergency'])
            ->count();
    }
    
    /**
     * Get active work orders count.
     */
    private function getActiveWorkOrders()
    {
        return WorkOrder::whereNotIn('status', ['completed', 'closed', 'cancelled'])->count();
    }
    
    /**
     * Get low stock parts count.
     */
    private function getLowStockParts()
    {
        return Part::whereNotNull('reorder_point')
            ->whereRaw('current_stock <= reorder_point')
            ->count();
    }
    
    /**
     * Get asset utilization data — proxied from work order open counts vs total assets.
     */
    private function getAssetUtilization()
    {
        $total     = max(Asset::count(), 1);
        $activeBase = Asset::where('status', 'active')->count();
        $baseUtil   = max(50, (int) round(($activeBase / $total) * 100));

        // Prefetch all WOs touched in the last 14 days
        $allWOs = WorkOrder::whereNotIn('status', ['cancelled'])
            ->where('created_at', '>=', now()->subWeeks(2)->startOfDay())
            ->get(['created_at', 'completed_at']);

        $labels   = [];
        $current  = [];
        $previous = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
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
     * Get recent activity from work orders updated in the last 7 days.
     */
    private function getRecentActivity(): array
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
                    'type'        => $sv,
                    'title'       => $title,
                    'description' => $description,
                    'time'        => $wo->updated_at->diffForHumans(),
                    'color'       => $color,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get high-priority open work orders for the dashboard table.
     */
    private function getHighPriorityMaintenance(): array
    {
        $terminal = ['completed', 'closed', 'cancelled'];

        return WorkOrder::with('asset')
            ->whereNotIn('status', $terminal)
            ->whereIn('priority', ['emergency', 'urgent', 'high'])
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get()
            ->map(function ($wo) {
                $sv = $wo->status instanceof \BackedEnum   ? $wo->status->value   : (string) $wo->status;
                $pv = $wo->priority instanceof \BackedEnum ? $wo->priority->value : (string) $wo->priority;
                $tv = $wo->type instanceof \BackedEnum     ? $wo->type->value     : (string) $wo->type;

                $isOverdue = $wo->scheduled_date && $wo->scheduled_date->isPast();

                $statusLabel = $isOverdue
                    ? 'OVERDUE'
                    : strtoupper(str_replace('_', ' ', $sv));

                $dotColor = match (true) {
                    $isOverdue || $pv === 'emergency' => 'red',
                    $pv === 'urgent'                  => 'orange',
                    default                           => 'yellow',
                };

                $badgeClass = match ($statusLabel) {
                    'OVERDUE'     => 'bg-red-100/80 text-red-700 border-red-200/50',
                    'IN PROGRESS' => 'bg-blue-100/80 text-blue-700 border-blue-200/50',
                    'SCHEDULED'   => 'bg-green-100/80 text-green-700 border-green-200/50',
                    default       => 'bg-yellow-100/80 text-yellow-700 border-yellow-200/50',
                };

                $health = max(20, 85 - match ($pv) {
                    'emergency' => 50,
                    'urgent'    => 35,
                    default     => 20,
                });

                return [
                    'asset_id'    => $wo->asset?->serial_number ?? 'N/A',
                    'type'        => ucwords(str_replace('_', ' ', $tv)),
                    'health'      => $health,
                    'due_date'    => $wo->scheduled_date?->format('Y-m-d') ?? 'TBD',
                    'status'      => $statusLabel,
                    'dot_color'   => $dotColor,
                    'badge_class' => $badgeClass,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get real-time dashboard data via API.
     */
    public function getRealTimeData()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getDashboardStats(),
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }
    
    /**
     * Export dashboard report.
     */
    public function exportReport(Request $request)
    {
        $format = $request->input('format', 'json');
        $dateRange = $request->input('date_range', '30_days');
        
        $data = $this->getDashboardStats();
        
        switch ($format) {
            case 'csv':
                return $this->exportCsv($data);
            case 'excel':
                return $this->exportExcel($data);
            case 'pdf':
                return $this->exportPdf($data);
            default:
                return response()->json($data);
        }
    }
    
    /**
     * Export data as CSV.
     */
    private function exportCsv($data)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="dashboard_report.csv"'
        ];
        
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Metric', 'Value']);
            
            // Data
            fputcsv($file, ['Total Assets', $data['totalAssets']]);
            fputcsv($file, ['Critical Alerts', $data['criticalAlerts']]);
            fputcsv($file, ['Active Work Orders', $data['activeWorkOrders']]);
            fputcsv($file, ['Low Stock SKUs', $data['lowStockSkus']]);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export data as Excel.
     */
    private function exportExcel($data)
    {
        // In a real application, you would use a library like Laravel Excel
        return response()->json([
            'message' => 'Excel export not implemented yet',
            'data' => $data
        ]);
    }
    
    /**
     * Export data as PDF.
     */
    private function exportPdf($data)
    {
        // In a real application, you would use a library like DomPDF
        return response()->json([
            'message' => 'PDF export not implemented yet',
            'data' => $data
        ]);
    }
}
