<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceController extends Controller
{
    /**
     * Display the maintenance page.
     */
    public function index()
    {
        // Get maintenance statistics
        $stats = $this->getMaintenanceStats();
        
        // Get maintenance tasks
        $tasks = $this->getMaintenanceTasks();
        
        // Get maintenance analytics data
        $analytics = $this->getMaintenanceAnalytics();
        
        return view('maintenance', compact('stats', 'tasks', 'analytics'));
    }
    
    /**
     * Get maintenance statistics.
     */
    private function getMaintenanceStats()
    {
        return [
            'activeOrders' => 24,
            'overdue' => 4,
            'preventiveCompliance' => 98.2,
        ];
    }
    
    /**
     * Get maintenance tasks for Kanban board.
     */
    private function getMaintenanceTasks()
    {
        return [
            'pending' => [
                [
                    'id' => 'WO-2024-089',
                    'title' => 'Compressor Unit #4 Vibration Check',
                    'description' => 'Scheduled vibration analysis and bearing inspection for main compressor unit',
                    'priority' => 'MEDIUM',
                    'asset' => 'CMP-004-A',
                    'technician' => 'Sarah Chen',
                    'dueDate' => '2024-05-18',
                    'estimatedHours' => 4,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-090',
                    'title' => 'Facility Lighting Upgrade',
                    'description' => 'Replace LED fixtures in production area with energy-efficient models',
                    'priority' => 'LOW',
                    'asset' => 'FAC-LIGHT-01',
                    'technician' => 'Mike Johnson',
                    'dueDate' => '2024-05-22',
                    'estimatedHours' => 8,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-091',
                    'title' => 'Conveyor Belt Tension Adjustment',
                    'description' => 'Adjust belt tension and alignment for conveyor system B',
                    'priority' => 'HIGH',
                    'asset' => 'CNV-042-B',
                    'technician' => 'David Kim',
                    'dueDate' => '2024-05-16',
                    'estimatedHours' => 2,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-092',
                    'title' => 'Control Panel Calibration',
                    'description' => 'Calibrate temperature and pressure sensors on control panel',
                    'priority' => 'MEDIUM',
                    'asset' => 'CTRL-015-C',
                    'technician' => 'Emily Rodriguez',
                    'dueDate' => '2024-05-19',
                    'estimatedHours' => 3,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-093',
                    'title' => 'Hydraulic System Inspection',
                    'description' => 'Inspect hydraulic lines and check for leaks in press system',
                    'priority' => 'HIGH',
                    'asset' => 'HYD-008-P',
                    'technician' => 'Tom Wilson',
                    'dueDate' => '2024-05-17',
                    'estimatedHours' => 6,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-094',
                    'title' => 'Motor Bearing Replacement',
                    'description' => 'Replace worn bearings on main drive motor',
                    'priority' => 'MEDIUM',
                    'asset' => 'MOT-023-D',
                    'technician' => 'Alex Turner',
                    'dueDate' => '2024-05-20',
                    'estimatedHours' => 5,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-095',
                    'title' => 'Safety Sensor Testing',
                    'description' => 'Test and calibrate all emergency stop sensors',
                    'priority' => 'HIGH',
                    'asset' => 'SAFE-ALL-01',
                    'technician' => 'Lisa Park',
                    'dueDate' => '2024-05-15',
                    'estimatedHours' => 4,
                    'progress' => 0
                ],
                [
                    'id' => 'WO-2024-096',
                    'title' => 'Filter System Maintenance',
                    'description' => 'Replace air filters and clean ventilation system',
                    'priority' => 'LOW',
                    'asset' => 'FILT-012-V',
                    'technician' => 'James Brown',
                    'dueDate' => '2024-05-25',
                    'estimatedHours' => 3,
                    'progress' => 0
                ]
            ],
            'inProgress' => [
                [
                    'id' => 'WO-2024-085',
                    'title' => 'Conveyor Belt #12 Replacement',
                    'description' => 'Replace worn conveyor belt section and realign tracking system',
                    'priority' => 'HIGH',
                    'asset' => 'CNV-012-A',
                    'technician' => 'Robert Martinez',
                    'dueDate' => '2024-05-14',
                    'estimatedHours' => 6,
                    'progress' => 65
                ],
                [
                    'id' => 'WO-2024-086',
                    'title' => 'Pump Seal Replacement',
                    'description' => 'Replace mechanical seals on primary water pump',
                    'priority' => 'MEDIUM',
                    'asset' => 'PMP-007-W',
                    'technician' => 'Jennifer Lee',
                    'dueDate' => '2024-05-15',
                    'estimatedHours' => 4,
                    'progress' => 40
                ],
                [
                    'id' => 'WO-2024-087',
                    'title' => 'Electrical Panel Upgrade',
                    'description' => 'Upgrade circuit breakers and add surge protection',
                    'priority' => 'MEDIUM',
                    'asset' => 'ELEC-003-P',
                    'technician' => 'Carlos Garcia',
                    'dueDate' => '2024-05-16',
                    'estimatedHours' => 8,
                    'progress' => 25
                ],
                [
                    'id' => 'WO-2024-088',
                    'title' => 'Robot Arm Calibration',
                    'description' => 'Calibrate robotic arm precision and test movement patterns',
                    'priority' => 'HIGH',
                    'asset' => 'ROB-009-R',
                    'technician' => 'Nina Patel',
                    'dueDate' => '2024-05-13',
                    'estimatedHours' => 5,
                    'progress' => 80
                ],
                [
                    'id' => 'WO-2024-097',
                    'title' => 'HVAC Filter Replacement',
                    'description' => 'Replace HVAC filters and clean ductwork',
                    'priority' => 'LOW',
                    'asset' => 'HVAC-002-M',
                    'technician' => 'Steve Davis',
                    'dueDate' => '2024-05-14',
                    'estimatedHours' => 2,
                    'progress' => 50
                ]
            ],
            'overdue' => [
                [
                    'id' => 'WO-2024-078',
                    'title' => 'Emergency HVAC Calibration',
                    'description' => 'Critical HVAC system calibration affecting production area',
                    'priority' => 'CRITICAL',
                    'asset' => 'HVAC-001-M',
                    'technician' => 'Urgent Assignment',
                    'dueDate' => '2024-05-10',
                    'estimatedHours' => 3,
                    'progress' => 0,
                    'overdueDays' => 4
                ],
                [
                    'id' => 'WO-2024-079',
                    'title' => 'Motor Overheating Issue',
                    'description' => 'Motor showing excessive temperature readings, immediate inspection required',
                    'priority' => 'CRITICAL',
                    'asset' => 'MOT-015-D',
                    'technician' => 'Emergency Response',
                    'dueDate' => '2024-05-11',
                    'estimatedHours' => 2,
                    'progress' => 0,
                    'overdueDays' => 3
                ],
                [
                    'id' => 'WO-2024-080',
                    'title' => 'Pressure Vessel Inspection',
                    'description' => 'Annual inspection of pressure vessel for safety compliance',
                    'priority' => 'HIGH',
                    'asset' => 'PRESS-004-V',
                    'technician' => 'Safety Inspector',
                    'dueDate' => '2024-05-12',
                    'estimatedHours' => 6,
                    'progress' => 0,
                    'overdueDays' => 2
                ],
                [
                    'id' => 'WO-2024-081',
                    'title' => 'Fire Suppression System Test',
                    'description' => 'Monthly test of fire suppression system and alarms',
                    'priority' => 'HIGH',
                    'asset' => 'FIRE-ALL-01',
                    'technician' => 'Safety Team',
                    'dueDate' => '2024-05-13',
                    'estimatedHours' => 2,
                    'progress' => 0,
                    'overdueDays' => 1
                ]
            ]
        ];
    }
    
    /**
     * Get maintenance analytics data.
     */
    private function getMaintenanceAnalytics()
    {
        return [
            'completionRate' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'data' => [92, 88, 95, 91, 94, 89]
            ],
            'responseTime' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'data' => [2.5, 2.8, 2.2, 2.6, 2.3, 2.4]
            ],
            'downtime' => [
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'data' => [12, 8, 15, 6]
            ]
        ];
    }
    
    /**
     * Get task details via API.
     */
    public function getTaskDetails($taskId)
    {
        $tasks = $this->getMaintenanceTasks();
        $task = null;
        
        // Search for task in all columns
        foreach ($tasks as $column) {
            $found = collect($column)->firstWhere('id', $taskId);
            if ($found) {
                $task = $found;
                break;
            }
        }
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }
    
    /**
     * Update task status.
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $request->validate([
            'status' => 'required|string|in:pending,in_progress,completed,overdue',
            'progress' => 'nullable|integer|min:0|max:100'
        ]);
        
        // In a real application, this would update the database
        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
            'data' => [
                'taskId' => $taskId,
                'status' => $request->input('status'),
                'progress' => $request->input('progress', 0)
            ]
        ]);
    }
    
    /**
     * Create new work order.
     */
    public function createWorkOrder(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string|in:LOW,MEDIUM,HIGH,CRITICAL',
            'asset' => 'required|string',
            'technician' => 'nullable|string',
            'dueDate' => 'required|date',
            'estimatedHours' => 'required|integer|min:1'
        ]);
        
        // In a real application, this would create a new record in the database
        return response()->json([
            'success' => true,
            'message' => 'Work order created successfully',
            'data' => [
                'id' => 'WO-2024-' . rand(100, 999),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'priority' => $request->input('priority'),
                'asset' => $request->input('asset'),
                'technician' => $request->input('technician'),
                'dueDate' => $request->input('dueDate'),
                'estimatedHours' => $request->input('estimatedHours'),
                'status' => 'pending',
                'progress' => 0,
                'createdAt' => now()->toISOString()
            ]
        ]);
    }
    
    /**
     * Export maintenance data.
     */
    public function exportMaintenance(Request $request)
    {
        $format = $request->input('format', 'csv');
        $tasks = $this->getMaintenanceTasks();
        
        // Flatten all tasks for export
        $allTasks = [];
        foreach ($tasks as $status => $taskList) {
            foreach ($taskList as $task) {
                $task['status'] = $status;
                $allTasks[] = $task;
            }
        }
        
        switch ($format) {
            case 'csv':
                return $this->exportCsv($allTasks);
            case 'excel':
                return $this->exportExcel($allTasks);
            default:
                return response()->json($allTasks);
        }
    }
    
    /**
     * Export data as CSV.
     */
    private function exportCsv($tasks)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="maintenance_tasks.csv"'
        ];
        
        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Work Order ID', 'Title', 'Priority', 'Asset', 'Technician', 'Due Date', 'Status', 'Progress']);
            
            // Data
            foreach ($tasks as $task) {
                fputcsv($file, [
                    $task['id'],
                    $task['title'],
                    $task['priority'],
                    $task['asset'],
                    $task['technician'],
                    $task['dueDate'],
                    $task['status'],
                    $task['progress'] . '%'
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export data as Excel.
     */
    private function exportExcel($tasks)
    {
        return response()->json([
            'message' => 'Excel export not implemented yet',
            'data' => $tasks
        ]);
    }
}
