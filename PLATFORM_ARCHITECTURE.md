# Multi-Tenant Asset Management Platform - Implementation Guide

## System Overview

Your Asset Management System now supports two distinct operational modes:

### 1. **Company/Organization Mode**
For businesses managing corporate assets with multiple stakeholders:
- **Asset Manager**: Requests assets, distributes to staff, tracks usage
- **CEO/CFO**: Approves requests, views all activities, makes strategic decisions
- **Employees**: Receive assignments, report asset status
- **Reports**: Comprehensive analytics on utilization, losses, ROI

### 2. **Household/Personal Mode**
For individuals tracking personal belongings:
- **Simple Interface**: Clean, intuitive dashboard
- **Insurance Tracking**: Manage policies and claims
- **Loan History**: Track items loaned to friends/family
- **Maintenance Reminders**: Know when service is due
- **Document Storage**: Receipts, warranties, photos

---

## Database Schema

### Core Tables

#### `organizations`
- Identifies tenant (company or household)
- Stores subscription level (basic, pro, enterprise)
- Multi-tenant segregation

#### Company-Specific Tables
- **asset_requests**: Requests from managers to executives
- **asset_assignments**: Track asset distribution to employees
- **asset_condition_reports**: Employee reports on asset status
- **inventory_snapshots**: Historical tracking of asset distribution

#### Household-Specific Tables
- **insurance_policies**: Manage insurance for valuables
- **asset_loans**: Track items loaned to others
- **maintenance_schedules**: Service reminders and history
- **asset_documents**: Storage for receipts, warranties, photos
- **asset_warranties**: Warranty tracking and claims

#### Cross-Tenant Features
- **alerts**: Notifications for all events
- **alert_preferences**: User notification preferences
- **asset_metrics**: KPI dashboards (utilization, loss rate, etc.)
- **asset_scans**: QR/Barcode scan history

---

## Workflow: Company Mode

### Asset Request Workflow
```
Asset Manager → Creates Request
    ↓
CEO/CFO → Reviews & Approves/Rejects
    ↓
Asset Manager → Receives Approved Assets
    ↓
Asset Manager → Assigns to Employees
    ↓
Employees → Receive & Acknowledge
    ↓
Employees → Report Status (In Use / Broken / Stolen / etc.)
    ↓
Manager & Executives → View Activities & Analytics
```

### Key Models & Services

**AssetRequest Model**
```php
$request = AssetRequest::create([
    'organization_id' => $org->id,
    'requested_by' => $manager->id,
    'title' => 'Laptops Q1 2026',
    'quantity' => 10,
    'asset_type' => 'laptop',
    'estimated_cost' => 15000,
]);

// Approve
$request->approve($cfo, 'Approved for Q1 budget');

// Reject
$request->reject($cfo, 'Budget constraints');
```

**AssetAssignment Model**
```php
$assignment = AssetAssignment::create([
    'asset_id' => $asset->id,
    'organization_id' => $org->id,
    'assigned_to' => $employee->id,
    'assigned_by' => $manager->id,
    'quantity' => 1,
    'status' => 'assigned',
]);

// Employee acknowledges
$assignment->confirmReceipt();

// Employee reports issue
$report = AssetConditionReport::create([
    'asset_assignment_id' => $assignment->id,
    'reported_by' => $employee->id,
    'condition' => 'broken',
    'description' => 'Screen cracked after drop',
    'requires_urgent_attention' => true,
]);
```

---

## Workflow: Household Mode

### Personal Asset Tracking
```
Add Asset → Set Insurance → Schedule Maintenance → Store Documents
    ↓
Loan to Friend → Track Loan → Get Returned → Update Status
    ↓
View Dashboard → See All Assets → Upcoming Maintenance
```

**InsurancePolicy Model**
```php
$policy = InsurancePolicy::create([
    'organization_id' => $org->id,
    'asset_id' => $asset->id,
    'policy_number' => 'POL-12345',
    'provider' => 'SafeGuard Insurance',
    'coverage_amount' => 50000,
    'start_date' => now(),
    'end_date' => now()->addYear(),
    'premium_amount' => 250,
]);

// Check expiration
if ($policy->isExpired()) {
    // Renew
}
```

**AssetLoan Model**
```php
$loan = AssetLoan::create([
    'organization_id' => $org->id,
    'asset_id' => $asset->id,
    'borrowed_by' => 'John Smith',
    'relationship' => 'Friend',
    'loaned_at' => now(),
    'due_back_at' => now()->addDays(7),
]);

// Check if overdue
if ($loan->isOverdue()) {
    // Send reminder (daysOverdue: $loan->daysOverdue())
}

// Return with condition
$loan->returnAsset('Good condition');
```

---

## Key Features

### 1. Barcode/QR Code Scanning
```php
// Service records each scan
$scan = AssetScan::create([
    'organization_id' => $org->id,
    'asset_id' => $asset->id,
    'scanned_by' => $user->id,
    'barcode_value' => '123456789',
    'scan_type' => 'checkin', // or checkout, verification, inventory_count
    'location' => 'Building A, Floor 2',
]);
```

### 2. Smart Alerts System
Auto-generated alerts for:
- Asset requests pending approval
- Assets assigned to employees
- Damaged/stolen assets reported
- Maintenance due dates
- Insurance expiring soon
- Warranty claims available
- Overdue asset returns
- High-value items moved

