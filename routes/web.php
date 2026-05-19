<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AssetRegistryController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Public routes ──────────────────────────────────────────────────────
Route::get('/',       [LoginController::class, 'showLanding'])->name('landing');
Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');
Route::get('/register',  [LoginController::class, 'showRegister'])->name('register');
Route::post('/register', [LoginController::class, 'register'])->name('register.post');

// ── Protected routes (require authentication) ───────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard',         [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/realtime',[DashboardController::class, 'getRealTimeData'])->name('dashboard.realtime');
    Route::post('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

    // Asset Registry
    Route::get('/asset-registry',                        [AssetRegistryController::class, 'index'])->name('asset-registry');
    Route::get('/asset-registry/{assetId}/details',      [AssetRegistryController::class, 'getAssetDetails'])->name('asset-registry.details');
    Route::post('/asset-registry/store',                 [AssetRegistryController::class, 'store'])->name('asset-registry.store');
    Route::match(['get','post'],'/asset-registry/export',[AssetRegistryController::class, 'exportAssets'])->name('asset-registry.export');

    // Maintenance
    Route::get('/maintenance',                        [MaintenanceController::class, 'index'])->name('maintenance');
    Route::get('/maintenance/work-orders/create',     [MaintenanceController::class, 'create'])->name('maintenance.work-orders.create');
    Route::get('/maintenance/{taskId}/details',       [MaintenanceController::class, 'getTaskDetails'])->name('maintenance.details');
    Route::put('/maintenance/{taskId}/status',        [MaintenanceController::class, 'updateTaskStatus'])->name('maintenance.update-status');
    Route::post('/maintenance/work-order',            [MaintenanceController::class, 'createWorkOrder'])->name('maintenance.create-work-order');
    Route::post('/maintenance/export',                [MaintenanceController::class, 'exportMaintenance'])->name('maintenance.export');

    // Inventory
    Route::get('/inventory',                   [InventoryController::class, 'index'])->name('inventory');
    Route::get('/inventory/report',            [InventoryController::class, 'generateReport'])->name('inventory.report');
    Route::get('/inventory/{itemId}/details',  [InventoryController::class, 'getItemDetails'])->name('inventory.details');
    Route::put('/inventory/{itemId}/stock',    [InventoryController::class, 'updateStock'])->name('inventory.update-stock');
    Route::post('/inventory/item',             [InventoryController::class, 'createItem'])->name('inventory.create-item');
    Route::post('/inventory/export',           [InventoryController::class, 'exportInventory'])->name('inventory.export');

    // Analytics
    Route::get('/analytics',         [AnalyticsController::class, 'index'])->name('analytics');
    Route::post('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // Global search + notifications (navbar)
    Route::get('/search',               function (\Illuminate\Http\Request $request) {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);
        $results = \App\Models\Asset::with('category')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('serial_number', 'like', "%{$q}%")
                      ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$q}%"));
            })
            ->limit(8)->get()
            ->map(fn($a) => ['id' => $a->serial_number, 'uuid' => $a->id, 'name' => $a->name, 'category' => $a->category?->name ?? 'Uncategorized']);
        return response()->json($results);
    })->name('search');

    Route::get('/notifications/recent', function () {
        $items = \App\Models\WorkOrder::with('asset')->latest()->limit(6)->get()
            ->map(function ($wo) {
                $sv = $wo->status instanceof \BackedEnum ? $wo->status->value : (string) $wo->status;
                return ['id' => $wo->id, 'message' => $wo->title, 'type' => $sv, 'asset' => $wo->asset?->name ?? 'Unknown', 'time' => $wo->created_at->diffForHumans()];
            });
        return response()->json($items);
    })->name('notifications.recent');

});

