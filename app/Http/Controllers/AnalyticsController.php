<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\Inspection;
use App\Models\MaintenanceSchedule;
use App\Models\Part;
use App\Models\SensorReading;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index()
    {
        $stats = [
            'totalAssets' => Asset::count(),
            'assetsActive' => Asset::where('status', 'active')->count(),
            'assetsInMaintenance' => Asset::where('status', 'under_maintenance')->count(),
            'assetsRetired' => Asset::where('status', 'retired')->count(),
            'totalWorkOrders' => WorkOrder::count(),
            'completedWorkOrders' => WorkOrder::where('status', 'completed')->count(),
            'pendingWorkOrders' => WorkOrder::where('status', 'pending')->count(),
            'inProgressWorkOrders' => WorkOrder::where('status', 'in_progress')->count(),
            'totalInspections' => Inspection::count(),
            'passedInspections' => Inspection::where('result', 'passed')->count(),
            'failedInspections' => Inspection::where('result', 'failed')->count(),
            'overdueMaintenances' => MaintenanceSchedule::where('status', 'pending')
                ->where('due_date', '<', now())->count(),
            'lowStockParts' => Part::whereNotNull('reorder_point')
                ->whereRaw('current_stock <= reorder_point')->count(),
            'totalSensorReadings' => SensorReading::count(),
            'poorQualityReadings' => SensorReading::where('quality', 'poor')->count(),
        ];

        return view('analytics', compact('stats'));
    }

    public function export(): StreamedResponse
    {
        $stats = [
            'totalAssets' => Asset::count(),
            'assetsActive' => Asset::where('status', 'active')->count(),
            'assetsInMaintenance' => Asset::where('status', 'under_maintenance')->count(),
            'assetsRetired' => Asset::where('status', 'retired')->count(),
            'totalWorkOrders' => WorkOrder::count(),
            'completedWorkOrders' => WorkOrder::where('status', 'completed')->count(),
            'pendingWorkOrders' => WorkOrder::where('status', 'pending')->count(),
            'inProgressWorkOrders' => WorkOrder::where('status', 'in_progress')->count(),
            'totalInspections' => Inspection::count(),
            'passedInspections' => Inspection::where('result', 'passed')->count(),
            'failedInspections' => Inspection::where('result', 'failed')->count(),
            'overdueMaintenances' => MaintenanceSchedule::where('status', 'pending')
                ->where('due_date', '<', now())->count(),
            'lowStockParts' => Part::whereNotNull('reorder_point')
                ->whereRaw('current_stock <= reorder_point')->count(),
            'totalSensorReadings' => SensorReading::count(),
            'poorQualityReadings' => SensorReading::where('quality', 'poor')->count(),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ];

        $filename = 'analytics_report_' . now()->format('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($stats) {
            $handle = fopen('php://output', 'w');
            
            // Add header styling comment
            fputcsv($handle, ['Asset Management Analytics Report']);
            fputcsv($handle, ['Generated: ' . $stats['generatedAt']]);
            fputcsv($handle, []);
            
            // Assets Section
            fputcsv($handle, ['ASSETS METRICS']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Assets', $stats['totalAssets']]);
            fputcsv($handle, ['Active Assets', $stats['assetsActive']]);
            fputcsv($handle, ['Assets in Maintenance', $stats['assetsInMaintenance']]);
            fputcsv($handle, ['Retired Assets', $stats['assetsRetired']]);
            fputcsv($handle, []);
            
            // Work Orders Section
            fputcsv($handle, ['WORK ORDERS METRICS']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Work Orders', $stats['totalWorkOrders']]);
            fputcsv($handle, ['Completed', $stats['completedWorkOrders']]);
            fputcsv($handle, ['In Progress', $stats['inProgressWorkOrders']]);
            fputcsv($handle, ['Pending', $stats['pendingWorkOrders']]);
            fputcsv($handle, []);
            
            // Inspections Section
            fputcsv($handle, ['INSPECTIONS METRICS']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Inspections', $stats['totalInspections']]);
            fputcsv($handle, ['Passed', $stats['passedInspections']]);
            fputcsv($handle, ['Failed', $stats['failedInspections']]);
            fputcsv($handle, []);
            
            // Other Metrics
            fputcsv($handle, ['OTHER METRICS']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Overdue Maintenances', $stats['overdueMaintenances']]);
            fputcsv($handle, ['Low Stock Parts', $stats['lowStockParts']]);
            fputcsv($handle, ['Total Sensor Readings', $stats['totalSensorReadings']]);
            fputcsv($handle, ['Poor Quality Readings', $stats['poorQualityReadings']]);
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