```php
Alert::create([
    'organization_id' => $org->id,
    'asset_id' => $asset->id,
    'alert_type' => 'maintenance_due',
    'title' => 'Maintenance Overdue',
    'message' => 'Vehicle #VH-001 maintenance is 5 days overdue',
    'severity' => 'high', // low, medium, high, critical
]);
```

### 3. Health Metrics Dashboard
Displays real-time KPIs:
- **Utilization Rate**: % of assets in active use
- **Loss Rate**: % of assets lost/stolen
- **Cost Metrics**: TCO, depreciation, maintenance costs
- **Maintenance Backlog**: # of overdue maintenance items
- **Asset Health Score**: 0-100 health indicator

```php
// Auto-calculated health score
$health_score = $metrics->health_score; // Considers utilization, losses, maintenance
```

### 4. Activity Audit Trail
All actions tracked for compliance:
- Who assigned assets to whom
- When employees acknowledged receipt
- All condition reports filed
- Request approvals/rejections
- Maintenance performed
- Insurance claims filed

---

## User Roles & Permissions

### Company Mode Roles
- **Admin**: Full system access
- **CEO/CFO**: Approve requests, view executive dashboards
- **Asset Manager**: Create requests, assign assets, manage inventory
- **Employee**: Receive assets, report conditions
- **Technician**: Perform maintenance, record work orders
- **Auditor**: View-only access to all data

### Household Mode
- **Owner**: Full control of all assets and data
- **Family Member**: Optional read-only or limited access

---

## Login Flow

1. **Tenant Type Selection** (`/select-tenant-type`)
   - User chooses "Company" or "Household"
   - Selection stored in session

2. **Login** (`/login`)
   - Standard email/password authentication
   - User filtered by organization type

3. **Dashboard** (`/dashboard`)
   - Role-based interface customized per mode

---

## API Endpoints (Coming Soon)

```
# Company Mode
POST   /api/asset-requests              # Create request
GET    /api/asset-requests              # List requests
POST   /api/asset-requests/:id/approve  # Approve request
POST   /api/asset-requests/:id/reject   # Reject request

GET    /api/assignments                 # Get my assignments
POST   /api/assignments/:id/acknowledge # Acknowledge receipt
POST   /api/assignments/:id/report      # Report condition

GET    /api/alerts                      # Get alerts
PATCH  /api/alerts/:id/resolve          # Mark alert resolved

# Household Mode
GET    /api/insurance-policies          # List policies
POST   /api/insurance-policies          # Add new policy
GET    /api/asset-loans                 # List loans
POST   /api/asset-loans/:id/return      # Return loaned item

# Metrics & Analytics
GET    /api/metrics                     # KPI dashboard
GET    /api/reports/utilization        # Utilization report
GET    /api/reports/financial          # Financial report
```

---

## Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

This creates:
- Multi-tenant organization structure
- Company workflow tables
- Household feature tables
- Alert and metrics system
- Document storage tables

### 2. Seed Initial Data
```bash
php artisan db:seed
```

Creates:
- Demo organizations (company + household)
- Sample users for each role
- Sample assets and assignments
- Insurance policies and loans

### 3. Build Assets
```bash
npm run build
```

### 4. Start Server
```bash
php artisan serve
```

Then navigate to `http://localhost:8000/select-tenant-type`

---

## Testing the System

### Company Mode Test Flow
1. Go to login page, select "Company"
2. Login as Asset Manager (email: manager@company.com)
3. Create an asset request for laptops
4. Login as CEO (email: ceo@company.com)
5. Approve the request
6. Go back to manager, assign asset to employee
7. Login as employee, acknowledge receipt
8. Report asset status (working, broken, stolen, etc.)
9. Check CEO dashboard for all activities

### Household Mode Test Flow
1. Go to login page, select "Household"
2. Login as homeowner (email: owner@household.com)
3. Add a valuable asset
4. Create insurance policy for the asset
5. Loan asset to friend
6. Set maintenance reminder
7. Upload receipt/warranty photo
8. View dashboard with asset inventory

---

## Advanced Features

### Multi-Organization Management
Future: A single CEO could manage multiple subsidiary companies

### Workflow Automation
Future: Automatic asset depreciation calculation, maintenance reminders

### Mobile App
Future: Native mobile app for barcode scanning and status reporting

### Integration
Future: ERP, accounting software, IoT device integration

### Custom Reports
Future: Build custom analytics dashboards per organization

---

## Support & Documentation

For detailed API documentation, see `API_QUICK_REFERENCE.md`

For troubleshooting, see `SETUP_COMPLETE.md`

---

## Security Notes

- All data segregated by organization_id
- Role-based access control enforced
- Audit trail maintained for all actions
- Passwords encrypted with Laravel Sanctum
- CSRF protection on all forms
- Input validation on all endpoints

---

## Roadmap

### Phase 1 (Current)
- ✅ Multi-tenant architecture
- ✅ Company workflows
- ✅ Household features
- ✅ Alert system
- ✅ Metrics dashboard

### Phase 2 (Next)
- Mobile-friendly reports
- Real-time notifications
- Advanced filtering
- Export capabilities

### Phase 3
- Mobile app
- IoT integration
- Predictive analytics
- Custom workflows

---

Last Updated: May 14, 2026
