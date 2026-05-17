<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use App\Models\WorkOrderLabor;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderComment;
use App\Models\WorkOrderHistory;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkOrderStatus;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkOrderController extends Controller
{
    /**
     * Display a listing of the work orders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::with([
            'asset', 'assignedTo', 'creator', 'requester', 
            'location', 'department', 'parts', 'laborEntries'
        ]);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('work_performed', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('priority')) {
            $priority = $request->input('priority');
            if (is_array($priority)) {
                $query->whereIn('priority', $priority);
            } else {
                $query->where('priority', $priority);
            }
        }

        if ($request->has('type')) {
            $type = $request->input('type');
            if (is_array($type)) {
                $query->whereIn('type', $type);
            } else {
                $query->where('type', $type);
            }
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        if ($request->has('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // Date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->has('scheduled_date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->input('scheduled_date_from'));
        }
        if ($request->has('scheduled_date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->input('scheduled_date_to'));
        }

        // Special filters
        if ($request->boolean('overdue', false)) {
            $query->overdue();
        }

        if ($request->boolean('due_today', false)) {
            $query->dueToday();
        }

        if ($request->boolean('due_this_week', false)) {
            $query->dueThisWeek();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $validSortFields = [
            'title', 'status', 'priority', 'type', 'scheduled_date',
            'created_at', 'updated_at', 'completed_at', 'actual_cost'
        ];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $workOrders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $workOrders->items(),
            'pagination' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
                'from' => $workOrders->firstItem(),
                'to' => $workOrders->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created work order in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent,emergency',
            'type' => 'required|in:preventive_maintenance,corrective_maintenance,emergency_maintenance,inspection,calibration,installation,removal,upgrade,repair,other',
            'asset_id' => 'required|uuid|exists:assets,id',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'requested_by' => 'nullable|uuid|exists:users,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'estimated_hours' => 'nullable|numeric|min:0|max:9999.99',
            'estimated_cost' => 'nullable|numeric|min:0|max:999999999.99',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string',
            'parts_used' => 'nullable|array',
            'tools_used' => 'nullable|array',
            'safety_precautions' => 'nullable|string',
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

        // Set default requested_by if not provided
        if (!isset($validated['requested_by'])) {
            $validated['requested_by'] = auth()->id();
        }

        DB::beginTransaction();
        try {
            $workOrder = WorkOrder::create($validated);

            // Log creation
            WorkOrderHistory::logCustomAction($workOrder, 'created', 'Work order created', auth()->user());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order created successfully',
                'data' => $workOrder->load([
                    'asset', 'assignedTo', 'creator', 'requester',
                    'location', 'department'
                ]),
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
     * Display the specified work order.
     */
    public function show(WorkOrder $workOrder): JsonResponse
    {
        $workOrder->load([
            'asset', 'assignedTo', 'creator', 'requester',
            'location', 'department', 'parts', 'laborEntries',
            'attachments', 'comments' => function ($query) {
                $query->with('user')->orderBy('created_at');
            }, 'history' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $workOrder,
        ]);
    }

    /**
     * Update the specified work order in storage.
     */
    public function update(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:low,normal,high,urgent,emergency',
            'type' => 'sometimes|required|in:preventive_maintenance,corrective_maintenance,emergency_maintenance,inspection,calibration,installation,removal,upgrade,repair,other',
            'asset_id' => 'sometimes|required|uuid|exists:assets,id',
            'assigned_to' => 'sometimes|nullable|uuid|exists:users,id',
            'requested_by' => 'sometimes|nullable|uuid|exists:users,id',
            'location_id' => 'sometimes|nullable|uuid|exists:locations,id',
            'department_id' => 'sometimes|nullable|uuid|exists:departments,id',
            'estimated_hours' => 'sometimes|nullable|numeric|min:0|max:9999.99',
            'actual_hours' => 'sometimes|nullable|numeric|min:0|max:9999.99',
            'estimated_cost' => 'sometimes|nullable|numeric|min:0|max:999999999.99',
            'actual_cost' => 'sometimes|nullable|numeric|min:0|max:999999999.99',
            'scheduled_date' => 'sometimes|nullable|date',
            'started_at' => 'sometimes|nullable|datetime',
            'completed_at' => 'sometimes|nullable|datetime|after_or_equal:started_at',
            'closed_at' => 'sometimes|nullable|datetime|after_or_equal:completed_at',
            'notes' => 'sometimes|nullable|string',
            'completion_notes' => 'sometimes|nullable|string',
            'work_performed' => 'sometimes|nullable|string',
            'parts_used' => 'sometimes|nullable|array',
            'tools_used' => 'sometimes|nullable|array',
            'safety_precautions' => 'sometimes|nullable|string',
            'follow_up_required' => 'sometimes|boolean',
            'follow_up_date' => 'sometimes|nullable|date',
            'customer_satisfaction' => 'sometimes|nullable|integer|min:1|max:5',
            'internal_notes' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $oldValues = $workOrder->only(array_keys($validated));

        DB::beginTransaction();
        try {
            // Handle status transitions
            if (isset($validated['status'])) {
                $newStatus = WorkOrderStatus::from($validated['status']);
                $oldStatus = $workOrder->status;
                
                if (!$oldStatus->canTransitionTo($newStatus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status transition',
                        'errors' => [
                            'status' => ["Cannot transition from {$oldStatus->getDisplayName()} to {$newStatus->getDisplayName()}"]
                        ],
                    ], 422);
                }

                // Set timestamps based on status
                if ($newStatus === WorkOrderStatus::IN_PROGRESS && !$workOrder->started_at) {
                    $validated['started_at'] = now();
                }
                
                if ($newStatus === WorkOrderStatus::COMPLETED && !$workOrder->completed_at) {
                    $validated['completed_at'] = now();
                }
                
                if ($newStatus === WorkOrderStatus::CLOSED && !$workOrder->closed_at) {
                    $validated['closed_at'] = now();
                }

                // Log status change
                WorkOrderHistory::logStatusChange($workOrder, $oldStatus, $newStatus, auth()->user());
            }

            // Handle assignment changes
            if (isset($validated['assigned_to']) && $validated['assigned_to'] !== $workOrder->assigned_to) {
                $oldAssignee = $workOrder->assignedTo ? User::find($workOrder->assigned_to) : null;
                $newAssignee = User::find($validated['assigned_to']);
                
                if ($newAssignee) {
                    WorkOrderHistory::logAssignmentChange($workOrder, $oldAssignee, $newAssignee, auth()->user());
                }
            }

            // Log field changes
            foreach ($validated as $field => $value) {
                if ($field !== 'status' && $field !== 'assigned_to' && 
                    isset($oldValues[$field]) && $oldValues[$field] != $value) {
                    WorkOrderHistory::logFieldChange($workOrder, $field, $oldValues[$field], $value, auth()->user());
                }
            }

            $workOrder->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order updated successfully',
                'data' => $workOrder->load([
                    'asset', 'assignedTo', 'creator', 'requester',
                    'location', 'department'
                ]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work order',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Remove the specified work order from storage.
     */
    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        // Check if work order can be deleted
        if (in_array($workOrder->status->value, ['in_progress', 'completed', 'closed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete work order in ' . $workOrder->status->getDisplayName() . ' status',
            ], 422);
        }

        if ($workOrder->parts()->exists() || $workOrder->laborEntries()->exists() || $workOrder->attachments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete work order with associated parts, labor, or attachments',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Log deletion
            WorkOrderHistory::logCustomAction($workOrder, 'deleted', 'Work order deleted', auth()->user());

            $workOrder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete work order',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get work order statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_work_orders' => WorkOrder::count(),
            'by_status' => WorkOrder::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                }),
            'by_priority' => WorkOrder::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->priority => $item->count];
                }),
            'by_type' => WorkOrder::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type => $item->count];
                }),
            'overdue_count' => WorkOrder::overdue()->count(),
            'due_today_count' => WorkOrder::dueToday()->count(),
            'due_this_week_count' => WorkOrder::dueThisWeek()->count(),
            'total_cost' => WorkOrder::sum('actual_cost'),
            'total_hours' => WorkOrder::sum('actual_hours'),
            'completion_rate' => WorkOrder::where('status', 'completed')->count() / max(WorkOrder::count(), 1) * 100,
            'average_completion_time' => WorkOrder::whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->avg(DB::raw('TIMESTAMPDIFF(HOUR, started_at, completed_at)')),
            'recent_work_orders' => WorkOrder::with(['asset', 'assignedTo'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get work orders by status.
     */
    public function byStatus(string $status): JsonResponse
    {
        if (!in_array($status, ['requested', 'approved', 'assigned', 'scheduled', 'in_progress', 'on_hold', 'completed', 'closed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status',
            ], 422);
        }

        $workOrders = WorkOrder::where('status', $status)
            ->with(['asset', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $workOrders->items(),
            'pagination' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
            ],
        ]);
    }

    /**
     * Get work orders assigned to current user.
     */
    public function myWorkOrders(Request $request): JsonResponse
    {
        $query = WorkOrder::where('assigned_to', auth()->id())
            ->with(['asset', 'creator', 'location', 'department']);

        // Apply filters
        if ($request->has('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        $workOrders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $workOrders->items(),
            'pagination' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
            ],
        ]);
    }

    /**
     * Add comment to work order.
     */
    public function addComment(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:2000',
            'is_internal' => 'boolean',
            'is_technician_note' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = $workOrder->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->input('comment'),
            'is_internal' => $request->boolean('is_internal', false),
            'is_technician_note' => $request->boolean('is_technician_note', false),
        ]);

        // Log comment addition
        WorkOrderHistory::logCustomAction($workOrder, 'comment_added', 'Comment added to work order', auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Get work order calendar view.
     */
    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $workOrders = WorkOrder::whereBetween('scheduled_date', [$startDate, $endDate])
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->with(['asset', 'assignedTo'])
            ->orderBy('scheduled_date')
            ->get();

        $calendarEvents = $workOrders->map(function ($workOrder) {
            return [
                'id' => $workOrder->id,
                'title' => $workOrder->title,
                'start' => $workOrder->scheduled_date->format('Y-m-d'),
                'backgroundColor' => $workOrder->priority->getColor(),
                'borderColor' => $workOrder->status->getColor(),
                'extendedProps' => [
                    'priority' => $workOrder->priority->getDisplayName(),
                    'status' => $workOrder->status->getDisplayName(),
                    'type' => $workOrder->type->getDisplayName(),
                    'asset_name' => $workOrder->asset?->name,
                    'assigned_to' => $workOrder->assignedTo?->full_name,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $calendarEvents,
        ]);
    }
}
