<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Asset;
use App\Services\ComplianceService;
use App\Http\Requests\StoreInspectionRequest;
use App\Http\Requests\UpdateInspectionRequest;
use Illuminate\Http\JsonResponse;

class InspectionController extends Controller
{
    public function __construct(private ComplianceService $complianceService) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['status', 'type', 'asset_id', 'per_page']);
        $inspections = Inspection::query();

        if (isset($filters['status'])) {
            $inspections->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $inspections->where('inspection_type', $filters['type']);
        }

        if (isset($filters['asset_id'])) {
            $inspections->where('asset_id', $filters['asset_id']);
        }

        $result = $inspections->with(['asset', 'inspector'])
            ->orderBy('scheduled_date', 'desc')
            ->paginate($filters['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function store(StoreInspectionRequest $request): JsonResponse
    {
        $inspection = $this->complianceService->scheduleInspection($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inspection scheduled successfully',
            'data' => $inspection->load(['asset', 'inspector']),
        ], 201);
    }

    public function show(Inspection $inspection): JsonResponse
    {
        $inspection->load(['asset', 'inspector']);

        return response()->json([
            'success' => true,
            'data' => $inspection,
        ]);
    }

    public function update(UpdateInspectionRequest $request, Inspection $inspection): JsonResponse
    {
        $updated = $inspection->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inspection updated successfully',
            'data' => $inspection->fresh()->load(['asset', 'inspector']),
        ]);
    }

    public function complete(Inspection $inspection): JsonResponse
    {
        request()->validate([
            'findings' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'compliance_met' => 'required|boolean',
            'certification_number' => 'nullable|string',
            'certification_expiry' => 'nullable|date',
        ]);

        $completed = $this->complianceService->completeInspection($inspection, request()->all());

        return response()->json([
            'success' => true,
            'message' => 'Inspection completed successfully',
            'data' => $completed,
        ]);
    }

    public function upcoming(): JsonResponse
    {
        $days = request('days', 7);
        $inspections = $this->complianceService->getUpcomingInspections($days);

        return response()->json([
            'success' => true,
            'data' => $inspections,
        ]);
    }

    public function overdue(): JsonResponse
    {
        $inspections = $this->complianceService->getOverdueInspections();

        return response()->json([
            'success' => true,
            'data' => $inspections,
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = $this->complianceService->getComplianceStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
