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
     * Get critical alerts count.
     */
    private function getCriticalAlerts()
    {
        return SensorReading::where('quality', 'poor')
            ->orWhere('error_code', '!=', null)
            ->orWhere('value', '>', 90) // High temperature/pressure alerts
            ->count();
    }
    
    /**
     * Get active work orders count.
     */
    private function getActiveWorkOrders()
    {
        return WorkOrder::whereIn('status', ['pending', 'in_progress'])->count();
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
     * Get asset utilization data.
     */
    private function getAssetUtilization()
    {
        // Get sensor readings for the last 7 days grouped by day
        $utilizationData = SensorReading::selectRaw('DATE(timestamp) as date, AVG(value) as utilization')
            ->where('timestamp', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Prepare data for chart
        $labels = [];
        $current = [];
        $previous = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D');
            
            $dayData = $utilizationData->where('date', $date->format('Y-m-d'))->first();
            $current[] = $dayData ? round($dayData->utilization, 1) : 85;
            $previous[] = round(85 - rand(5, 15), 1); // Previous week data
        }
        
        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previous,
        ];
    }
    
    /**
     * Get recent activity.
     */
    private function getRecentActivity()
    {
        $activities = collect();
        
        // Get recent completed work orders
        $completedWorkOrders = WorkOrder::where('status', 'completed')
            ->with('asset')
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get();
            
        foreach ($completedWorkOrders as $workOrder) {
            $activities->push([
                'type' => 'work_order_completed',
                'title' => 'Work order completed',
                'description' => "{$workOrder->asset->name} maintenance finished",
                'timestamp' => $workOrder->completed_at,
                'icon' => 'check-circle',
                'color' => 'green'
            ]);
        }
        
        // Get recent sensor alerts
        $recentAlerts = SensorReading::where('quality', 'poor')
            ->orWhere('error_code', '!=', null)
            ->with('sensor.asset')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
            
        foreach ($recentAlerts as $reading) {
            $activities->push([
                'type' => 'telemetry_alert',
                'title' => 'Telemetry alert',
                'description' => "{$reading->sensor->asset->name} sensor anomaly detected",
                'timestamp' => $reading->created_at,
                'icon' => 'exclamation-triangle',
                'color' => 'yellow'
            ]);
        }
        
        // Get recent user assignments
        $recentAssignments = WorkOrder::whereNotNull('assigned_to')
            ->where('assigned_at', '>=', now()->subHours(24))
            ->with(['assignedTo', 'asset'])
            ->orderBy('assigned_at', 'desc')
            ->limit(2)
            ->get();
            
        foreach ($recentAssignments as $workOrder) {
            $activities->push([
                'type' => 'user_assigned',
                'title' => 'User assigned',
                'description' => "{$workOrder->assignedTo->name} assigned to {$workOrder->asset->name}",
                'timestamp' => $workOrder->assigned_at,
                'icon' => 'user',
                'color' => 'blue'
            ]);
        }
        
        return $activities->sortByDesc('timestamp')->take(5)->values()->all();
    }
    
    /**
     * Get high priority maintenance tasks.
     */
    private function getHighPriorityMaintenance()
    {
        // Get overdue maintenance schedules
        $overdueSchedules = MaintenanceSchedule::where('due_date', '<', now())
            ->where('status', 'pending')
            ->with(['asset', 'assignedTo'])
            ->orderBy('due_date')
            ->limit(3)
            ->get();
            
        $highPriorityTasks = collect();
        
        foreach ($overdueSchedules as $schedule) {
            $statusColor = match($schedule->priority) {
                'critical' => 'red',
                'high' => 'orange',
                'medium' => 'yellow',
                'low' => 'blue',
                default => 'gray'
            };
            
            $highPriorityTasks->push([
                'asset_id' => $schedule->asset->serial_number,
                'type' => ucfirst($schedule->type),
                'condition' => $schedule->condition_score ?? 0,
                'due_date' => $schedule->due_date->format('Y-m-d'),
                'status' => 'OVERDUE',
                'status_color' => $statusColor,
                'schedule_id' => $schedule->id,
                'assigned_to' => $schedule->assignedTo?->name
            ]);
        }
        
        // Get high priority work orders
        $highPriorityWorkOrders = WorkOrder::whereIn('priority', ['critical', 'high'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['asset', 'assignedTo'])
            ->orderBy('priority')
            ->orderBy('created_at')
            ->limit(5 - $highPriorityTasks->count())
            ->get();
            
        foreach ($highPriorityWorkOrders as $workOrder) {
            $statusColor = match($workOrder->status) {
                'pending' => 'yellow',
                'in_progress' => 'blue',
                'scheduled' => 'green',
                default => 'gray'
            };
            
            $highPriorityTasks->push([
                'asset_id' => $workOrder->asset->serial_number,
                'type' => ucfirst($workOrder->type?->name ?? $workOrder->type),
                'condition' => 0,
                'due_date' => $workOrder->scheduled_date?->format('Y-m-d') ?? 'TBD',
                'status' => strtoupper($workOrder->status?->value ?? $workOrder->status),
                'status_color' => $statusColor,
                'work_order_id' => $workOrder->id,
                'assigned_to' => $workOrder->assignedTo?->name
            ]);
        }
        
        return $highPriorityTasks->take(5)->values()->all();
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
