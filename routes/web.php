<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Dashboard routes
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/realtime', [DashboardController::class, 'getRealTimeData'])->name('dashboard.realtime');
Route::post('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

// Asset Registry routes
Route::get('/asset-registry', [AssetRegistryController::class, 'index'])->name('asset-registry');
Route::get('/asset-registry/{assetId}/details', [AssetRegistryController::class, 'getAssetDetails'])->name('asset-registry.details');
Route::post('/asset-registry/store', [AssetRegistryController::class, 'store'])->name('asset-registry.store');
Route::match(['get', 'post'], '/asset-registry/export', [AssetRegistryController::class, 'exportAssets'])->name('asset-registry.export');

// Maintenance routes
Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance');
Route::get('/maintenance/{taskId}/details', [MaintenanceController::class, 'getTaskDetails'])->name('maintenance.details');
Route::put('/maintenance/{taskId}/status', [MaintenanceController::class, 'updateTaskStatus'])->name('maintenance.update-status');
Route::post('/maintenance/work-order', [MaintenanceController::class, 'createWorkOrder'])->name('maintenance.create-work-order');
Route::post('/maintenance/export', [MaintenanceController::class, 'exportMaintenance'])->name('maintenance.export');

// Inventory routes
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
Route::get('/inventory/{itemId}/details', [InventoryController::class, 'getItemDetails'])->name('inventory.details');
Route::put('/inventory/{itemId}/stock', [InventoryController::class, 'updateStock'])->name('inventory.update-stock');
Route::post('/inventory/item', [InventoryController::class, 'createItem'])->name('inventory.create-item');
Route::post('/inventory/export', [InventoryController::class, 'exportInventory'])->name('inventory.export');
Route::get('/inventory/report', [InventoryController::class, 'generateReport'])->name('inventory.report');

// Analytics routes
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
Route::post('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

// Settings routes
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// Welcome route (fallback)
Route::get('/welcome', function () {
    return view('welcome');
});
