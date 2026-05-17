<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MobileApiController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DepreciationController;
use App\Http\Controllers\IoTController;
use App\Http\Controllers\PredictiveMaintenanceController;
use App\Http\Controllers\AdvancedAnalyticsController;
use App\Http\Controllers\ApiDocumentationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    
    // User profile routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/auth/tokens', [AuthController::class, 'tokens']);
    Route::delete('/auth/tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);

    // User management routes (admin/manager only)
    Route::apiResource('users', UserController::class);
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::get('/users/statistics', [UserController::class, 'statistics']);
    Route::get('/users/role/{role}', [UserController::class, 'byRole']);

    // Asset Management Routes (with permission checks)
    Route::apiResource('assets', AssetController::class);
    Route::get('assets/statistics', [AssetController::class, 'statistics']);
    Route::get('assets/status/{status}', [AssetController::class, 'byStatus']);
    
    // Advanced Search Routes
    Route::get('assets/search', [AssetController::class, 'search']);
    Route::get('assets/suggestions', [AssetController::class, 'searchSuggestions']);
    Route::get('assets/popular-searches', [AssetController::class, 'popularSearches']);
    Route::get('assets/search-filters', [AssetController::class, 'searchFilters']);

    // Category Management Routes (with permission checks)
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/active', [CategoryController::class, 'active']);
    Route::get('categories/root', [CategoryController::class, 'root']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);
    Route::get('categories/statistics', [CategoryController::class, 'statistics']);
    Route::get('categories/{category}/assets', [CategoryController::class, 'assets']);
    Route::get('categories/{category}/maintenance-schedule', [CategoryController::class, 'maintenanceSchedule']);
    Route::post('categories/bulk-update', [CategoryController::class, 'bulkUpdate']);
    Route::post('categories/{category}/duplicate', [CategoryController::class, 'duplicate']);
    Route::get('categories/export', [CategoryController::class, 'export']);

    // Location Management Routes (with permission checks)
    Route::apiResource('locations', LocationController::class);
    Route::get('locations/active', [LocationController::class, 'active']);
    Route::get('locations/root', [LocationController::class, 'root']);

    // Department Management Routes (with permission checks)
    Route::apiResource('departments', DepartmentController::class);
    Route::get('departments/active', [DepartmentController::class, 'active']);

    // Work Order Management Routes (with permission checks)
    Route::apiResource('work-orders', WorkOrderController::class);
    Route::get('work-orders/statistics', [WorkOrderController::class, 'statistics']);
    Route::get('work-orders/status/{status}', [WorkOrderController::class, 'byStatus']);
    Route::get('work-orders/my', [WorkOrderController::class, 'myWorkOrders']);
    Route::post('work-orders/{workOrder}/comments', [WorkOrderController::class, 'addComment']);
    Route::get('work-orders/calendar', [WorkOrderController::class, 'calendar']);

    // Maintenance Scheduling Routes (with permission checks)
    Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class);
    Route::get('maintenance-schedules/statistics', [MaintenanceScheduleController::class, 'statistics']);
    Route::post('maintenance-schedules/{schedule}/create-work-order', [MaintenanceScheduleController::class, 'createWorkOrder']);
    Route::post('maintenance-schedules/{schedule}/mark-performed', [MaintenanceScheduleController::class, 'markAsPerformed']);
    Route::get('maintenance-schedules/{schedule}/history', [MaintenanceScheduleController::class, 'history']);
    Route::get('maintenance-schedules/calendar', [MaintenanceScheduleController::class, 'calendar']);
    Route::post('maintenance-schedules/automated-scheduling', [MaintenanceScheduleController::class, 'runAutomatedScheduling']);

    // Mobile API Routes (with permission checks)
    Route::get('mobile/dashboard', [MobileApiController::class, 'dashboard']);
    Route::get('mobile/work-orders', [MobileApiController::class, 'workOrders']);
    Route::get('mobile/work-orders/{workOrder}', [MobileApiController::class, 'workOrderDetails']);
    Route::put('mobile/work-orders/{workOrder}/status', [MobileApiController::class, 'updateWorkOrderStatus']);
    Route::post('mobile/work-orders/{workOrder}/time-entry', [MobileApiController::class, 'addTimeEntry']);
    Route::post('mobile/work-orders/{workOrder}/comments', [MobileApiController::class, 'addComment']);
    Route::post('mobile/work-orders/{workOrder}/attachments', [MobileApiController::class, 'uploadAttachment']);
    Route::get('mobile/assets', [MobileApiController::class, 'assets']);
    Route::get('mobile/assets/{asset}', [MobileApiController::class, 'assetDetails']);
    Route::get('mobile/maintenance-schedules', [MobileApiController::class, 'maintenanceSchedules']);
    Route::get('mobile/calendar', [MobileApiController::class, 'calendar']);
    Route::get('mobile/search', [MobileApiController::class, 'search']);
    Route::get('mobile/profile', [MobileApiController::class, 'profile']);
    Route::put('mobile/profile', [MobileApiController::class, 'updateProfile']);
    Route::get('mobile/notifications', [MobileApiController::class, 'notifications']);
    Route::get('mobile/statistics', [MobileApiController::class, 'statistics']);

    // Inspection Management Routes (with permission checks)
    Route::apiResource('inspections', InspectionController::class);
    Route::get('inspections/statistics', [InspectionController::class, 'statistics']);
    Route::post('inspections/{inspection}/start', [InspectionController::class, 'startInspection']);
    Route::post('inspections/{inspection}/complete', [InspectionController::class, 'completeInspection']);
    Route::post('inspections/{inspection}/comments', [InspectionController::class, 'addComment']);
    Route::post('inspections/{inspection}/attachments', [InspectionController::class, 'uploadAttachment']);
    Route::get('inspections/{inspection}/history', [InspectionController::class, 'history']);
    Route::get('inspections/calendar', [InspectionController::class, 'calendar']);
    Route::get('inspections/{inspection}/report', [InspectionController::class, 'generateReport']);

    // Inventory Management Routes (with permission checks)
    Route::get('inventory/parts', [InventoryController::class, 'parts']);
    Route::post('inventory/parts', [InventoryController::class, 'storePart']);
    Route::get('inventory/parts/{part}', [InventoryController::class, 'showPart']);
    Route::put('inventory/parts/{part}', [InventoryController::class, 'updatePart']);
    Route::delete('inventory/parts/{part}', [InventoryController::class, 'destroyPart']);
    Route::get('inventory/transactions', [InventoryController::class, 'transactions']);
    Route::post('inventory/transactions', [InventoryController::class, 'createTransaction']);
    Route::get('inventory/purchase-orders', [InventoryController::class, 'purchaseOrders']);
    Route::post('inventory/purchase-orders', [InventoryController::class, 'createPurchaseOrder']);
    Route::get('inventory/statistics', [InventoryController::class, 'statistics']);
    Route::get('inventory/stock-forecast', [InventoryController::class, 'stockForecast']);
    Route::get('inventory/low-stock-alerts', [InventoryController::class, 'lowStockAlerts']);

    // Depreciation Management Routes (with permission checks)
    Route::get('depreciation', [DepreciationController::class, 'index']);
    Route::post('depreciation', [DepreciationController::class, 'store']);
    Route::get('depreciation/{depreciation}', [DepreciationController::class, 'show']);
    Route::put('depreciation/{depreciation}', [DepreciationController::class, 'update']);
    Route::delete('depreciation/{depreciation}', [DepreciationController::class, 'destroy']);
    Route::get('depreciation/entries', [DepreciationController::class, 'entries']);
    Route::post('depreciation/entries', [DepreciationController::class, 'createEntry']);
    Route::post('depreciation/process-monthly', [DepreciationController::class, 'processMonthlyDepreciation']);
    Route::post('depreciation/assets/{asset}/process', [DepreciationController::class, 'processAssetDepreciation']);
    Route::get('depreciation/methods', [DepreciationController::class, 'methods']);
    Route::get('depreciation/statistics', [DepreciationController::class, 'statistics']);
    Route::get('depreciation/report', [DepreciationController::class, 'report']);

    // IoT Sensor Integration Routes (with permission checks)
    Route::get('iot/sensors', [IoTController::class, 'sensors']);
    Route::post('iot/sensors', [IoTController::class, 'storeSensor']);
    Route::get('iot/sensors/{sensor}', [IoTController::class, 'showSensor']);
    Route::put('iot/sensors/{sensor}', [IoTController::class, 'updateSensor']);
    Route::delete('iot/sensors/{sensor}', [IoTController::class, 'destroySensor']);
    Route::get('iot/readings', [IoTController::class, 'readings']);
    Route::post('iot/readings', [IoTController::class, 'createReading']);
    Route::get('iot/alerts', [IoTController::class, 'alerts']);
    Route::post('iot/alerts/{alert}/acknowledge', [IoTController::class, 'acknowledgeAlert']);
    Route::post('iot/alerts/{alert}/resolve', [IoTController::class, 'resolveAlert']);
    Route::get('iot/sensor-types', [IoTController::class, 'sensorTypes']);
    Route::get('iot/calibrations', [IoTController::class, 'calibrations']);
    Route::post('iot/calibrations', [IoTController::class, 'createCalibration']);
    Route::post('iot/calibrations/{calibration}/approve', [IoTController::class, 'approveCalibration']);
    Route::post('iot/calibrations/{calibration}/reject', [IoTController::class, 'rejectCalibration']);
    Route::get('iot/statistics', [IoTController::class, 'statistics']);
    Route::get('iot/analytics', [IoTController::class, 'analytics']);
    Route::get('iot/health-report', [IoTController::class, 'healthReport']);
    Route::post('iot/process-data', [IoTController::class, 'processSensorData']);

    // Predictive Maintenance Routes (with permission checks)
    Route::get('predictive/models', [PredictiveMaintenanceController::class, 'models']);
    Route::post('predictive/models', [PredictiveMaintenanceController::class, 'storeModel']);
    Route::get('predictive/models/{model}', [PredictiveMaintenanceController::class, 'showModel']);
    Route::put('predictive/models/{model}', [PredictiveMaintenanceController::class, 'updateModel']);
    Route::delete('predictive/models/{model}', [PredictiveMaintenanceController::class, 'destroyModel']);
    Route::post('predictive/models/{model}/train', [PredictiveMaintenanceController::class, 'trainModel']);
    Route::post('predictive/generate-predictions', [PredictiveMaintenanceController::class, 'generatePredictions']);
    Route::get('predictive/predictions', [PredictiveMaintenanceController::class, 'predictions']);
    Route::get('predictive/predictions/{prediction}', [PredictiveMaintenanceController::class, 'showPrediction']);
    Route::post('predictive/predictions/{prediction}/validate', [PredictiveMaintenanceController::class, 'validatePrediction']);
    Route::get('predictive/recommendations', [PredictiveMaintenanceController::class, 'recommendations']);
    Route::get('predictive/recommendations/{recommendation}', [PredictiveMaintenanceController::class, 'showRecommendation']);
    Route::post('predictive/recommendations/{recommendation}/approve', [PredictiveMaintenanceController::class, 'approveRecommendation']);
    Route::post('predictive/recommendations/{recommendation}/reject', [PredictiveMaintenanceController::class, 'rejectRecommendation']);
    Route::post('predictive/recommendations/{recommendation}/complete', [PredictiveMaintenanceController::class, 'completeRecommendation']);
    Route::post('predictive/recommendations/{recommendation}/create-work-order', [PredictiveMaintenanceController::class, 'createWorkOrder']);
    Route::get('predictive/training-histories', [PredictiveMaintenanceController::class, 'trainingHistories']);
    Route::get('predictive/model-performances', [PredictiveMaintenanceController::class, 'modelPerformances']);
    Route::post('predictive/models/{model}/evaluate', [PredictiveMaintenanceController::class, 'evaluateModel']);
    Route::post('predictive/models/{model}/detect-drift', [PredictiveMaintenanceController::class, 'detectModelDrift']);
    Route::post('predictive/generate-schedule', [PredictiveMaintenanceController::class, 'generateSchedule']);
    Route::get('predictive/statistics', [PredictiveMaintenanceController::class, 'statistics']);
    Route::get('predictive/dashboard', [PredictiveMaintenanceController::class, 'dashboard']);

    // Advanced Analytics Routes (with permission checks)
    Route::get('analytics/dashboard', [AdvancedAnalyticsController::class, 'dashboard']);
    Route::get('analytics/overview', [AdvancedAnalyticsController::class, 'overview']);
    Route::get('analytics/assets', [AdvancedAnalyticsController::class, 'assetAnalytics']);
    Route::get('analytics/maintenance', [AdvancedAnalyticsController::class, 'maintenanceAnalytics']);
    Route::get('analytics/financial', [AdvancedAnalyticsController::class, 'financialAnalytics']);
    Route::get('analytics/operational', [AdvancedAnalyticsController::class, 'operationalAnalytics']);
    Route::get('analytics/iot', [AdvancedAnalyticsController::class, 'iotAnalytics']);
    Route::get('analytics/predictive', [AdvancedAnalyticsController::class, 'predictiveAnalytics']);
    Route::get('analytics/performance', [AdvancedAnalyticsController::class, 'performanceAnalytics']);
    Route::get('analytics/trends', [AdvancedAnalyticsController::class, 'trendAnalytics']);
    Route::get('analytics/alerts', [AdvancedAnalyticsController::class, 'alertAnalytics']);
    Route::get('analytics/real-time', [AdvancedAnalyticsController::class, 'realTimeAnalytics']);
    Route::get('analytics/comparative', [AdvancedAnalyticsController::class, 'comparativeAnalytics']);
    Route::post('analytics/custom-report', [AdvancedAnalyticsController::class, 'customReport']);
    Route::post('analytics/export', [AdvancedAnalyticsController::class, 'exportData']);
    Route::get('analytics/insights', [AdvancedAnalyticsController::class, 'insights']);
    Route::get('analytics/settings', [AdvancedAnalyticsController::class, 'settings']);
    Route::post('analytics/clear-cache', [AdvancedAnalyticsController::class, 'clearCache']);

    // Reporting Routes (with permission checks)
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/asset-value', [ReportController::class, 'assetValueReport']);
    Route::get('reports/lifecycle', [ReportController::class, 'assetLifecycleReport']);
    Route::get('reports/utilization', [ReportController::class, 'utilizationReport']);
    Route::post('reports/export', [ReportController::class, 'exportReport']);
    Route::get('reports/custom-builder', [ReportController::class, 'customReportBuilder']);
    Route::post('reports/custom', [ReportController::class, 'generateCustomReport']);

    // API Documentation Routes (public and protected)
    Route::get('docs', [ApiDocumentationController::class, 'documentation']);
    Route::get('docs/endpoints', [ApiDocumentationController::class, 'endpointsByTag']);
    Route::get('docs/endpoint', [ApiDocumentationController::class, 'endpointDetail']);
    Route::get('docs/schemas', [ApiDocumentationController::class, 'schemas']);
    Route::get('docs/examples', [ApiDocumentationController::class, 'examples']);
    Route::get('docs/errors', [ApiDocumentationController::class, 'errorCodes']);
    Route::get('docs/rate-limits', [ApiDocumentationController::class, 'rateLimits']);
    Route::get('docs/versioning', [ApiDocumentationController::class, 'versioning']);
    Route::get('docs/changelog', [ApiDocumentationController::class, 'changelog']);
    Route::get('docs/search', [ApiDocumentationController::class, 'search']);
    Route::get('docs/statistics', [ApiDocumentationController::class, 'statistics']);
    Route::get('docs/health', [ApiDocumentationController::class, 'health']);
    Route::post('docs/export', [ApiDocumentationController::class, 'export']);

    // API Testing Routes (with permission checks)
    Route::post('docs/tests/run', [ApiDocumentationController::class, 'runTests']);
    Route::get('docs/tests/results', [ApiDocumentationController::class, 'testResults']);
    Route::post('docs/tests/report', [ApiDocumentationController::class, 'testReport']);
});

// Role-based routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin-only routes
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:admin,manager'])->group(function () {
    // Admin and Manager routes
    Route::post('/assets', [AssetController::class, 'store']);
    Route::put('/assets/{asset}', [AssetController::class, 'update']);
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:admin,manager,technician'])->group(function () {
    // Asset creation and update for technicians
    Route::post('/assets', [AssetController::class, 'store']);
    Route::put('/assets/{asset}', [AssetController::class, 'update']);
});

// Permission-based routes
Route::middleware(['auth:sanctum', 'permission:create_asset'])->group(function () {
    Route::post('/assets', [AssetController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'permission:edit_asset'])->group(function () {
    Route::put('/assets/{asset}', [AssetController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'permission:delete_asset'])->group(function () {
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'permission:manage_categories'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'permission:manage_users'])->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
