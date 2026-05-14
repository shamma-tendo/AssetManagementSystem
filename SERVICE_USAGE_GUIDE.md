# API & Service Usage Guide

## Quick Start Examples

### Company Workflow Example

```php
<?php
// Create an asset request
use App\Services\AssetRequestService;

$assetRequestService = app(AssetRequestService::class);

$request = $assetRequestService->createRequest(
    organizationId: $organization->id,
    requestedBy: $assetManager,
    title: 'Laptops for Q2 2026',
    description: 'Need 5 MacBook Pro laptops for development team',
    quantity: 5,
    assetType: 'laptop',
    estimatedCost: 15000
);
// Automatically creates alert for CEO/CFO
```

```php
// CEO/CFO Approves Request
$approvedRequest = $assetRequestService->approveRequest(
    request: $request,
    approver: $cfo,
    notes: 'Approved for Q2 budget. Please order ASAP.'
);

// Gets pending requests for review
$pendingRequests = $assetRequestService->getPendingRequests(
    organizationId: $organization->id
);
```

```php
// Assign asset to employee
use App\Services\AssetAssignmentService;

$assignmentService = app(AssetAssignmentService::class);

$assignment = $assignmentService->assignToEmployee(
    assetId: $laptop->id,
    organizationId: $organization->id,
    employeeId: $employee->id,
    assignedBy: $manager,
    notes: 'Production laptop - handle with care'
);
// Automatically notifies employee
```

```php
// Employee Acknowledges Receipt
$assignment->confirmReceipt();
// Updates status to 'in_use'
```

```php
// Employee Reports Asset Condition
$report = $assignmentService->reportCondition(
    assignment: $assignment,
    employee: $employee,
    condition: 'broken',  // or: in_use, needs_repair, stolen, lost, not_effective
    description: 'Screen malfunction - won\'t turn on',
    actionRequired: 'Send to repair center'
);
// Creates CRITICAL alert if urgent
```

```php
// Manager Acknowledges Report
$report->reviewReport(
    reviewer: $manager,
    notes: 'Escalating to IT department for repair'
);
```

---

### Household Workflow Example

```php
<?php
use App\Services\HouseholdAssetService;

$householdService = app(HouseholdAssetService::class);

// Add Insurance for valuable asset
$policy = $householdService->createInsurancePolicy(
    organizationId: $household->id,
    assetId: $diamond->id,
    policyNumber: 'POL-2026-001',
    provider: 'SafeGuard Insurance',
    coverageAmount: 50000,
    startDate: now(),
    endDate: now()->addYear(),
    premiumAmount: 500,
    premiumFrequency: 'annual',
    coverageDetails: 'Full coverage including theft and damage'
);
// Alerts if expiring soon (< 30 days)
```

```php
// Schedule Maintenance
$maintenance = $householdService->scheduleMaintenance(
    organizationId: $household->id,
    assetId: $car->id,
    serviceType: 'Oil Change',
    nextServiceDate: now()->addMonths(3),
    intervalDays: 90,  // Repeat every 90 days
    serviceProvider: 'John\'s Automotive',
    estimatedCost: 75
);

// Get Due Maintenance Items
$dueSoon = $householdService->getDueMaintenanceItems(
    organizationId: $household->id,
    daysAhead: 7  // Due in next 7 days
);
```

```php
// Loan Asset to Friend
$loan = $householdService->loanAsset(
    organizationId: $household->id,
    assetId: $camera->id,
    borrowedBy: 'John Smith',
    relationship: 'Friend',
    dueBackAt: now()->addDays(7),
    borrowedByContact: '555-1234',
    conditionAtLoan: 'Good - fully charged'
);

// Get overdue loans
$overdue = $householdService->getOverdueLoans(
    organizationId: $household->id
);

// Record return
$returnedLoan = $householdService->returnLoan(
    loan: $loan,
    conditionAtReturn: 'Good condition',
    notes: 'Thanks for loaning!'
);
```

```php
// Upload Document (Receipt/Warranty/Photo)
$document = $householdService->uploadDocument(
    organizationId: $household->id,
    assetId: $tv->id,
    documentType: 'receipt',  // or: warranty, photo, certificate, manual
    filePath: 'assets/documents/tv-receipt.pdf',
    fileName: 'tv-receipt.pdf',
    fileSize: '2.5MB',
    uploadedBy: $owner,
    documentDate: now()->subDays(30),
    notes: 'Receipt from Best Buy'
);

// Get documents by type
$receipts = $householdService->getAssetDocumentsByType(
    assetId: $tv->id,
    documentType: 'receipt'
);
```

