<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\DashboardController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('assets/stats', [AssetController::class, 'stats']);
    Route::apiResource('assets', AssetController::class);
    Route::post('assets/{asset}/change-status', [AssetController::class, 'changeStatus']);

    Route::get('spare-parts/stats', [InventoryController::class, 'stats']);
    Route::get('spare-parts/low-stock', [InventoryController::class, 'lowStock']);
    Route::apiResource('spare-parts', InventoryController::class);
    Route::post('spare-parts/{sparePart}/add-stock', [InventoryController::class, 'addStock']);
    Route::post('spare-parts/{sparePart}/remove-stock', [InventoryController::class, 'removeStock']);

    Route::post('financial/depreciation/{asset}', [FinancialController::class, 'calculateDepreciation']);
    Route::get('financial/tco/{asset}', [FinancialController::class, 'totalCostOfOwnership']);
    Route::get('financial/depreciation-trend/{asset}', [FinancialController::class, 'depreciationTrend']);
    Route::get('financial/portfolio-value', [FinancialController::class, 'assetPortfolioValue']);
});
