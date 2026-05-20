<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\ChecklistTemplate;
use App\Models\InspectionAttachment;
use App\Models\InspectionComment;
use App\Models\InspectionHistory;
use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\InspectionType;
use App\Models\InspectionStatus;
use App\Models\InspectionPriority;
use App\Services\InspectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InspectionController extends Controller
{
    protected InspectionService $inspectionService;

    public function __construct(InspectionService $inspectionService)
    {
        $this->inspectionService = $inspectionService;
    }

    /**
     * Display a listing of the inspections.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inspection::with([
            'asset', 'inspector', 'supervisor', 'checklistTemplate', 'attachments'
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

        if ($request->has('inspection_type')) {
            $type = $request->input('inspection_type');
            if (is_array($type)) {
                $query->whereIn('inspection_type', $type);
            } else {
                $query->where('inspection_type', $type);
            }
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

        if ($request->has('inspector_id')) {
            $query->where('inspector_id', $request->input('inspector_id'));
        }

        if ($request->has('supervisor_id')) {
            $query->where('supervisor_id', $request->input('supervisor_id'));
        }

        // Date filters
        if ($request->has('scheduled_date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->input('scheduled_date_from'));
        }
        if ($request->has('scheduled_date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->input('scheduled_date_to'));
        }

        if ($request->has('performed_date_from')) {
            $query->whereDate('performed_date', '>=', $request->input('performed_date_from'));
        }
        if ($request->has('performed_date_to')) {
            $query->whereDate('performed_date', '<=', $request->input('performed_date_to'));
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

        if ($request->boolean('requires_follow_up', false)) {
            $query->requiresFollowUp();
        }

        if ($request->boolean('with_deficiencies', false)) {
            $query->withDeficiencies();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'scheduled_date');
        $sortOrder = $request->input('sort_order', 'asc');
        
        $validSortFields = [
            'title', 'inspection_type', 'status', 'priority', 'scheduled_date',
            'performed_date', 'overall_score', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('scheduled_date', 'asc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $inspections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inspections->items(),
            'pagination' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total(),
                'from' => $inspections->firstItem(),
                'to' => $inspections->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created inspection in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|uuid|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'inspection_type' => 'required|in:routine,periodic,special,emergency,preventive,compliance,safety,environmental,quality,operational,acceptance',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'inspector_id' => 'nullable|uuid|exists:users,id',
            'supervisor_id' => 'nullable|uuid|exists:users,id',
            'priority' => 'required|in:low,normal,high,urgent,critical',
            'checklist_template_id' => 'nullable|uuid|exists:checklist_templates,id',
            'checklist_items' => 'nullable|array',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
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

        // Set default inspector if not provided
        if (!isset($validated['inspector_id'])) {
            $validated['inspector_id'] = auth()->id();
        }

        // Set passing score based on template if not provided
        if (!isset($validated['passing_score']) && isset($validated['checklist_template_id'])) {
            $template = ChecklistTemplate::find($validated['checklist_template_id']);
            $validated['passing_score'] = $template->passing_score ?? 70;
        }

        DB::beginTransaction();
        try {
            $inspection = Inspection::create($validated);

            // Log creation
            InspectionHistory::logCustomAction($inspection, 'created', 'Inspection created', auth()->user());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection created successfully',
                'data' => $inspection->load([
                    'asset', 'inspector', 'supervisor', 'checklistTemplate'
                ]),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inspection',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified inspection.
     */
    public function show(Inspection $inspection): JsonResponse
    {
        $inspection->load([
            'asset', 'inspector', 'supervisor', 'checklistTemplate',
            'attachments' => function ($query) {
                $query->with('uploader')->orderBy('created_at', 'desc');
            },
            'comments' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'history' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'workOrders'
        ]);

        return response()->json([
            'success' => true,
            'data' => $inspection,
        ]);
    }

    /**
     * Update the specified inspection in storage.
     */
    public function update(Request $request, Inspection $inspection): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'inspection_type' => 'sometimes|required|in:routine,periodic,special,emergency,preventive,compliance,safety,environmental,quality,operational,acceptance',
            'scheduled_date' => 'sometimes|required|date',
            'inspector_id' => 'sometimes|nullable|uuid|exists:users,id',
            'supervisor_id' => 'sometimes|nullable|uuid|exists:users,id',
            'priority' => 'sometimes|required|in:low,normal,high,urgent,critical',
            'checklist_template_id' => 'sometimes|nullable|uuid|exists:checklist_templates,id',
            'checklist_items' => 'sometimes|nullable|array',
            'passing_score' => 'sometimes|nullable|numeric|min:0|max:100',
            'estimated_duration_minutes' => 'sometimes|nullable|integer|min:1|max:1440',
            'notes' => 'sometimes|nullable|string',
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
        $oldValues = $inspection->only(array_keys($validated));
        $validated['updated_by'] = auth()->id();

        DB::beginTransaction();
        try {
            // Handle status transitions
            if (isset($validated['status'])) {
                $newStatus = InspectionStatus::from($validated['status']);
                $oldStatus = $inspection->status;
                
                if (!$oldStatus->canTransitionTo($newStatus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status transition',
                        'errors' => [
                            'status' => ["Cannot transition from {$oldStatus->getDisplayName()} to {$newStatus->getDisplayName()}"]
                        ],
                    ], 422);
                }

                // Set performed date if completing
                if ($newStatus === InspectionStatus::COMPLETED && !$inspection->performed_date) {
                    $validated['performed_date'] = now();
                }

                // Log status change
                InspectionHistory::logStatusChange($inspection, $oldStatus, $newStatus, auth()->user());
            }

            // Handle checklist updates
            if (isset($validated['checklist_results'])) {
                $this->inspectionService->processChecklistResults($inspection, $validated['checklist_results']);
                unset($validated['checklist_results']); // Remove from main update
            }

            // Log field changes
            foreach ($validated as $field => $value) {
                if ($field !== 'status' && isset($oldValues[$field]) && $oldValues[$field] != $value) {
                    InspectionHistory::logFieldChange($inspection, $field, $oldValues[$field], $value, auth()->user());
                }
            }

            $inspection->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection updated successfully',
                'data' => $inspection->load([
                    'asset', 'inspector', 'supervisor', 'checklistTemplate'
                ]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inspection',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Remove the specified inspection from storage.
     */
    public function destroy(Inspection $inspection): JsonResponse
    {
        // Check if inspection can be deleted
        if (in_array($inspection->status->value, ['in_progress', 'completed', 'passed', 'failed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete inspection in ' . $inspection->status->getDisplayName() . ' status',
            ], 422);
        }

        if ($inspection->attachments()->exists() || $inspection->comments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete inspection with attachments or comments',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Log deletion
            InspectionHistory::logCustomAction($inspection, 'deleted', 'Inspection deleted', auth()->user());

            $inspection->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inspection',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get inspection statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_inspections' => Inspection::count(),
            'by_status' => Inspection::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                }),
            'by_type' => Inspection::select('inspection_type', DB::raw('count(*) as count'))
                ->groupBy('inspection_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->inspection_type => $item->count];
                }),
            'by_priority' => Inspection::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->priority => $item->count];
                }),
            'overdue_count' => Inspection::overdue()->count(),
            'due_today_count' => Inspection::dueToday()->count(),
            'due_this_week_count' => Inspection::dueThisWeek()->count(),
            'requires_follow_up_count' => Inspection::requiresFollowUp()->count(),
            'with_deficiencies_count' => Inspection::withDeficiencies()->count(),
            'average_score' => Inspection::whereNotNull('overall_score')->avg('overall_score'),
            'pass_rate' => $this->calculatePassRate(),
            'completion_rate' => $this->calculateCompletionRate(),
            'recent_inspections' => Inspection::with(['asset', 'inspector'])
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
     * Start an inspection.
     */
    public function startInspection(Request $request, Inspection $inspection): JsonResponse
    {
        if ($inspection->status !== InspectionStatus::SCHEDULED) {
            return response()->json([
                'success' => false,
                'message' => 'Inspection cannot be started in current status',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $inspection->update([
                'status' => InspectionStatus::IN_PROGRESS,
                'performed_date' => now(),
                'updated_by' => auth()->id(),
            ]);

            // Log status change
            InspectionHistory::logStatusChange(
                $inspection, 
                InspectionStatus::SCHEDULED, 
                InspectionStatus::IN_PROGRESS, 
                auth()->user()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection started successfully',
                'data' => $inspection->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start inspection',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Complete an inspection.
     */
    public function completeInspection(Request $request, Inspection $inspection): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'checklist_results' => 'required|array',
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'findings' => 'nullable|array',
            'recommendations' => 'nullable|array',
            'deficiencies' => 'nullable|array',
            'corrective_actions_required' => 'boolean',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after_or_equal:today',
            'next_inspection_date' => 'nullable|date|after:performed_date',
            'risk_assessment' => 'nullable|array',
            'safety_concerns' => 'nullable|array',
            'environmental_concerns' => 'nullable|array',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
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
            // Process checklist results and calculate score
            $scoreData = $this->inspectionService->processChecklistResults($inspection, $validated['checklist_results']);
            
            // Update inspection with completion data
            $updateData = [
                'status' => $inspection->isPassed() ? InspectionStatus::PASSED : InspectionStatus::FAILED,
                'duration_minutes' => $validated['duration_minutes'],
                'overall_score' => $scoreData['overall_score'],
                'max_score' => $scoreData['max_score'],
                'findings' => $validated['findings'] ?? [],
                'recommendations' => $validated['recommendations'] ?? [],
                'deficiencies' => $validated['deficiencies'] ?? [],
                'corrective_actions_required' => $validated['corrective_actions_required'],
                'follow_up_required' => $validated['follow_up_required'],
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'next_inspection_date' => $validated['next_inspection_date'] ?? null,
                'risk_assessment' => $validated['risk_assessment'] ?? [],
                'safety_concerns' => $validated['safety_concerns'] ?? [],
                'environmental_concerns' => $validated['environmental_concerns'] ?? [],
                'notes' => $validated['notes'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'updated_by' => auth()->id(),
            ];

            $oldStatus = $inspection->status;
            $inspection->update($updateData);

            // Log completion
            InspectionHistory::logStatusChange($inspection, $oldStatus, $inspection->status, auth()->user());
            InspectionHistory::logScoreChange($inspection, 0, $inspection->overall_score, auth()->user());

            // Create follow-up work order if required
            if ($inspection->corrective_actions_required && $inspection->deficiencies_count > 0) {
                $workOrder = $inspection->createWorkOrderFromFindings();
                InspectionHistory::logWorkOrderCreated($inspection, $workOrder, auth()->user());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection completed successfully',
                'data' => $inspection->fresh()->load(['asset', 'inspector']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete inspection',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Add comment to inspection.
     */
    public function addComment(Request $request, Inspection $inspection): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:2000',
            'comment_type' => 'required|in:general,finding,recommendation,correction,question,approval,rejection,note',
            'is_internal' => 'boolean',
            'is_private' => 'boolean',
            'attachment_references' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = $inspection->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->input('comment'),
            'comment_type' => $request->input('comment_type'),
            'is_internal' => $request->boolean('is_internal', false),
            'is_private' => $request->boolean('is_private', false),
            'attachment_references' => $request->input('attachment_references', []),
        ]);

        // Log comment addition
        InspectionHistory::logCommentAdded($inspection, $comment, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Upload attachment to inspection.
     */
    public function uploadAttachment(Request $request, Inspection $inspection): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
            'attachment_type' => 'required|in:photo,video,document,report,certificate,diagram,drawing,signature,other',
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
        $filePath = $file->store('inspection-attachments', 'public');

        $attachment = $inspection->attachments()->create([
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'description' => $request->input('description'),
            'attachment_type' => $request->input('attachment_type'),
            'is_public' => $request->boolean('is_public', true),
        ]);

        // Log file upload
        InspectionHistory::logFileUpload($inspection, $attachment, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $attachment->load('uploader'),
        ], 201);
    }

    /**
     * Get inspection calendar view.
     */
    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $inspections = Inspection::whereBetween('scheduled_date', [$startDate, $endDate])
            ->with(['asset', 'inspector'])
            ->orderBy('scheduled_date')
            ->get();

        $calendarEvents = $inspections->map(function ($inspection) {
            $backgroundColor = match($inspection->status->value) {
                'completed' => $inspection->isPassed() ? 'green' : 'red',
                'in_progress' => 'yellow',
                'scheduled' => 'blue',
                'cancelled' => 'gray',
                'postponed' => 'orange',
                default => 'blue',
            };

            return [
                'id' => $inspection->id,
                'title' => $inspection->title,
                'start' => $inspection->scheduled_date->format('Y-m-d'),
                'backgroundColor' => $backgroundColor,
                'borderColor' => $inspection->priority->getColor(),
                'extendedProps' => [
                    'inspection_type' => $inspection->inspection_type->getDisplayName(),
                    'priority' => $inspection->priority->getDisplayName(),
                    'status' => $inspection->status->getDisplayName(),
                    'asset_name' => $inspection->asset?->name,
                    'inspector' => $inspection->inspector?->full_name,
                    'overall_score' => $inspection->overall_score,
                    'is_passed' => $inspection->isPassed(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $calendarEvents,
        ]);
    }

    /**
     * Get inspection history.
     */
    public function history(Inspection $inspection): JsonResponse
    {
        $history = $inspection->history()
            ->with('user')
            ->orderBy('created_at', 'desc')
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
     * Generate inspection report.
     */
    public function generateReport(Inspection $inspection): JsonResponse
    {
        $inspection->load([
            'asset', 'inspector', 'supervisor', 'checklistTemplate',
            'attachments', 'comments', 'history'
        ]);

        $report = [
            'inspection' => $inspection->report_summary,
            'asset_details' => [
                'name' => $inspection->asset->name,
                'serial_number' => $inspection->asset->serial_number,
                'category' => $inspection->asset->category?->name,
                'location' => $inspection->asset->location?->name,
                'department' => $inspection->asset->department?->name,
                'status' => $inspection->asset->status,
                'purchase_date' => $inspection->asset->purchase_date?->format('Y-m-d'),
                'manufacturer' => $inspection->asset->manufacturer,
                'model' => $inspection->asset->model,
            ],
            'checklist_results' => $this->inspectionService->formatChecklistResults($inspection),
            'findings_summary' => [
                'total_findings' => $inspection->finding_count,
                'total_deficiencies' => $inspection->deficiency_count,
                'total_recommendations' => $inspection->recommendation_count,
                'risk_level' => $inspection->risk_level,
                'compliance_status' => $inspection->compliance_status,
            ],
            'attachments' => $inspection->attachments->map(function ($attachment) {
                return [
                    'file_name' => $attachment->original_name,
                    'type' => $attachment->attachment_type->getDisplayName(),
                    'size' => $attachment->file_size_human,
                    'uploaded_at' => $attachment->created_at->format('Y-m-d H:i'),
                    'uploader' => $attachment->uploader?->full_name,
                ];
            }),
            'comments' => $inspection->comments->public()->map(function ($comment) {
                return [
                    'comment' => $comment->comment,
                    'type' => $comment->comment_type->getDisplayName(),
                    'user' => $comment->user->full_name,
                    'created_at' => $comment->created_at->format('Y-m-d H:i'),
                ];
            }),
            'generated_at' => now()->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Calculate pass rate.
     */
    private function calculatePassRate(): float
    {
        $totalCompleted = Inspection::whereIn('status', ['completed', 'passed', 'failed'])->count();
        $totalPassed = Inspection::where('status', 'passed')->count();
        
        return $totalCompleted > 0 ? ($totalPassed / $totalCompleted) * 100 : 100;
    }

    /**
     * Calculate completion rate.
     */
    private function calculateCompletionRate(): float
    {
        $totalScheduled = Inspection::count();
        $totalCompleted = Inspection::whereIn('status', ['completed', 'passed', 'failed'])->count();
        
        return $totalScheduled > 0 ? ($totalCompleted / $totalScheduled) * 100 : 100;
    }
}