```php
// Add Warranty
$warranty = $householdService->addWarranty(
    organizationId: $household->id,
    assetId: $laptop->id,
    warrantyType: 'AppleCare+',
    startDate: now(),
    endDate: now()->addYears(3),
    coverageDetails: 'Accidental damage coverage included',
    providerName: 'Apple Inc.',
    providerContact: '1-800-MY-APPLE'
);

// File warranty claim
$warranty->claimWarranty('Screen replacement needed due to accident');
```

---

### Alert Management Example

```php
<?php
use App\Services\AlertService;

$alertService = app(AlertService::class);

// Get active alerts
$alerts = $alertService->getActiveAlerts(
    organizationId: $organization->id,
    limit: 50
);

// Get critical alerts only
$critical = $alertService->getCriticalAlerts(
    organizationId: $organization->id
);

// Get alerts for specific user (respects preferences)
$userAlerts = $alertService->getUserAlerts(
    user: $user,
    organizationId: $organization->id,
    limit: 20
);

// Resolve an alert
$alertService->resolveAlert(
    alert: $alert,
    notes: 'Issue resolved - laptop sent to repair'
);

// Get alert statistics
$stats = $alertService->getAlertStats(
    organizationId: $organization->id
);
// Returns: total_unresolved, critical_count, high_count, medium_count, low_count

// Set user alert preferences
$alertService->setAlertPreferences(
    user: $user,
    organizationId: $organization->id,
    preferences: [
        'email_alerts' => true,
        'push_notifications' => true,
        'maintenance_alerts' => true,
        'asset_overdue_alerts' => true,
        'high_value_alerts' => true,
        'damage_alerts' => true,
        'daily_digest' => false,
    ]
);
```

---

### Metrics & Analytics Example

```php
<?php
use App\Services\MetricsService;

$metricsService = app(MetricsService::class);

// Calculate metrics for today
$metrics = $metricsService->calculateMetrics(
    organizationId: $organization->id
);

// Get latest metrics
$latest = $metricsService->getLatestMetrics(
    organizationId: $organization->id
);

// Get health score (0-100)
$healthScore = $metricsService->getHealthScore(
    organizationId: $organization->id
);

// Get summary for dashboard
$summary = $metricsService->getSummary(
    organizationId: $organization->id
);
// Returns: health_score, utilization_rate, loss_rate, total_assets, assets_in_use, unused_assets, 
//          damaged_assets, stolen_assets, total_asset_value, depreciation, cost_per_asset, etc.

// Get trend comparison
$trend = $metricsService->calculateTrend(
    organizationId: $organization->id,
    daysBack: 30  // Compare last 30 days to previous 30 days
);
// Returns: current_utilization, previous_utilization, trend_percentage, is_improving

// Get metrics for date range
$period = $metricsService->getMetricsForDateRange(
    organizationId: $organization->id,
    startDate: now()->subDays(90),
    endDate: now()
);

// Get period comparison (this month vs last month)
$comparison = $metricsService->getComparison(
    organizationId: $organization->id
);
// Returns: utilization_change, loss_rate_change, asset_count_change, value_change
```

---

### Barcode/QR Code Example

```php
<?php
use App\Services\BarcodeService;

$barcodeService = app(BarcodeService::class);

// Generate barcode for new asset
$barcode = $barcodeService->generateBarcode();
// Returns: 'AST-ABC123XYZ'

// Process a barcode scan
$scan = $barcodeService->processScan(
    organizationId: $organization->id,
    barcodeValue: 'AST-ABC123XYZ',
    scanType: 'checkin',  // or: checkout, verification, inventory_count, assignment
    scannedBy: $employee,
    location: 'Building A, Floor 3',
    deviceInfo: 'iPhone 14 Pro',
    notes: 'Checked in after meeting'
);

// Get scan history for asset
$history = $barcodeService->getScanHistory(
    assetId: $asset->id,
    limit: 50
);

// Get last check status (in or out?)
$status = $barcodeService->getAssetCheckStatus(
    assetId: $asset->id
);
// Returns: status, last_scan_at, location, scanned_by, scan_type

// Verify asset ownership by barcode
$isOwned = $barcodeService->verifyAssetOwnership(
    organizationId: $organization->id,
    barcodeValue: 'AST-ABC123XYZ'
);

// Batch verify multiple assets
$results = $barcodeService->batchVerifyAssets(
    organizationId: $organization->id,
    barcodes: ['AST-001', 'AST-002', 'AST-003']
);
// Returns: found, not_found, unauthorized
```

