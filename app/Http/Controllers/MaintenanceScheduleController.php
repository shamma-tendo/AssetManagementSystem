<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceHistory;
use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\MaintenanceType;
use App\Models\FrequencyType;
use App\Services\MaintenanceSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceScheduleController extends Controller
{
    protected MaintenanceSchedulingService $schedulingService;

    public function __construct(MaintenanceSchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
    }

    /**
     * Display a listing of the maintenance schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceSchedule::with([
            'asset', 'assignedTechnician', 'creator', 'workOrders'
        ]);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('maintenance_type')) {
            $type = $request->input('maintenance_type');
            if (is_array($type)) {
                $query->whereIn('maintenance_type', $type);
            } else {
                $query->where('maintenance_type', $type);
            }
        }

        if ($request->has('frequency_type')) {
            $query->where('frequency_type', $request->input('frequency_type'));
        }

        if ($request->has('assigned_technician_id')) {
            $query->where('assigned_technician_id', $request->input('assigned_technician_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('auto_create_work_order')) {
            $query->where('auto_create_work_order', $request->boolean('auto_create_work_order'));
        }

        // Date filters
        if ($request->has('due_date_from')) {
            $query->whereDate('next_due_date', '>=', $request->input('due_date_from'));
        }
        if ($request->has('due_date_to')) {
            $query->whereDate('next_due_date', '<=', $request->input('due_date_to'));
        }

        // Special filters
        if ($request->boolean('overdue', false)) {
            $query->overdue();
        }

        if ($request->boolean('due_soon', false)) {
            $query->dueSoon($request->input('due_soon_days', 30));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'next_due_date');
        $sortOrder = $request->input('sort_order', 'asc');
        
        $validSortFields = [
            'title', 'next_due_date', 'last_performed_date', 'maintenance_type',
            'frequency_type', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('next_due_date', 'asc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $schedules = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $schedules->items(),
            'pagination' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
                'from' => $schedules->firstItem(),
                'to' => $schedules->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created maintenance schedule in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|uuid|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'maintenance_type' => 'required|in:preventive,predictive,corrective,condition_based,emergency,routine,inspection,calibration,lubrication,cleaning,testing',
            'frequency_type' => 'required|in:daily,weekly,monthly,yearly,hourly,custom',
            'frequency_interval' => 'required|integer|min:1|max:365',
            'frequency_months' => 'nullable|integer|min:1|max:120|required_if:frequency_type,monthly',
            'frequency_days' => 'nullable|integer|min:1|max:365|required_if:frequency_type,daily',
            'frequency_hours' => 'nullable|integer|min:1|max:8760|required_if:frequency_type,hourly',
            'last_performed_date' => 'nullable|date|before_or_equal:today',
            'next_due_date' => 'required|date|after_or_equal:today',
            'due_date_based_on' => 'nullable|date',
            'auto_create_work_order' => 'boolean',
            'work_order_priority' => 'nullable|in:low,normal,high,urgent,emergency',
            'assigned_technician_id' => 'nullable|uuid|exists:users,id',
            'estimated_duration_hours' => 'nullable|numeric|min:0.1|max:9999.9999',
            'estimated_cost' => 'nullable|numeric|min:0|max:999999999.99',
            'required_parts' => 'nullable|array',
            'required_tools' => 'nullable|array',
            'safety_requirements' => 'nullable|array',
            'checklist_items' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $schedule = MaintenanceSchedule::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance schedule created successfully',
                'data' => $schedule->load([
                    'asset', 'assignedTechnician', 'creator'
                ]),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create maintenance schedule',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified maintenance schedule.
     */
    public function show(MaintenanceSchedule $schedule): JsonResponse
    {
        $schedule->load([
            'asset', 'assignedTechnician', 'creator', 'updater',
            'workOrders', 'maintenanceHistory' => function ($query) {
                $query->with('performer')->orderBy('performed_date', 'desc');
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $schedule,
        ]);
    }

    /**
     * Update the specified maintenance schedule in storage.
     */
    public function update(Request $request, MaintenanceSchedule $schedule): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'maintenance_type' => 'sometimes|required|in:preventive,predictive,corrective,condition_based,emergency,routine,inspection,calibration,lubrication,cleaning,testing',
            'frequency_type' => 'sometimes|required|in:daily,weekly,monthly,yearly,hourly,custom',
            'frequency_interval' => 'sometimes|required|integer|min:1|max:365',
            'frequency_months' => 'sometimes|nullable|integer|min:1|max:120|required_if:frequency_type,monthly',
            'frequency_days' => 'sometimes|nullable|integer|min:1|max:365|required_if:frequency_type,daily',
            'frequency_hours' => 'sometimes|nullable|integer|min:1|max:8760|required_if:frequency_type,hourly',
            'last_performed_date' => 'sometimes|nullable|date|before_or_equal:today',
            'next_due_date' => 'sometimes|nullable|date|after_or_equal:today',
            'due_date_based_on' => 'sometimes|nullable|date',
            'auto_create_work_order' => 'sometimes|boolean',
            'work_order_priority' => 'sometimes|nullable|in:low,normal,high,urgent,emergency',
            'assigned_technician_id' => 'sometimes|nullable|uuid|exists:users,id',
            'estimated_duration_hours' => 'sometimes|nullable|numeric|min:0.1|max:9999.9999',
            'estimated_cost' => 'sometimes|nullable|numeric|min:0|max:999999999.99',
            'required_parts' => 'sometimes|nullable|array',
            'required_tools' => 'sometimes|nullable|array',
            'safety_requirements' => 'sometimes|nullable|array',
            'checklist_items' => 'sometimes|nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $schedule->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance schedule updated successfully',
                'data' => $schedule->load([
                    'asset', 'assignedTechnician', 'creator'
                ]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance schedule',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Remove the specified maintenance schedule from storage.
     */
    public function destroy(MaintenanceSchedule $schedule): JsonResponse
    {
        // Check if schedule can be deleted
        if ($schedule->workOrders()->whereIn('status', ['in_progress', 'completed'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete schedule with active or completed work orders',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $schedule->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance schedule deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete maintenance schedule',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get maintenance schedule statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_schedules' => MaintenanceSchedule::count(),
            'active_schedules' => MaintenanceSchedule::active()->count(),
            'overdue_count' => MaintenanceSchedule::overdue()->count(),
            'due_soon_count' => MaintenanceSchedule::dueSoon()->count(),
            'auto_create_count' => MaintenanceSchedule::autoCreate()->count(),
            'by_maintenance_type' => MaintenanceSchedule::select('maintenance_type', DB::raw('count(*) as count'))
                ->groupBy('maintenance_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->maintenance_type => $item->count];
                }),
            'by_frequency_type' => MaintenanceSchedule::select('frequency_type', DB::raw('count(*) as count'))
                ->groupBy('frequency_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->frequency_type => $item->count];
                }),
            'compliance_rate' => $this->calculateOverallComplianceRate(),
            'upcoming_maintenance' => MaintenanceSchedule::active()
                ->with('asset')
                ->whereBetween('next_due_date', [now(), now()->addDays(30)])
                ->orderBy('next_due_date')
                ->limit(10)
                ->get(),
            'overdue_maintenance' => MaintenanceSchedule::overdue()
                ->with('asset')
                ->orderBy('next_due_date')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Create work order from maintenance schedule.
     */
    public function createWorkOrder(MaintenanceSchedule $schedule): JsonResponse
    {
        // Check if work order already exists for this schedule and due date
        $existingWorkOrder = WorkOrder::where('maintenance_schedule_id', $schedule->id)
            ->where('scheduled_date', $schedule->next_due_date)
            ->whereNotIn('status', ['cancelled', 'closed'])
            ->first();

        if ($existingWorkOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Work order already exists for this maintenance schedule',
                'data' => $existingWorkOrder,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $workOrder = $schedule->createWorkOrder();

            // Log the creation
            MaintenanceHistory::create([
                'maintenance_schedule_id' => $schedule->id,
                'work_order_id' => $workOrder->id,
                'asset_id' => $schedule->asset_id,
                'performed_by' => auth()->id(),
                'performed_date' => now(),
                'completion_status' => 'scheduled',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order created successfully from maintenance schedule',
                'data' => $workOrder->load(['asset', 'assignedTo']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create work order',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Mark maintenance as performed.
     */
    public function markAsPerformed(Request $request, MaintenanceSchedule $schedule): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'performed_date' => 'required|date|before_or_equal:today',
            'actual_duration_hours' => 'nullable|numeric|min:0.1|max:9999.9999',
            'actual_cost' => 'nullable|numeric|min:0|max:999999999.99',
            'notes' => 'nullable|string',
            'findings' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'parts_used' => 'nullable|array',
            'tools_used' => 'nullable|array',
            'checklist_completed' => 'boolean',
            'checklist_items' => 'nullable|array',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'issues_found' => 'nullable|array',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after_or_equal:performed_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // Create maintenance history record
            $history = MaintenanceHistory::create([
                'maintenance_schedule_id' => $schedule->id,
                'asset_id' => $schedule->asset_id,
                'performed_by' => auth()->id(),
                'performed_date' => $validated['performed_date'],
                'actual_duration_hours' => $validated['actual_duration_hours'] ?? null,
                'estimated_duration_hours' => $schedule->estimated_duration_hours,
                'actual_cost' => $validated['actual_cost'] ?? null,
                'estimated_cost' => $schedule->estimated_cost,
                'notes' => $validated['notes'] ?? null,
                'findings' => $validated['findings'] ?? null,
                'recommendations' => $validated['recommendations'] ?? null,
                'parts_used' => $validated['parts_used'] ?? null,
                'tools_used' => $validated['tools_used'] ?? null,
                'checklist_completed' => $validated['checklist_completed'] ?? false,
                'checklist_items' => $validated['checklist_items'] ?? null,
                'performance_rating' => $validated['performance_rating'] ?? null,
                'issues_found' => $validated['issues_found'] ?? null,
                'follow_up_required' => $validated['follow_up_required'] ?? false,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'completed_on_time' => Carbon::parse($validated['performed_date'])->lte($schedule->next_due_date),
                'created_by' => auth()->id(),
            ]);

            // Update schedule
            $schedule->markAsPerformed(Carbon::parse($validated['performed_date']));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance marked as performed successfully',
                'data' => [
                    'history' => $history->load(['performer', 'asset']),
                    'schedule' => $schedule->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark maintenance as performed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get maintenance calendar view.
     */
    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $schedules = MaintenanceSchedule::active()
            ->whereBetween('next_due_date', [$startDate, $endDate])
            ->with(['asset', 'assignedTechnician'])
            ->orderBy('next_due_date')
            ->get();

        $calendarEvents = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'start' => $schedule->next_due_date->format('Y-m-d'),
                'backgroundColor' => $schedule->isOverdue() ? 'red' : ($schedule->isDueSoon() ? 'orange' : 'blue'),
                'extendedProps' => [
                    'maintenance_type' => $schedule->maintenance_type->getDisplayName(),
                    'frequency_type' => $schedule->frequency_type->getDisplayName(),
                    'asset_name' => $schedule->asset?->name,
                    'assigned_technician' => $schedule->assignedTechnician?->full_name,
                    'priority' => $schedule->priority_level,
                    'status' => $schedule->status_display,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $calendarEvents,
        ]);
    }

    /**
     * Run automated scheduling process.
     */
    public function runAutomatedScheduling(): JsonResponse
    {
        $results = $this->schedulingService->processAutomatedScheduling();

        return response()->json([
            'success' => true,
            'message' => 'Automated scheduling completed',
            'data' => $results,
        ]);
    }

    /**
     * Get maintenance history for a schedule.
     */
    public function history(MaintenanceSchedule $schedule): JsonResponse
    {
        $history = $schedule->maintenanceHistory()
            ->with(['performer', 'workOrder'])
            ->orderBy('performed_date', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Calculate overall compliance rate.
     */
    private function calculateOverallComplianceRate(): float
    {
        $totalHistory = MaintenanceHistory::count();
        $completedOnTime = MaintenanceHistory::where('completed_on_time', true)->count();
        
        return $totalHistory > 0 ? ($completedOnTime / $totalHistory) * 100 : 100;
    }
}
