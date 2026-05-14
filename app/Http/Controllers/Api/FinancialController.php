<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\DepreciationService;
use Illuminate\Http\JsonResponse;

class FinancialController extends Controller
{
    public function __construct(private DepreciationService $depreciationService) {}

    public function calculateDepreciation(Asset $asset): JsonResponse
    {
        $method = request('method', 'straight_line');
        $value = $this->depreciationService->calculate($asset, $method);

        return response()->json([
            'success' => true,
            'method' => $method,
            'book_value' => $value,
            'asset' => $asset->refresh(),
        ]);
    }

    public function totalCostOfOwnership(Asset $asset): JsonResponse
    {
        $tco = $this->depreciationService->calculateTotalCostOfOwnership($asset);

        return response()->json([
            'success' => true,
            'data' => $tco,
        ]);
    }

    public function depreciationTrend(Asset $asset): JsonResponse
    {
        $years = request('years', 5);
        $trend = $this->depreciationService->getAssetDepreciationTrend($asset, $years);

        return response()->json([
            'success' => true,
            'data' => $trend,
        ]);
    }

    public function assetPortfolioValue(): JsonResponse
    {
        $portfolioValue = Asset::sum('current_value');
        $originalValue = Asset::sum('purchase_cost');
        $depreciation = $originalValue - $portfolioValue;

        return response()->json([
            'success' => true,
            'data' => [
                'original_value' => $originalValue,
                'current_value' => $portfolioValue,
                'total_depreciation' => $depreciation,
                'depreciation_percentage' => $originalValue > 0 ? ($depreciation / $originalValue) * 100 : 0,
            ],
        ]);
    }
}
