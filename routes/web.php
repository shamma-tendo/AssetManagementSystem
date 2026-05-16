<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\Web\AssetAssignmentWebController;
use App\Http\Controllers\Web\AssetIntakeWebController;
use App\Http\Controllers\Web\AssetWebController;
use App\Http\Controllers\Web\ExecutiveOverviewController;
use App\Http\Controllers\Web\ExecutiveDashboardController;
use App\Http\Controllers\Web\AssetManagerDashboardController;
use App\Http\Controllers\Web\StaffDashboardController;
use App\Http\Controllers\Web\HouseholdDashboardController;
use App\Http\Controllers\Web\InventoryWebController;
use App\Http\Controllers\Web\OrganizationWebController;
use App\Http\Controllers\Web\ResourceRequestWebController;
use App\Http\Controllers\Web\WelcomeController;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('select-tenant-type');
});

Route::post('/welcome/context', [WelcomeController::class, 'storeContext'])->name('welcome.context');

Route::middleware('guest')->group(function () {
    // New multi-tenant login flow
    Route::get('/select-tenant-type', [TenantLoginController::class, 'selectTenantType'])->name('select-tenant-type');
    Route::post('/tenant-type', [TenantLoginController::class, 'storeTenantType'])->name('tenant.store-type');
    
    // Industry type selection (only for companies)
    Route::get('/select-industry-type', [TenantLoginController::class, 'selectIndustryType'])->name('select-industry-type');
    Route::post('/industry-type', [TenantLoginController::class, 'storeIndustryType'])->name('industry.store-type');
    
    // Legacy routes (kept for backward compatibility)
    Route::get('/welcome', [WelcomeController::class, 'show'])->name('welcome');
    Route::get('/login', [TenantLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TenantLoginController::class, 'login']);
});

