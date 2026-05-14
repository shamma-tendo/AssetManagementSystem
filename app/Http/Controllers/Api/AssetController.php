<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AssetService;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['category_id', 'location_id', 'status', 'department_id', 'search', 'per_page']);
        $assets = $this->assetService->getAllAssets($filters);

        return response()->json([
            'success' => true,
            'data' => $assets,
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = $this->assetService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => $asset->load(['category', 'location', 'department']),
        ], 201);
    }

    public function show(Asset $asset): JsonResponse
    {
        $asset->load([
            'category', 'location', 'department',
            'workOrders', 'maintenanceRecords', 'inspections',
            'depreciationRecords', 'iotReadings'
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $updated = $this->assetService->update($asset, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => $updated->load(['category', 'location', 'department']),
        ]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully',
        ]);
    }

    public function changeStatus(Asset $asset): JsonResponse
    {
        request()->validate(['status' => 'required|in:Ordered,Received,Active,Under Maintenance,Retired,Disposed']);

        $updated = $this->assetService->changeStatus($asset, request('status'));

        return response()->json([
            'success' => true,
            'message' => 'Asset status updated successfully',
            'data' => $updated,
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = $this->assetService->getAssetStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
