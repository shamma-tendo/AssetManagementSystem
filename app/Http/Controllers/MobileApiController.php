<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkOrderStatus;
use App\Models\MaintenanceHistory;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Services\SearchService;
use App\Services\MaintenanceSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MobileApiController extends Controller
{
    protected SearchService $searchService;
    protected MaintenanceSchedulingService $schedulingService;

    public function __construct(SearchService $searchService, MaintenanceSchedulingService $schedulingService)
    {
        $this->searchService = $searchService;
        $this->schedulingService = $schedulingService;
    }

    /**
     * Get mobile dashboard data for the authenticated user.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        // Get user's work orders
        $myWorkOrders = WorkOrder::where('assigned_to', $user->id)
            ->with(['asset', 'location'])
            ->orderBy('scheduled_date', 'asc')
            ->limit(10)
            ->get();

        // Get today's schedule
        $todayWorkOrders = $myWorkOrders->filter(function ($wo) use ($today) {
            return $wo->scheduled_date === $today;
        });

        // Get overdue work orders
        $overdueWorkOrders = $myWorkOrders->filter(function ($wo) {
            return $wo->scheduled_date && $wo->scheduled_date->isPast() && 
                   !in_array($wo->status->value, ['completed', 'closed']);
        });

        // Get upcoming maintenance
        $upcomingMaintenance = MaintenanceSchedule::where('assigned_technician_id', $user->id)
            ->active()
            ->with('asset')
            ->whereBetween('next_due_date', [now(), now()->addDays(30)])
            ->orderBy('next_due_date')
            ->limit(10)
            ->get();

        // Get recent assets assigned to user
        $recentAssets = Asset::whereHas('workOrders', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })
            ->with(['category', 'location'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role->getDisplayName(),
                    'avatar' => $user->avatar_url ?? null,
                ],
                'summary' => [
                    'today_work_orders' => $todayWorkOrders->count(),
                    'overdue_work_orders' => $overdueWorkOrders->count(),
                    'upcoming_maintenance' => $upcomingMaintenance->count(),
                    'total_assigned' => $myWorkOrders->count(),
                ],
                'today_work_orders' => $todayWorkOrders->map(function ($wo) {
                    return [
                        'id' => $wo->id,
                        'title' => $wo->title,
                        'asset_name' => $wo->asset->name ?? 'N/A',
                        'location_name' => $wo->location->name ?? 'N/A',
                        'priority' => $wo->priority->getDisplayName(),
                        'priority_color' => $wo->priority->getColor(),
                        'status' => $wo->status->getDisplayName(),
                        'status_color' => $wo->status->getColor(),
                        'scheduled_time' => $wo->scheduled_date ? $wo->scheduled_date->format('H:i') : null,
                    ];
                }),
                'overdue_work_orders' => $overdueWorkOrders->map(function ($wo) {
                    return [
                        'id' => $wo->id,
                        'title' => $wo->title,
                        'asset_name' => $wo->asset->name ?? 'N/A',
                        'location_name' => $wo->location->name ?? 'N/A',
                        'priority' => $wo->priority->getDisplayName(),
                        'priority_color' => $wo->priority->getColor(),
                        'days_overdue' => $wo->scheduled_date ? now()->diffInDays($wo->scheduled_date) : 0,
                        'scheduled_date' => $wo->scheduled_date?->format('Y-m-d'),
                    ];
                }),
                'upcoming_maintenance' => $upcomingMaintenance->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'title' => $schedule->title,
                        'asset_name' => $schedule->asset->name ?? 'N/A',
                        'maintenance_type' => $schedule->maintenance_type->getDisplayName(),
                        'next_due_date' => $schedule->next_due_date->format('Y-m-d'),
                        'days_until_due' => $schedule->days_until_due,
                        'priority_level' => $schedule->priority_level,
                    ];
                }),
                'recent_assets' => $recentAssets->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'serial_number' => $asset->serial_number,
                        'category_name' => $asset->category->name ?? 'N/A',
                        'location_name' => $asset->location->name ?? 'N/A',
                        'status' => $asset->status,
                        'last_work_order' => $asset->workOrders()->where('assigned_to', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->first()?->created_at?->format('Y-m-d H:i'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get work orders for mobile view.
     */
    public function workOrders(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = WorkOrder::where('assigned_to', $user->id)
            ->with(['asset', 'location', 'department']);

        // Apply filters
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

        if ($request->has('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->input('date_to'));
        }

        // Mobile-specific filters
        if ($request->boolean('today', false)) {
            $query->whereDate('scheduled_date', today());
        }

        if ($request->boolean('overdue', false)) {
            $query->where('scheduled_date', '<', now())
                  ->whereNotIn('status', ['completed', 'closed']);
        }

        if ($request->boolean('this_week', false)) {
            $query->whereBetween('scheduled_date', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'scheduled_date');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 20), 50); // Limit for mobile
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
     * Get work order details optimized for mobile.
     */
    public function workOrderDetails(WorkOrder $workOrder): JsonResponse
    {
        // Verify user has access to this work order
        if ($workOrder->assigned_to !== auth()->id() && !auth()->user()->hasRole(['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $workOrder->load([
            'asset',
            'location',
            'department',
            'creator',
            'assignedTo',
            'parts',
            'laborEntries' => function ($query) {
                $query->with('technician');
            },
            'attachments',
            'comments' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'maintenanceSchedule',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'work_order' => [
                    'id' => $workOrder->id,
                    'title' => $workOrder->title,
                    'description' => $workOrder->description,
                    'priority' => $workOrder->priority->getDisplayName(),
                    'priority_color' => $workOrder->priority->getColor(),
                    'status' => $workOrder->status->getDisplayName(),
                    'status_color' => $workOrder->status->getColor(),
                    'type' => $workOrder->type->getDisplayName(),
                    'scheduled_date' => $workOrder->scheduled_date?->format('Y-m-d'),
                    'scheduled_time' => $workOrder->scheduled_date?->format('H:i'),
                    'started_at' => $workOrder->started_at?->format('Y-m-d H:i'),
                    'completed_at' => $workOrder->completed_at?->format('Y-m-d H:i'),
                    'estimated_hours' => $workOrder->estimated_hours,
                    'actual_hours' => $workOrder->actual_hours,
                    'estimated_cost' => $workOrder->estimated_cost,
                    'actual_cost' => $workOrder->actual_cost,
                    'notes' => $workOrder->notes,
                    'completion_notes' => $workOrder->completion_notes,
                    'work_performed' => $workOrder->work_performed,
                    'duration' => $workOrder->duration,
                    'cost_variance' => $workOrder->cost_variance,
                    'hours_variance' => $workOrder->hours_variance,
                    'total_cost' => $workOrder->total_cost,
                    'follow_up_required' => $workOrder->follow_up_required,
                    'follow_up_date' => $workOrder->follow_up_date?->format('Y-m-d'),
                    'created_at' => $workOrder->created_at->format('Y-m-d H:i'),
                ],
                'asset' => $workOrder->asset ? [
                    'id' => $workOrder->asset->id,
                    'name' => $workOrder->asset->name,
                    'serial_number' => $workOrder->asset->serial_number,
                    'category' => $workOrder->asset->category?->name,
                    'location' => $workOrder->asset->location?->name,
                    'department' => $workOrder->asset->department?->name,
                    'status' => $workOrder->asset->status,
                    'purchase_date' => $workOrder->asset->purchase_date?->format('Y-m-d'),
                    'current_value' => $workOrder->asset->current_value,
                    'manufacturer' => $workOrder->asset->manufacturer,
                    'model' => $workOrder->asset->model,
                ] : null,
                'location' => $workOrder->location ? [
                    'id' => $workOrder->location->id,
                    'name' => $workOrder->location->name,
                    'address' => $workOrder->location->full_address,
                    'city' => $workOrder->location->city,
                    'state' => $workOrder->location->state,
                ] : null,
                'assigned_technician' => $workOrder->assignedTo ? [
                    'id' => $workOrder->assignedTo->id,
                    'name' => $workOrder->assignedTo->full_name,
                    'email' => $workOrder->assignedTo->email,
                    'phone' => $workOrder->assignedTo->phone,
                    'avatar' => $workOrder->assignedTo->avatar_url ?? null,
                ] : null,
                'creator' => $workOrder->creator ? [
                    'id' => $workOrder->creator->id,
                    'name' => $workOrder->creator->full_name,
                    'email' => $workOrder->creator->email,
                ] : null,
                'parts' => $workOrder->parts->map(function ($part) {
                    return [
                        'id' => $part->id,
                        'name' => $part->part_name,
                        'part_number' => $part->part_number,
                        'quantity_used' => $part->quantity_used,
                        'unit_cost' => $part->unit_cost,
                        'total_cost' => $part->total_cost,
                        'supplier' => $part->supplier,
                        'notes' => $part->notes,
                    ];
                }),
                'labor_entries' => $workOrder->laborEntries->map(function ($labor) {
                    return [
                        'id' => $labor->id,
                        'technician' => $labor->technician ? [
                            'id' => $labor->technician->id,
                            'name' => $labor->technician->full_name,
                        ] : null,
                        'hours_worked' => $labor->hours_worked,
                        'hourly_rate' => $labor->hourly_rate,
                        'total_cost' => $labor->total_cost,
                        'work_description' => $labor->work_description,
                        'start_time' => $labor->start_time?->format('H:i'),
                        'end_time' => $labor->end_time?->format('H:i'),
                        'duration' => $labor->duration,
                        'notes' => $labor->notes,
                    ];
                }),
                'attachments' => $workOrder->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'original_name' => $attachment->original_name,
                        'file_size' => $attachment->file_size_human,
                        'mime_type' => $attachment->mime_type,
                        'uploaded_at' => $attachment->created_at->format('Y-m-d H:i'),
                        'uploader' => $attachment->uploader?->full_name,
                    ];
                }),
                'comments' => $workOrder->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'is_internal' => $comment->is_internal,
                        'is_technician_note' => $comment->is_technician_note,
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->full_name,
                            'avatar' => $comment->user->avatar_url ?? null,
                        ],
                        'created_at' => $comment->created_at->format('Y-m-d H:i'),
                    ];
                }),
                'maintenance_schedule' => $workOrder->maintenanceSchedule ? [
                    'id' => $workOrder->maintenanceSchedule->id,
                    'title' => $workOrder->maintenanceSchedule->title,
                    'maintenance_type' => $workOrder->maintenanceSchedule->maintenance_type->getDisplayName(),
                    'frequency_type' => $workOrder->maintenanceSchedule->frequency_type->getDisplayName(),
                    'next_due_date' => $workOrder->maintenanceSchedule->next_due_date?->format('Y-m-d'),
                ] : null,
            ],
        ]);
    }

    /**
     * Update work order status for mobile.
     */
    public function updateWorkOrderStatus(Request $request, WorkOrder $workOrder): JsonResponse
    {
        // Verify user has access to this work order
        if ($workOrder->assigned_to !== auth()->id() && !auth()->user()->hasRole(['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:in_progress,on_hold,completed,closed',
            'notes' => 'nullable|string|max:2000',
            'completion_notes' => 'nullable|string|max:2000|required_if:status,completed',
            'work_performed' => 'nullable|string|max:5000',
            'actual_hours' => 'nullable|numeric|min:0|max:9999.99',
            'actual_cost' => 'nullable|numeric|min:0|max:999999999.99',
            'parts_used' => 'nullable|array',
            'tools_used' => 'nullable|array',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after_or_equal:today',
            'customer_satisfaction' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $oldStatus = $workOrder->status;
        $newStatus = \App\Models\WorkOrderStatus::from($validated['status']);

        // Validate status transition
        if (!$oldStatus->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition',
                'errors' => [
                    'status' => ["Cannot transition from {$oldStatus->getDisplayName()} to {$newStatus->getDisplayName()}"]
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Set timestamps based on status
            if ($newStatus === \App\Models\WorkOrderStatus::IN_PROGRESS && !$workOrder->started_at) {
                $validated['started_at'] = now();
            }
            
            if ($newStatus === \App\Models\WorkOrderStatus::COMPLETED && !$workOrder->completed_at) {
                $validated['completed_at'] = now();
            }
            
            if ($newStatus === \App\Models\WorkOrderStatus::CLOSED && !$workOrder->closed_at) {
                $validated['closed_at'] = now();
            }

            $workOrder->update($validated);

            // Log status change
            \App\Models\WorkOrderHistory::logStatusChange($workOrder, $oldStatus, $newStatus, auth()->user());

            // If completed, create maintenance history if from schedule
            if ($newStatus === \App\Models\WorkOrderStatus::COMPLETED && $workOrder->maintenance_schedule_id) {
                MaintenanceHistory::create([
                    'maintenance_schedule_id' => $workOrder->maintenance_schedule_id,
                    'work_order_id' => $workOrder->id,
                    'asset_id' => $workOrder->asset_id,
                    'performed_by' => auth()->id(),
                    'performed_date' => now(),
                    'actual_duration_hours' => $validated['actual_hours'] ?? null,
                    'estimated_duration_hours' => $workOrder->estimated_hours,
                    'actual_cost' => $validated['actual_cost'] ?? null,
                    'estimated_cost' => $workOrder->estimated_cost,
                    'notes' => $validated['notes'] ?? null,
                    'parts_used' => $validated['parts_used'] ?? null,
                    'tools_used' => $validated['tools_used'] ?? null,
                    'completion_status' => 'completed',
                    'completed_on_time' => $workOrder->scheduled_date && $workOrder->scheduled_date->gte(now()),
                    'created_by' => auth()->id(),
                ]);

                // Update maintenance schedule
                $schedule = $workOrder->maintenanceSchedule;
                $schedule->markAsPerformed();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order status updated successfully',
                'data' => $workOrder->fresh()->load(['asset', 'location']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work order status',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Add time entry to work order.
     */
    public function addTimeEntry(Request $request, WorkOrder $workOrder): JsonResponse
    {
        // Verify user has access to this work order
        if ($workOrder->assigned_to !== auth()->id() && !auth()->user()->hasRole(['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'hours_worked' => 'required|numeric|min:0.1|max:24',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'work_description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['technician_id'] = auth()->id();
        $validated['hourly_rate'] = $validated['hourly_rate'] ?? auth()->user()->hourly_rate ?? 50.00;

        // Set start and end times if provided
        if (isset($validated['start_time'])) {
            $validated['start_time'] = now()->setTimeFromTimeString($validated['start_time']);
        }
        if (isset($validated['end_time'])) {
            $validated['end_time'] = now()->setTimeFromTimeString($validated['end_time']);
        }

        $timeEntry = $workOrder->laborEntries()->create($validated);

        // Update work order actual hours
        $totalHours = $workOrder->laborEntries()->sum('hours_worked');
        $workOrder->update(['actual_hours' => $totalHours]);

        return response()->json([
            'success' => true,
            'message' => 'Time entry added successfully',
            'data' => $timeEntry->load('technician'),
        ], 201);
    }

    /**
     * Add comment to work order.
     */
    public function addComment(Request $request, WorkOrder $workOrder): JsonResponse
    {
        // Verify user has access to this work order
        if ($workOrder->assigned_to !== auth()->id() && !auth()->user()->hasRole(['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:2000',
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
            'is_internal' => false,
            'is_technician_note' => $request->boolean('is_technician_note', false),
        ]);

        // Log comment addition
        \App\Models\WorkOrderHistory::logCustomAction($workOrder, 'comment_added', 'Comment added to work order', auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Upload attachment to work order.
     */
    public function uploadAttachment(Request $request, WorkOrder $workOrder): JsonResponse
    {
        // Verify user has access to this work order
        if ($workOrder->assigned_to !== auth()->id() && !auth()->user()->hasRole(['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->store('work-order-attachments', 'public');

        $attachment = $workOrder->attachments()->create([
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'description' => $request->input('description'),
            'is_public' => $request->boolean('is_public', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $attachment->load('uploader'),
        ], 201);
    }

    /**
     * Get assets optimized for mobile.
     */
    public function assets(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Asset::with(['category', 'location', 'department']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Mobile-specific filters
        if ($request->has('my_assets')) {
            $query->whereHas('workOrders', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            });
        }

        if ($request->boolean('active', false)) {
            $query->where('status', 'active');
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 20), 50);
        $assets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $assets->items(),
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'from' => $assets->firstItem(),
                'to' => $assets->lastItem(),
            ],
        ]);
    }

    /**
     * Get asset details optimized for mobile.
     */
    public function assetDetails(Asset $asset): JsonResponse
    {
        $asset->load([
            'category',
            'location',
            'department',
            'creator',
            'workOrders' => function ($query) {
                $query->with(['assignedTo'])
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            },
            'maintenanceSchedules' => function ($query) {
                $query->active()
                      ->orderBy('next_due_date')
                      ->limit(5);
            },
            'depreciationRecords' => function ($query) {
                $query->orderBy('record_date', 'desc')
                      ->limit(5);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'asset' => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'serial_number' => $asset->serial_number,
                    'status' => $asset->status,
                    'category' => $asset->category ? [
                        'id' => $asset->category->id,
                        'name' => $asset->category->name,
                        'pm_frequency_months' => $asset->category->pm_frequency_months,
                        'useful_life_years' => $asset->category->useful_life_years,
                    ] : null,
                    'location' => $asset->location ? [
                        'id' => $asset->location->id,
                        'name' => $asset->location->name,
                        'address' => $asset->location->full_address,
                    ] : null,
                    'department' => $asset->department ? [
                        'id' => $asset->department->id,
                        'name' => $asset->department->name,
                        'manager' => $asset->department->manager?->full_name,
                    ] : null,
                    'purchase_date' => $asset->purchase_date?->format('Y-m-d'),
                    'purchase_cost' => $asset->purchase_cost,
                    'current_value' => $asset->current_value,
                    'manufacturer' => $asset->manufacturer,
                    'model' => $asset->model,
                    'warranty_expiry' => $asset->warranty_expiry?->format('Y-m-d'),
                    'description' => $asset->description,
                    'notes' => $asset->notes,
                    'created_at' => $asset->created_at->format('Y-m-d H:i'),
                    'updated_at' => $asset->updated_at->format('Y-m-d H:i'),
                ],
                'recent_work_orders' => $asset->workOrders->map(function ($wo) {
                    return [
                        'id' => $wo->id,
                        'title' => $wo->title,
                        'status' => $wo->status->getDisplayName(),
                        'priority' => $wo->priority->getDisplayName(),
                        'assigned_to' => $wo->assignedTo?->full_name,
                        'scheduled_date' => $wo->scheduled_date?->format('Y-m-d'),
                        'created_at' => $wo->created_at->format('Y-m-d H:i'),
                    ];
                }),
                'maintenance_schedules' => $asset->maintenanceSchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'title' => $schedule->title,
                        'maintenance_type' => $schedule->maintenance_type->getDisplayName(),
                        'frequency_type' => $schedule->frequency_type->getDisplayName(),
                        'next_due_date' => $schedule->next_due_date->format('Y-m-d'),
                        'days_until_due' => $schedule->days_until_due,
                        'priority_level' => $schedule->priority_level,
                        'status_display' => $schedule->status_display,
                    ];
                }),
                'depreciation_records' => $asset->depreciationRecords->map(function ($record) {
                    return [
                        'record_date' => $record->record_date->format('Y-m-d'),
                        'depreciation_amount' => $record->depreciation_amount,
                        'accumulated_depreciation' => $record->accumulated_depreciation,
                        'book_value' => $record->book_value,
                        'description' => $record->description,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get maintenance schedules for mobile.
     */
    public function maintenanceSchedules(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = MaintenanceSchedule::active()
            ->with(['asset', 'assignedTechnician']);

        // Apply filters
        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('maintenance_type')) {
            $query->where('maintenance_type', $request->input('maintenance_type'));
        }

        // Mobile-specific filters
        if ($request->has('assigned_to_me')) {
            $query->where('assigned_technician_id', $user->id);
        }

        if ($request->boolean('overdue', false)) {
            $query->overdue();
        }

        if ($request->boolean('due_soon', false)) {
            $query->dueSoon($request->input('days', 30));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'next_due_date');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 20), 50);
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
     * Get mobile calendar view.
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = auth()->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Get work orders
        $workOrders = WorkOrder::where('assigned_to', $user->id)
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->with(['asset', 'location'])
            ->orderBy('scheduled_date')
            ->get();

        // Get maintenance schedules
        $schedules = MaintenanceSchedule::where('assigned_technician_id', $user->id)
            ->active()
            ->whereBetween('next_due_date', [$startDate, $endDate])
            ->with(['asset'])
            ->orderBy('next_due_date')
            ->get();

        $calendarEvents = [];

        // Add work orders to calendar
        foreach ($workOrders as $workOrder) {
            $calendarEvents[] = [
                'id' => 'wo_' . $workOrder->id,
                'title' => $workOrder->title,
                'start' => $workOrder->scheduled_date->format('Y-m-d'),
                'backgroundColor' => $workOrder->priority->getColor(),
                'borderColor' => $workOrder->status->getColor(),
                'type' => 'work_order',
                'extendedProps' => [
                    'work_order_id' => $workOrder->id,
                    'priority' => $workOrder->priority->getDisplayName(),
                    'status' => $workOrder->status->getDisplayName(),
                    'asset_name' => $workOrder->asset?->name,
                    'location_name' => $workOrder->location?->name,
                    'time' => $workOrder->scheduled_date?->format('H:i'),
                ],
            ];
        }

        // Add maintenance schedules to calendar
        foreach ($schedules as $schedule) {
            $calendarEvents[] = [
                'id' => 'ms_' . $schedule->id,
                'title' => $schedule->title,
                'start' => $schedule->next_due_date->format('Y-m-d'),
                'backgroundColor' => $schedule->isOverdue() ? 'red' : ($schedule->isDueSoon() ? 'orange' : 'blue'),
                'borderColor' => 'blue',
                'type' => 'maintenance_schedule',
                'extendedProps' => [
                    'schedule_id' => $schedule->id,
                    'maintenance_type' => $schedule->maintenance_type->getDisplayName(),
                    'frequency_type' => $schedule->frequency_type->getDisplayName(),
                    'asset_name' => $schedule->asset?->name,
                    'priority_level' => $schedule->priority_level,
                    'status' => $schedule->status_display,
                    'auto_create_work_order' => $schedule->auto_create_work_order,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $calendarEvents,
        ]);
    }

    /**
     * Get mobile search results.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:100',
            'type' => 'required|in:assets,work_orders,maintenance_schedules',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type');
        $query = $request->input('query');
        $perPage = $request->input('per_page', 20);

        $results = match($type) {
            'assets' => $this->searchAssets($query, $perPage),
            'work_orders' => $this->searchWorkOrders($query, $perPage),
            'maintenance_schedules' => $this->searchMaintenanceSchedules($query, $perPage),
        };

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Search assets for mobile.
     */
    private function searchAssets(string $query, int $perPage): array
    {
        $user = auth()->user();
        
        $assets = Asset::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('serial_number', 'like', "%{$query}%")
                  ->orWhere('manufacturer', 'like', "%{$query}%")
                  ->orWhere('model', 'like', "%{$query}%");
            })
            ->with(['category', 'location'])
            ->orderBy('name')
            ->paginate($perPage);

        return [
            'items' => $assets->items(),
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ];
    }

    /**
     * Search work orders for mobile.
     */
    private function searchWorkOrders(string $query, int $perPage): array
    {
        $user = auth()->user();
        
        $workOrders = WorkOrder::where('assigned_to', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('work_performed', 'like', "%{$query}%");
            })
            ->with(['asset', 'location'])
            ->orderBy('scheduled_date')
            ->paginate($perPage);

        return [
            'items' => $workOrders->items(),
            'pagination' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
            ],
        ];
    }

    /**
     * Search maintenance schedules for mobile.
     */
    private function searchMaintenanceSchedules(string $query, int $perPage): array
    {
        $user = auth()->user();
        
        $schedules = MaintenanceSchedule::where('assigned_technician_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['asset'])
            ->orderBy('next_due_date')
            ->paginate($perPage);

        return [
            'items' => $schedules->items(),
            'pagination' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ];
    }

    /**
     * Get user profile for mobile.
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'username' => $user->username,
                'phone' => $user->phone,
                'role' => $user->role->getDisplayName(),
                'department' => $user->department?->name,
                'location' => $user->location?->name,
                'avatar' => $user->avatar_url ?? null,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i'),
                'created_at' => $user->created_at->format('Y-m-d H:i'),
                'preferences' => [
                    'notifications' => true,
                    'theme' => 'light',
                    'language' => 'en',
                ],
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20',
            'preferences' => 'nullable|array',
            'avatar' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,gif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarPath = $avatar->store('avatars', 'public');
            $validated['avatar_url'] = url('storage/' . $avatarPath);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Get notifications for mobile.
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = min($request->input('limit', 20), 50);

        // Get overdue work orders
        $overdueWorkOrders = WorkOrder::where('assigned_to', $user->id)
            ->where('scheduled_date', '<', now())
            ->whereNotIn('status', ['completed', 'closed'])
            ->with(['asset'])
            ->orderBy('scheduled_date')
            ->get();

        // Get due soon work orders
        $dueSoonWorkOrders = WorkOrder::where('assigned_to', $user->id)
            ->whereBetween('scheduled_date', [now(), now()->addDays(7)])
            ->whereNotIn('status', ['completed', 'closed'])
            ->with(['asset'])
            ->orderBy('scheduled_date')
            ->get();

        // Get overdue maintenance schedules
        $overdueSchedules = MaintenanceSchedule::where('assigned_technician_id', $user->id)
            ->overdue()
            ->with(['asset'])
            ->orderBy('next_due_date')
            ->get();

        $notifications = [];

        // Add overdue work orders
        foreach ($overdueWorkOrders as $wo) {
            $notifications[] = [
                'id' => 'overdue_wo_' . $wo->id,
                'type' => 'overdue_work_order',
                'title' => 'Overdue Work Order',
                'message' => "Work order '{$wo->title}' for {$wo->asset->name} is overdue",
                'priority' => 'high',
                'created_at' => $wo->scheduled_date,
                'data' => [
                    'work_order_id' => $wo->id,
                    'asset_name' => $wo->asset->name,
                    'days_overdue' => now()->diffInDays($wo->scheduled_date),
                ],
            ];
        }

        // Add due soon work orders
        foreach ($dueSoonWorkOrders as $wo) {
            $notifications[] = [
                'id' => 'due_soon_wo_' . $wo->id,
                'type' => 'due_soon_work_order',
                'title' => 'Work Order Due Soon',
                'message' => "Work order '{$wo->title}' for {$wo->asset->name} is due soon",
                'priority' => 'medium',
                'created_at' => $wo->scheduled_date,
                'data' => [
                    'work_order_id' => $wo->id,
                    'asset_name' => $wo->asset->name,
                    'days_until_due' => now()->diffInDays($wo->scheduled_date),
                ],
            ];
        }

        // Add overdue maintenance schedules
        foreach ($overdueSchedules as $schedule) {
            $notifications[] = [
                'id' => 'overdue_ms_' . $schedule->id,
                'type' => 'overdue_maintenance',
                'title' => 'Overdue Maintenance',
                'message' => "Maintenance '{$schedule->title}' for {$schedule->asset->name} is overdue",
                'priority' => 'high',
                'created_at' => $schedule->next_due_date,
                'data' => [
                    'schedule_id' => $schedule->id,
                    'asset_name' => $schedule->asset->name,
                    'days_overdue' => now()->diffInDays($schedule->next_due_date),
                ],
            ];
        }

        // Sort by priority and date
        usort($notifications, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] === 'high' ? -1 : 1;
            }
            return $b['created_at'] <=> $a['created_at'];
        });

        return response()->json([
            'success' => true,
            'data' => array_slice($notifications, 0, $limit),
        ]);
    }

    /**
     * Get system statistics for mobile dashboard.
     */
    public function statistics(): JsonResponse
    {
        $user = auth()->user();

        $stats = [
            'work_orders' => [
                'total' => WorkOrder::where('assigned_to', $user->id)->count(),
                'completed' => WorkOrder::where('assigned_to', $user->id)->where('status', 'completed')->count(),
                'in_progress' => WorkOrder::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
                'overdue' => WorkOrder::where('assigned_to', $user->id)
                    ->where('scheduled_date', '<', now())
                    ->whereNotIn('status', ['completed', 'closed'])
                    ->count(),
                'due_today' => WorkOrder::where('assigned_to', $user->id)
                    ->whereDate('scheduled_date', today())
                    ->count(),
                'due_this_week' => WorkOrder::where('assigned_to', $user->id)
                    ->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ],
            'maintenance_schedules' => [
                'total' => MaintenanceSchedule::where('assigned_technician_id', $user->id)->count(),
                'active' => MaintenanceSchedule::where('assigned_technician_id', $user->id)->active()->count(),
                'overdue' => MaintenanceSchedule::where('assigned_technician_id', $user->id)->overdue()->count(),
                'due_soon' => MaintenanceSchedule::where('assigned_technician_id', $user->id)->dueSoon()->count(),
            ],
            'assets' => [
                'total' => Asset::whereHas('workOrders', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })->count(),
                'active' => Asset::whereHas('workOrders', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })->where('status', 'active')->count(),
                'under_maintenance' => Asset::whereHas('workOrders', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })->where('status', 'under_maintenance')->count(),
            ],
            'performance' => [
                'completion_rate' => $this->calculateCompletionRate($user),
                'average_completion_time' => $this->calculateAverageCompletionTime($user),
                'total_hours_worked' => $this->calculateTotalHoursWorked($user),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate completion rate for user.
     */
    private function calculateCompletionRate(User $user): float
    {
        $totalWorkOrders = WorkOrder::where('assigned_to', $user->id)->count();
        $completedWorkOrders = WorkOrder::where('assigned_to', $user->id)->where('status', 'completed')->count();
        
        return $totalWorkOrders > 0 ? ($completedWorkOrders / $totalWorkOrders) * 100 : 100;
    }

    /**
     * Calculate average completion time for user.
     */
    private function calculateAverageCompletionTime(User $user): ?float
    {
        return WorkOrder::where('assigned_to', $user->id)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->avg(DB::raw('TIMESTAMPDIFF(HOUR, started_at, completed_at)'));
    }

    /**
     * Calculate total hours worked for user.
     */
    private function calculateTotalHoursWorked(User $user): float
    {
        return WorkOrder::where('assigned_to', $user->id)
            ->whereNotNull('actual_hours')
            ->sum('actual_hours');
    }
}
