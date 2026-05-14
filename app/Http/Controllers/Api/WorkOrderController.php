<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\Asset;
use App\Services\WorkOrderService;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use Illuminate\Http\JsonResponse;

class WorkOrderController extends Controller
{
    public function __construct(private WorkOrderService $workOrderService) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['status', 'type', 'asset_id', 'per_page']);
        $workOrders = WorkOrder::query();

        if (auth()->user()?->organization_id) {
            $workOrders->whereHas('asset', fn ($q) => $q->where('organization_id', auth()->user()->organization_id));
        }

        if (isset($filters['status'])) {
            $workOrders->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $workOrders->where('type', $filters['type']);
        }

        if (isset($filters['asset_id'])) {
            $workOrders->where('asset_id', $filters['asset_id']);
        }

        $result = $workOrders->with(['asset', 'assignedTo', 'spareParts'])
            ->orderBy('scheduled_date', 'asc')
            ->paginate($filters['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        $workOrder = $this->workOrderService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Work order created successfully',
            'data' => $workOrder->load(['asset', 'assignedTo']),
        ], 201);
    }

    public function show(WorkOrder $workOrder): JsonResponse
    {
        $workOrder->load(['asset', 'assignedTo', 'spareParts', 'maintenanceRecords']);

        return response()->json([
            'success' => true,
            'data' => $workOrder,
        ]);
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        $updated = $this->workOrderService->update($workOrder, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Work order updated successfully',
            'data' => $updated->load(['asset', 'assignedTo']),
        ]);
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        $workOrder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work order deleted successfully',
        ]);
    }

    public function changeStatus(WorkOrder $workOrder): JsonResponse
    {
        request()->validate(['status' => 'required|in:Open,In Progress,On Hold,Completed,Cancelled']);

        $updated = $this->workOrderService->changeStatus($workOrder, request('status'));

        return response()->json([
            'success' => true,
            'message' => 'Work order status updated successfully',
            'data' => $updated,
        ]);
    }

    public function addParts(WorkOrder $workOrder): JsonResponse
    {
        request()->validate([
            'parts' => 'required|array',
            'parts.*.spare_part_id' => 'required|uuid|exists:spare_parts,id',
            'parts.*.quantity_used' => 'required|integer|min:1',
            'parts.*.unit_cost' => 'required|numeric|min:0',
        ]);

        foreach (request('parts') as $part) {
            $workOrder->spareParts()->attach($part['spare_part_id'], [
                'quantity_used' => $part['quantity_used'],
                'unit_cost' => $part['unit_cost'],
                'total_cost' => $part['quantity_used'] * $part['unit_cost'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parts added to work order',
            'data' => $workOrder->load(['spareParts']),
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = $this->workOrderService->getWorkOrderStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
