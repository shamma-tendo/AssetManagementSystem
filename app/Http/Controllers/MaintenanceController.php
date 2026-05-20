<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MaintenanceController extends Controller
{
    /**
     * Display the maintenance page.
     */
    public function index()
    {
        $stats     = $this->getMaintenanceStats();
        $tasks     = $this->getMaintenanceTasks();
        $analytics = $this->getMaintenanceAnalytics();
        $assets    = Asset::orderBy('name')->get(['id', 'name', 'serial_number']);
        $users     = User::orderBy('name')->get(['id', 'name']);
        $openModal = request()->boolean('new');

        return view('maintenance', compact('stats', 'tasks', 'analytics', 'assets', 'users', 'openModal'));
    }
    
    /**
     * Show the standalone New Work Order form.
     */
    public function create()
    {
        $assets = Asset::orderBy('name')->get(['id', 'name', 'serial_number']);
        $users  = User::orderBy('name')->get(['id', 'name']);

        return view('work-order-create', compact('assets', 'users'));
    }

    /**
     * Get maintenance statistics from the database.
     */
    private function getMaintenanceStats(): array
    {
        $terminal      = ['completed', 'closed', 'cancelled'];
        $thirtyDaysAgo = now()->subDays(30);
        $sixtyDaysAgo  = now()->subDays(60);

        $activeOrders = WorkOrder::whereNotIn('status', $terminal)->count();

        $overdue = WorkOrder::whereNotIn('status', $terminal)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<', now()->toDateString())
            ->count();

        $totalPM     = WorkOrder::where('type', 'preventive_maintenance')->where('created_at', '>=', $thirtyDaysAgo)->count();
        $completedPM = WorkOrder::where('type', 'preventive_maintenance')->whereIn('status', ['completed', 'closed'])->where('created_at', '>=', $thirtyDaysAgo)->count();
        $compliance  = $totalPM > 0 ? round(($completedPM / $totalPM) * 100, 1) : 100.0;

        // --- Trends (current 30-day window vs previous 30-day window) ---

        // Active Orders: new WOs opened this period vs previous
        $currActive  = WorkOrder::where('created_at', '>=', $thirtyDaysAgo)->count();
        $prevActive  = WorkOrder::whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->count();
        $activeDelta = $currActive - $prevActive;

        // Overdue: WOs whose scheduled_date fell in each window
        $currOverdue  = WorkOrder::whereNotIn('status', $terminal)
            ->whereBetween('scheduled_date', [$thirtyDaysAgo->toDateString(), now()->toDateString()])
            ->count();
        $prevOverdue  = WorkOrder::whereBetween('scheduled_date', [$sixtyDaysAgo->toDateString(), $thirtyDaysAgo->toDateString()])
            ->count();
        $overdueDelta = $currOverdue - $prevOverdue;

        // Preventive Compliance: compare this period vs previous period
        $totalPMPrev     = WorkOrder::where('type', 'preventive_maintenance')
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->count();
        $completedPMPrev = WorkOrder::where('type', 'preventive_maintenance')
            ->whereIn('status', ['completed', 'closed'])
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->count();
        $prevCompliance  = $totalPMPrev > 0 ? round(($completedPMPrev / $totalPMPrev) * 100, 1) : 100.0;
        $complianceDelta = round($compliance - $prevCompliance, 1);

        return [
            'activeOrders'         => $activeOrders,
            'overdue'              => $overdue,
            'preventiveCompliance' => $compliance,
            'trends'               => [
                'activeOrders' => [
                    'label' => ($activeDelta >= 0 ? '+' : '') . $activeDelta,
                    'color' => 'text-blue-400',
                ],
                'overdue' => [
                    'label' => ($overdueDelta >= 0 ? '+' : '') . $overdueDelta,
                    'color' => $overdueDelta <= 0 ? 'text-green-400' : 'text-red-500',
                ],
                'preventiveCompliance' => [
                    'label' => ($complianceDelta >= 0 ? '+' : '') . $complianceDelta . '%',
                    'color' => $complianceDelta >= 0 ? 'text-green-400' : 'text-red-400',
                ],
            ],
        ];
    }
    
    /**
     * Get work orders grouped into Kanban columns from the database.
     */
    private function getMaintenanceTasks(): array
    {
        $terminal        = ['completed', 'closed', 'cancelled'];
        $pendingStatuses = ['requested', 'approved', 'assigned', 'scheduled'];

        $overdue = WorkOrder::with(['asset', 'assignedTo'])
            ->whereNotIn('status', $terminal)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<', now()->toDateString())
            ->orderBy('scheduled_date')
            ->limit(20)
            ->get()
            ->map(fn($wo) => $wo->formatted)
            ->values()
            ->toArray();

        $pending = WorkOrder::with(['asset', 'assignedTo'])
            ->whereIn('status', $pendingStatuses)
            ->where(function ($q) {
                $q->whereNull('scheduled_date')
                  ->orWhere('scheduled_date', '>=', now()->toDateString());
            })
            ->orderBy('scheduled_date')
            ->limit(20)
            ->get()
            ->map(fn($wo) => $wo->formatted)
            ->values()
            ->toArray();

        $inProgress = WorkOrder::with(['asset', 'assignedTo'])
            ->whereIn('status', ['in_progress', 'on_hold'])
            ->orderByDesc('started_at')
            ->limit(20)
            ->get()
            ->map(fn($wo) => $wo->formatted)
            ->values()
            ->toArray();

        return compact('pending', 'inProgress', 'overdue');
    }

    /**
     * Build analytics data for the last 6 months from real work order records.
     */
    private function getMaintenanceAnalytics(): array
    {
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));

        $completionRate = $months->map(function ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $total     = WorkOrder::whereBetween('scheduled_date', [$start, $end])->count();
            $completed = WorkOrder::whereBetween('scheduled_date', [$start, $end])
                ->whereIn('status', ['completed', 'closed'])->count();
            return $total > 0 ? round(($completed / $total) * 100) : 0;
        });

        $responseTime = $months->map(function ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $avg = WorkOrder::whereBetween('created_at', [$start, $end])
                ->whereNotNull('started_at')
                ->selectRaw('AVG((julianday(started_at) - julianday(created_at)) * 24) as avg_hours')
                ->value('avg_hours');
            return round($avg ?? 0, 1);
        });

        $weeks = collect(range(3, 0))->map(fn($i) => [
            'label' => 'Week ' . (4 - $i),
            'start' => now()->subWeeks($i)->startOfWeek(),
            'end'   => now()->subWeeks($i)->endOfWeek(),
        ]);

        $downtime = $weeks->map(fn($w) => round(
            WorkOrder::whereBetween('completed_at', [$w['start'], $w['end']])
                ->whereNotNull('actual_hours')
                ->sum('actual_hours'),
            1
        ));

        return [
            'completionRate' => [
                'labels' => $months->map(fn($m) => $m->format('M'))->values()->toArray(),
                'data'   => $completionRate->values()->toArray(),
            ],
            'responseTime' => [
                'labels' => $months->map(fn($m) => $m->format('M'))->values()->toArray(),
                'data'   => $responseTime->values()->toArray(),
            ],
            'downtime' => [
                'labels' => $weeks->pluck('label')->values()->toArray(),
                'data'   => $downtime->values()->toArray(),
            ],
        ];
    }
    
    /**
     * Get full work order details via API (taskId = UUID).
     */
    public function getTaskDetails($taskId)
    {
        $wo = WorkOrder::with(['asset', 'assignedTo'])->find($taskId);

        if (!$wo) {
            return response()->json(['success' => false, 'message' => 'Work order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $wo->formatted]);
    }
    
    /**
     * Update work order status (taskId = UUID).
     * Triggers WorkOrderObserver which auto-syncs the linked asset status.
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $request->validate([
            'status' => 'required|in:requested,approved,assigned,scheduled,in_progress,on_hold,completed,closed,cancelled',
        ]);

        $wo        = WorkOrder::findOrFail($taskId);
        $oldStatus = $wo->status;
        $newStatus = WorkOrderStatus::from($request->status);

        if (!$oldStatus->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition from {$oldStatus->getDisplayName()} to {$newStatus->getDisplayName()}",
            ], 422);
        }

        $updates = ['status' => $request->status];
        if ($newStatus === WorkOrderStatus::IN_PROGRESS && !$wo->started_at) {
            $updates['started_at'] = now();
        }
        if ($newStatus === WorkOrderStatus::COMPLETED && !$wo->completed_at) {
            $updates['completed_at'] = now();
        }
        if ($newStatus === WorkOrderStatus::CLOSED && !$wo->closed_at) {
            $updates['closed_at'] = now();
        }

        $wo->update($updates);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }
    
    /**
     * Save a new work order to the database.
     * Triggers WorkOrderObserver → asset status becomes under_maintenance.
     */
    public function createWorkOrder(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'required|string',
            'type'            => 'required|in:preventive_maintenance,corrective_maintenance,emergency_maintenance,inspection,calibration,installation,removal,upgrade,repair,other',
            'priority'        => 'required|in:low,normal,high,urgent,emergency',
            'asset_id'        => 'required|exists:assets,id',
            'assigned_to'     => 'nullable|exists:users,id',
            'scheduled_date'  => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $actorId = Auth::id() ?? User::first()?->id;

        $wo = WorkOrder::create(array_merge($validated, [
            'status'       => 'requested',
            'requested_by' => $actorId,
            'created_by'   => $actorId,
            'assigned_to'  => $validated['assigned_to'] ?? null,
        ]));

        return redirect()->route('maintenance')
            ->with('success', 'Work order "' . $wo->title . '" created successfully!');
    }
    
    /**
     * Export all non-terminal work orders as CSV.
     */
    public function exportMaintenance(Request $request)
    {
        $tasks = WorkOrder::with(['asset', 'assignedTo'])
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn($wo) => $wo->formatted)
            ->toArray();

        return match ($request->input('format', 'csv')) {
            'excel' => response()->json(['message' => 'Excel export not implemented yet', 'data' => $tasks]),
            default => $this->exportCsv($tasks),
        };
    }

    private function exportCsv(array $tasks)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="maintenance_' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($tasks) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Work Order ID', 'Title', 'Priority', 'Type', 'Asset', 'Technician', 'Due Date', 'Est. Hours', 'Status', 'Progress']);
            foreach ($tasks as $t) {
                fputcsv($file, [
                    $t['id'], $t['title'], $t['priority'], $t['type'],
                    $t['asset'], $t['technician'], $t['dueDate'],
                    $t['estimatedHours'], $t['status'], $t['progress'] . '%',
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Update a work order.
     */
    public function update(Request $request, $taskId)
    {
        $wo = WorkOrder::findOrFail($taskId);

        $validated = $request->validate([
            'title'           => 'sometimes|required|string|max:255',
            'description'     => 'sometimes|required|string',
            'type'            => 'sometimes|required|in:preventive_maintenance,corrective_maintenance,emergency_maintenance,inspection,calibration,installation,removal,upgrade,repair,other',
            'priority'        => 'sometimes|required|in:low,normal,high,urgent,emergency',
            'asset_id'        => 'sometimes|required|exists:assets,id',
            'assigned_to'     => 'nullable|exists:users,id',
            'scheduled_date'  => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'status'          => 'sometimes|required|in:requested,approved,assigned,scheduled,in_progress,on_hold,completed,closed,cancelled',
        ]);

        // Handle status transition validation if status is being updated
        if (isset($validated['status'])) {
            $oldStatus = $wo->status;
            $newStatus = WorkOrderStatus::from($validated['status']);
            if (!$oldStatus->canTransitionTo($newStatus)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot transition from {$oldStatus->getDisplayName()} to {$newStatus->getDisplayName()}",
                ], 422);
            }
            $updates = ['status' => $validated['status']];
            if ($newStatus === WorkOrderStatus::IN_PROGRESS && !$wo->started_at) {
                $updates['started_at'] = now();
            }
            if ($newStatus === WorkOrderStatus::COMPLETED && !$wo->completed_at) {
                $updates['completed_at'] = now();
            }
            if ($newStatus === WorkOrderStatus::CLOSED && !$wo->closed_at) {
                $updates['closed_at'] = now();
            }
        } else {
            $updates = [];
        }

        $wo->update(array_merge($validated, $updates, ['updated_by' => Auth::id()]));

        return response()->json([
            'success' => true,
            'message' => 'Work order updated successfully',
            'data' => $this->formatWorkOrder($wo->fresh()),
        ]);
    }

    /**
     * Delete a work order.
     */
    public function destroy($taskId)
    {
        $wo = WorkOrder::findOrFail($taskId);

        // Prevent deletion of in-progress work orders
        if (in_array($wo->status->value, ['in_progress', 'scheduled', 'assigned'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an in-progress or scheduled work order',
            ], 422);
        }

        $wo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work order deleted successfully',
        ]);
    }
}
