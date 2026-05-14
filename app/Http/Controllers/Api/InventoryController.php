<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SparePart;
use App\Services\InventoryService;
use App\Http\Requests\StoreSparePartRequest;
use App\Http\Requests\UpdateSparePartRequest;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['category_id', 'location_id', 'low_stock', 'out_stock', 'search', 'per_page']);
        $parts = $this->inventoryService->getAllParts($filters);

        return response()->json([
            'success' => true,
            'data' => $parts,
        ]);
    }

    public function store(StoreSparePartRequest $request): JsonResponse
    {
        $part = $this->inventoryService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Spare part created successfully',
            'data' => $part->load(['category', 'location']),
        ], 201);
    }

    public function show(SparePart $sparePart): JsonResponse
    {
        $sparePart->load(['category', 'location', 'workOrders']);

        return response()->json([
            'success' => true,
            'data' => $sparePart,
        ]);
    }

    public function update(UpdateSparePartRequest $request, SparePart $sparePart): JsonResponse
    {
        $updated = $this->inventoryService->update($sparePart, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Spare part updated successfully',
            'data' => $updated->load(['category', 'location']),
        ]);
    }

    public function destroy(SparePart $sparePart): JsonResponse
    {
        $sparePart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Spare part deleted successfully',
        ]);
    }

    public function addStock(SparePart $sparePart): JsonResponse
    {
        request()->validate(['quantity' => 'required|integer|min:1']);

        $updated = $this->inventoryService->addStock($sparePart, request('quantity'));

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully',
            'data' => $updated,
        ]);
    }

    public function removeStock(SparePart $sparePart): JsonResponse
    {
        request()->validate(['quantity' => 'required|integer|min:1']);

        try {
            $updated = $this->inventoryService->removeStock($sparePart, request('quantity'));

            return response()->json([
                'success' => true,
                'message' => 'Stock removed successfully',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function lowStock(): JsonResponse
    {
        $parts = $this->inventoryService->getLowStockParts();

        return response()->json([
            'success' => true,
            'data' => $parts,
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = $this->inventoryService->getInventoryStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
