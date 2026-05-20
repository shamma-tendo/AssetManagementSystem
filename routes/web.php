<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\Web\AssetWebController;
use App\Http\Controllers\Web\ExecutiveOverviewController;
use App\Http\Controllers\Web\ExecutiveDashboardController;
use App\Http\Controllers\Web\StaffDashboardController;
use App\Http\Controllers\Web\HouseholdDashboardController;
use App\Http\Controllers\Web\InventoryWebController;
use App\Http\Controllers\Web\WelcomeController;
use App\Http\Controllers\Web\AssetWorkflowController;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
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
    
    // Company auth flow (Register New or Join Existing)
    Route::get('/company/auth-choice', [TenantLoginController::class, 'showCompanyAuthChoice'])->name('company.auth-choice');
    Route::get('/company/register', function () {
        return view('auth.company-register');
    })->name('company.register');
    Route::post('/company/register', [\App\Http\Controllers\Auth\CompanyOnboardingController::class, 'register'])->name('company.register.submit');
    Route::get('/company/join', function () {
        return view('auth.company-join');
    })->name('company.join');
    Route::post('/company/join', [\App\Http\Controllers\Auth\CompanyOnboardingController::class, 'join'])->name('company.join.submit');
    
    // Household auth flow (Sign Up or Sign In)
    Route::get('/household/auth-choice', [\App\Http\Controllers\Auth\HouseholdRegistrationController::class, 'showAuthChoice'])->name('household.auth-choice');
    Route::get('/household/register', [\App\Http\Controllers\Auth\HouseholdRegistrationController::class, 'showRegistrationForm'])->name('household.register');
    Route::post('/household/register', [\App\Http\Controllers\Auth\HouseholdRegistrationController::class, 'register']);
    
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

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    // Executive Dashboard
    Route::prefix('executive')->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('executive.dashboard');
        Route::get('/approvals', [ExecutiveDashboardController::class, 'approvalQueue'])->name('executive.approvals');
    });

    // CEO User Approvals (for pending team members)
    Route::prefix('admin')->middleware('auth')->group(function () {
        Route::get('/approvals', [\App\Http\Controllers\Web\UserApprovalController::class, 'index'])->name('admin.approvals');
        Route::post('/approvals/{user}/approve', [\App\Http\Controllers\Web\UserApprovalController::class, 'approve'])->name('admin.approvals.approve');
        Route::post('/approvals/{user}/reject', [\App\Http\Controllers\Web\UserApprovalController::class, 'reject'])->name('admin.approvals.reject');
        
        // Aliases for member-queue blade template
        Route::post('/approvals/{user}/approve-alias', [\App\Http\Controllers\Web\UserApprovalController::class, 'approve'])->name('executive.members.approve');
        Route::post('/approvals/{user}/reject-alias', [\App\Http\Controllers\Web\UserApprovalController::class, 'reject'])->name('executive.members.reject');
    });

    // Staff Dashboard
    Route::prefix('staff')->group(function () {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
        Route::get('/assets/{assetId}', [StaffDashboardController::class, 'viewAsset'])->name('staff.asset.view');
        Route::get('/assets/{assetId}/report', [StaffDashboardController::class, 'reportAssetStatus'])->name('staff.asset.report');
        Route::post('/assets/{assetId}/report', [StaffDashboardController::class, 'submitReport'])->name('staff.asset.report.submit');
        Route::get('/request-asset', [AssetWorkflowController::class, 'staffRequestForm'])->middleware('role:staff')->name('staff.request.form');
        Route::post('/request-asset', [AssetWorkflowController::class, 'staffSubmitRequest'])->middleware('role:staff')->name('staff.request.submit');
        Route::get('/my-requests', [AssetWorkflowController::class, 'staffMyRequests'])->middleware('role:staff')->name('staff.requests');
        Route::get('/asset-registry', [AssetWorkflowController::class, 'staffAssetRegistry'])->middleware('role:staff')->name('staff.asset-registry');
        Route::get('/leave', [AssetWorkflowController::class, 'staffLeaveForm'])->middleware('role:staff')->name('staff.leave');
        Route::post('/leave', [AssetWorkflowController::class, 'staffSubmitLeave'])->middleware('role:staff')->name('staff.leave.submit');
        Route::get('/report', [AssetWorkflowController::class, 'staffReportForm'])->middleware('role:staff')->name('staff.report');
        Route::post('/report', [AssetWorkflowController::class, 'staffSubmitAssetReport'])->middleware('role:staff')->name('staff.report.submit');
    });

    // CEO Asset Workflow — executive only
    Route::prefix('ceo')->middleware('role:executive')->group(function () {
        Route::get('/inventory', [AssetWorkflowController::class, 'ceoInventory'])->name('ceo.inventory');
        Route::post('/inventory', [AssetWorkflowController::class, 'ceoStoreAsset'])->name('ceo.inventory.store');
        Route::get('/requests', [AssetWorkflowController::class, 'ceoRequests'])->name('ceo.requests');
        Route::post('/requests/{assetRequest}/approve', [AssetWorkflowController::class, 'ceoApprove'])->name('ceo.requests.approve');
        Route::post('/requests/{assetRequest}/reject', [AssetWorkflowController::class, 'ceoReject'])->name('ceo.requests.reject');
        Route::get('/reports', [AssetWorkflowController::class, 'ceoReports'])->name('ceo.reports');
        Route::post('/reports/{report}/reviewed', [AssetWorkflowController::class, 'ceoMarkReviewed'])->name('ceo.reports.reviewed');
        Route::get('/leave', [AssetWorkflowController::class, 'ceoLeaveRequests'])->name('ceo.leave');
        Route::post('/leave/{leaveRequest}/approve', [AssetWorkflowController::class, 'ceoApproveLeave'])->name('ceo.leave.approve');
        Route::post('/leave/{leaveRequest}/reject', [AssetWorkflowController::class, 'ceoRejectLeave'])->name('ceo.leave.reject');
    });

    // Household Dashboard
    Route::prefix('household')->group(function () {
        Route::get('/dashboard', [HouseholdDashboardController::class, 'index'])->name('household.dashboard');
        Route::get('/assets/create', [HouseholdDashboardController::class, 'createAsset'])->name('household.assets.create');
        Route::post('/assets', [HouseholdDashboardController::class, 'storeAsset'])->name('household.assets.store');
        Route::get('/assets/{assetId}', [HouseholdDashboardController::class, 'viewAsset'])->name('household.assets.view');
        Route::get('/insurance', [HouseholdDashboardController::class, 'insurance'])->name('household.insurance');
        Route::get('/reminders', [HouseholdDashboardController::class, 'reminders'])->name('household.reminders');
        Route::post('/reminders', [HouseholdDashboardController::class, 'storeReminder'])->name('household.reminders.store');
        Route::delete('/reminders/{reminder}', [HouseholdDashboardController::class, 'deleteReminder'])->name('household.reminders.delete');
        Route::get('/photos', [HouseholdDashboardController::class, 'photos'])->name('household.photos');
        Route::post('/photos', [HouseholdDashboardController::class, 'storePhoto'])->name('household.photos.store');
        Route::delete('/photos/{document}', [HouseholdDashboardController::class, 'deletePhoto'])->name('household.photos.delete');
    });

   
    Route::get('/assets', function () {
        return view('assets.index');
    })->name('assets.index');

    Route::get('/assets/create', [AssetWebController::class, 'create'])->name('assets.create');
    Route::post('/assets', [AssetWebController::class, 'store'])->name('assets.store');
    Route::get('/assets/{asset}', [AssetWebController::class, 'show'])->name('assets.show');
    Route::get('/assets/{asset}/edit', [AssetWebController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}', [AssetWebController::class, 'update'])->name('assets.update');

    Route::get('/inventory', function () {
        return view('inventory.index');
    })->name('inventory.index');

    Route::get('/spare-parts/create', [InventoryWebController::class, 'create'])->name('spare-parts.create');
    Route::post('/spare-parts', [InventoryWebController::class, 'store'])->name('spare-parts.store');

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

Route::get('pending-approval', function () {
    return view('auth.pending-approval');
})->name('pending-approval');

// End of routes file