---

## Model Relationships

### Organization
```php
$org->users();              // All users
$org->assets();             // All assets
$org->assetRequests();      // All requests
$org->assetAssignments();   // All assignments
$org->conditionReports();   // All reports
$org->insurancePolicies();  // All policies
$org->assetLoans();         // All loans
$org->alerts();             // All alerts
$org->metrics();            // All metrics
```

### AssetRequest
```php
$request->organization();
$request->requestedBy();    // Asset Manager (User)
$request->approvedBy();     // CEO/CFO (User)

// Methods:
$request->approve($cfo, 'notes');
$request->reject($cfo, 'notes');
$request->isApproved();
$request->isPending();
```

### AssetAssignment
```php
$assignment->asset();
$assignment->organization();
$assignment->assignedTo();     // Employee (User)
$assignment->assignedBy();     // Manager (User)
$assignment->conditionReports();

// Methods:
$assignment->confirmReceipt();
$assignment->markAsReturned();
$assignment->markAsLost();
$assignment->markAsDamaged();
```

### AssetConditionReport
```php
$report->assetAssignment();
$report->organization();
$report->reportedBy();          // Employee (User)
$report->reviewedBy();          // Manager (User)

// Methods:
$report->reviewReport($reviewer, 'notes');
$report->requiresAction();      // bool
```

---

## Enums & Constants

### Status Values

**AssetAssignment Status:**
- `assigned` - Initial state
- `in_use` - Employee acknowledged
- `returned` - Returned to manager
- `lost` - Missing/lost
- `damaged` - Damaged/broken

**AssetConditionReport Condition:**
- `in_use` - Working normally
- `broken` - Not functional
- `needs_repair` - Functional but needs service
- `stolen` - Stolen/missing
- `lost` - Lost by employee
- `not_effective` - Not suitable for work
- `ready_for_return` - Ready to return

**AssetRequest Status:**
- `pending` - Awaiting approval
- `approved` - Approved by executive
- `rejected` - Rejected by executive
- `fulfilled` - Assets received

**AssetLoan Status:**
- `active` - Currently loaned
- `returned` - Returned by borrower
- `lost` - Lost by borrower
- `damaged` - Damaged while loaned

**Alert Severity:**
- `low` - Informational
- `medium` - Needs attention
- `high` - Urgent
- `critical` - Requires immediate action

**Alert Types:**
- `asset_request_pending`
- `asset_request_approved`
- `asset_request_rejected`
- `asset_assigned`
- `asset_acknowledged`
- `asset_damaged`
- `asset_stolen`
- `asset_returned`
- `maintenance_due`
- `insurance_expiring`
- `warranty_expiring`
- And more...

---

## Common Queries

```php
// Get all pending approvals for CEO
$pending = AssetRequest::where('organization_id', $org->id)
    ->where('status', 'pending')
    ->with('requestedBy')
    ->get();

// Get all active assignments for employee
$assignments = AssetAssignment::where('assigned_to', $employee->id)
    ->where('status', '!=', 'returned')
    ->with('asset')
    ->get();

// Get overdue maintenance
$overdue = MaintenanceSchedule::where('organization_id', $org->id)
    ->whereDate('next_service_date', '<', now())
    ->get();

// Get critical alerts
$critical = Alert::where('organization_id', $org->id)
    ->where('is_resolved', false)
    ->where('severity', 'critical')
    ->orderBy('created_at', 'desc')
    ->get();

// Get asset scan history
$scans = AssetScan::where('asset_id', $asset->id)
    ->with('scannedBy')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();
```

---

## Error Handling

```php
try {
    $request = $assetRequestService->approveRequest($request, $user);
} catch (\Exception $e) {
    // "User is not authorized to approve requests"
    Log::error('Approval failed: ' . $e->getMessage());
}

try {
    $assignment = $assignmentService->confirmReceipt($assignment, $wrongEmployee);
} catch (\Exception $e) {
    // "Unauthorized action"
    Log::error('Receipt confirmation failed: ' . $e->getMessage());
}
```

---

For more details, see `PLATFORM_ARCHITECTURE.md`