Route::post('/logout', [TenantLoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    // Smart dashboard routing based on user role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        if (!$user->organization) {
            return view('dashboard');
        }

        // Route to appropriate dashboard based on role
        if ($user->organization->isHousehold()) {
            return redirect()->route('household.dashboard');
        }

        if ($user->isExecutive()) {
            return redirect()->route('executive.dashboard');
        }

        if ($user->isAssetManager()) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    // Executive Dashboard
    Route::prefix('executive')->middleware('can:view-executive-dashboard')->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('executive.dashboard');
        Route::get('/approvals', [ExecutiveDashboardController::class, 'approvalQueue'])->name('executive.approvals');
    });

    // Asset Manager Dashboard
    Route::prefix('manager')->middleware('can:view-manager-dashboard')->group(function () {
        Route::get('/dashboard', [AssetManagerDashboardController::class, 'index'])->name('manager.dashboard');
        Route::get('/requests/create', [AssetManagerDashboardController::class, 'createRequest'])->name('manager.requests.create');
        Route::get('/distribute', [AssetManagerDashboardController::class, 'distributeAssets'])->name('manager.distribute');
    });

    // Staff Dashboard
    Route::prefix('staff')->middleware('can:view-staff-dashboard')->group(function () {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
        Route::get('/assets/{assetId}', [StaffDashboardController::class, 'viewAsset'])->name('staff.asset.view');
        Route::get('/assets/{assetId}/report', [StaffDashboardController::class, 'reportAssetStatus'])->name('staff.asset.report');
    });

    // Household Dashboard
    Route::prefix('household')->middleware('can:view-household-dashboard')->group(function () {
        Route::get('/dashboard', [HouseholdDashboardController::class, 'index'])->name('household.dashboard');
        Route::get('/assets/create', [HouseholdDashboardController::class, 'createAsset'])->name('household.assets.create');
        Route::get('/assets/{assetId}', [HouseholdDashboardController::class, 'viewAsset'])->name('household.assets.view');
        Route::get('/insurance', [HouseholdDashboardController::class, 'insurance'])->name('household.insurance');
    });

   
    Route::get('workspace/pulse', [ExecutiveOverviewController::class, 'index']);
    Route::get('/resource-requests', [ResourceRequestWebController::class, 'index'])->name('resource-requests.index');
    Route::get('/resource-requests/create', [ResourceRequestWebController::class, 'create'])->name('resource-requests.create');
    Route::post('/resource-requests', [ResourceRequestWebController::class, 'store'])->name('resource-requests.store');
    Route::get('/resource-requests/{resourceRequest}', [ResourceRequestWebController::class, 'show'])->name('resource-requests.show');
    Route::post('/resource-requests/{resourceRequest}/decide', [ResourceRequestWebController::class, 'decide'])->name('resource-requests.decide');

    Route::get('/asset-intakes', [AssetIntakeWebController::class, 'index'])->name('asset-intakes.index');
    Route::get('/asset-intakes/create', [AssetIntakeWebController::class, 'create'])->name('asset-intakes.create');
    Route::post('/asset-intakes', [AssetIntakeWebController::class, 'store'])->name('asset-intakes.store');

    Route::get('/my-assignments', [AssetAssignmentWebController::class, 'mine'])->name('assignments.mine');
    Route::get('/assets/{asset}/assign', [AssetAssignmentWebController::class, 'create'])->name('assets.assignments.create');
    Route::post('/assets/{asset}/assignments', [AssetAssignmentWebController::class, 'store'])->name('assets.assignments.store');
    Route::post('/assignments/{assignment}/acknowledge', [AssetAssignmentWebController::class, 'acknowledge'])->name('assignments.acknowledge');
    Route::post('/assignments/{assignment}/report', [AssetAssignmentWebController::class, 'report'])->name('assignments.report');

    Route::get('/settings/organization', [OrganizationWebController::class, 'edit'])->name('settings.organization');
    Route::put('/settings/organization', [OrganizationWebController::class, 'update'])->name('settings.organization.update');

    Route::get('/assets', function () {
        return view('assets.index');
    })->name('assets.index');

    Route::get('/assets/create', [AssetWebController::class, 'create'])->name('assets.create');
    Route::post('/assets', [AssetWebController::class, 'store'])->name('assets.store');
    Route::get('/assets/{asset}', [AssetWebController::class, 'show'])->name('assets.show');
    Route::get('/assets/{asset}/edit', [AssetWebController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}', [AssetWebController::class, 'update'])->name('assets.update');

    Route::get('/work-orders', function () {
        return view('work-orders.index');
    })->name('work-orders.index');

    Route::get('/work-orders/{workOrder}', function (WorkOrder $workOrder) {
        return view('work-orders.show', [
            'workOrder' => $workOrder->load(['asset', 'assignedTo', 'spareParts', 'maintenanceRecords']),
        ]);
    })->name('work-orders.show');

    Route::get('/inventory', function () {
        return view('inventory.index');
    })->name('inventory.index');

    Route::get('/spare-parts/create', [InventoryWebController::class, 'create'])->name('spare-parts.create');
    Route::post('/spare-parts', [InventoryWebController::class, 'store'])->name('spare-parts.store');

    Route::get('/inspections', function () {
        return view('inspections.index');
    })->name('inspections.index');

    Route::get('/reports/financial', function () {
        return view('reports.financial');
    })->name('reports.financial');

    Route::get('/reports/analytics', function () {
        return view('reports.analytics');
    })->name('reports.analytics');

    Route::get('/settings/categories', function () {
        return view('settings.categories', ['categories' => Category::orderBy('name')->get()]);
    })->name('settings.categories');

    Route::get('/settings/locations', function () {
        return view('settings.locations', ['locations' => Location::orderBy('name')->get()]);
    })->name('settings.locations');

    Route::get('/settings/audit-log', function () {
        $orgId = auth()->user()?->organization_id;
        $logs = ActivityLog::query()
            ->when($orgId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('organization_id', $orgId)))
            ->with('user')
            ->latest()
            ->limit(100)
            ->get();

        return view('settings.audit-log', ['logs' => $logs]);
    })->name('settings.audit-log');
});
