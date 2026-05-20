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
use App\Services\DashboardService;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     */
    public function index(Request $request)
    {
        $period = (int) $request->input('period', 30);
        $period = in_array($period, [7, 30, 90]) ? $period : 30;

        $dashboardService = new DashboardService();
        $stats = $dashboardService->getDashboardStats($period);

        return view('dashboard', compact('stats', 'period'));
    }

    /**
     * Get real-time data for dashboard widgets.
     */
    public function getRealTimeData(Request $request)
    {
        $dashboardService = new DashboardService();
        $stats = $dashboardService->getDashboardStats((int) $request->input('period', 30));

        return response()->json($stats);
    }

    /**
     * Export dashboard report as CSV.
     */
    public function exportReport(Request $request)
    {
        $dashboardService = new DashboardService();
        $stats = $dashboardService->getDashboardStats((int) $request->input('period', 30));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="dashboard_report_' . now()->format('Y-m-d') . '.csv"'
        ];

        return response()->stream(function () use ($stats) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Assets', $stats['totalAssets']]);
            fputcsv($file, ['Critical Alerts', $stats['criticalAlerts']]);
            fputcsv($file, ['Active Work Orders', $stats['activeWorkOrders']]);
            fputcsv($file, ['Low Stock SKUs', $stats['lowStockSkus']]);
            fputcsv($file, ['Asset Utilization', $stats['assetUtilization'] . '%']);
            fclose($file);
        }, 200, $headers);
    }
}
