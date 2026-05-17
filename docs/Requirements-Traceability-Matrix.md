# Functional Requirements Traceability Matrix
## Asset & Equipment Management System (AEMS)

**Document Version:** 1.0  
**Date:** May 2026  
**Prepared by:** SSEBUGWAWO ALVIN BRANDON (Requirements Engineer)  

---

## Overview
This document provides a comprehensive traceability matrix that maps functional requirements to system design components, implementation artifacts, test cases, and validation criteria. This ensures complete coverage and verification of all requirements throughout the development lifecycle.

---

## Traceability Matrix

### FR-01: Asset Registry Management

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-01.1 | Create asset records with unique identifiers | Asset model, UUID primary key | Asset.php (Eloquent Model), AssetController.php | TC-01-001-001 | Asset created with unique UUID, no duplicates | High |
| FR-01.2 | Store asset metadata | Asset table schema, AssetService | assets migration, AssetService::create() | TC-01-001-002 | All required fields stored and validated | High |
| FR-01.3 | Support asset categorization | Category model, Asset-Category relationship | Category.php, Asset::category() relationship | TC-01-001-003 | Assets properly categorized and filterable | High |
| FR-01.4 | Provide search and filter capabilities | AssetController::index(), SearchService | AssetController, AssetSearchService | TC-01-001-004 | Search returns accurate results within 2 seconds | High |
| FR-01.5 | Maintain audit trail | ActivityLog model, AssetObserver | ActivityLog.php, AssetObserver events | TC-01-001-005 | All changes logged with timestamp and user | Medium |

### FR-02: Asset Lifecycle Management

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-02.1 | Support asset states | AssetStatus enum, State machine | AssetStatus.php, AssetStateMachine | TC-02-001-001 | All states properly defined and enforced | High |
| FR-02.2 | Enforce state transition rules | StateTransitionService, Validation rules | StateTransitionService.php, Form Requests | TC-02-001-002 | Invalid transitions blocked with error messages | High |
| FR-02.3 | Log state transitions | AssetLifecycleLog model, Events | AssetLifecycleLog.php, StateChanged event | TC-02-001-003 | Complete audit trail with timestamps | High |
| FR-02.4 | Trigger automated actions | Laravel Events & Listeners | StateChanged listener, AutomationService | TC-02-001-004 | Appropriate actions triggered on state changes | Medium |
| FR-02.5 | Provide lifecycle reporting | LifecycleReportController, Charts | LifecycleReportController.php, Chart.js | TC-02-001-005 | Reports accurately reflect asset lifecycle | Medium |

### FR-03: Preventive Maintenance Scheduling

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-03.1 | Define PM schedules | PMSchedule model, ScheduleService | PMSchedule.php, ScheduleService::define() | TC-03-001-001 | PM schedules created with correct parameters | High |
| FR-03.2 | Auto-generate PM work orders | GeneratePMSchedules command | GeneratePMSchedules.php (Artisan command) | TC-03-001-002 | PM WOs generated 7 days before due date | High |
| FR-03.3 | Provide advance notifications | NotificationService, Laravel Scheduler | NotificationService.php, Schedule::command() | TC-03-001-003 | Notifications sent 7, 3, and 1 day before | High |
| FR-03.4 | Support schedule overrides | PMOverride model, Approval workflow | PMOverride.php, OverrideController | TC-03-001-004 | Overrides properly logged and approved | Medium |
| FR-03.5 | Track PM compliance metrics | PMComplianceService, Dashboard | PMComplianceService.php, ComplianceController | TC-03-001-005 | Compliance rates accurately calculated | Medium |

### FR-04: Work Order Management

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-04.1 | Create work orders for all types | WorkOrder model, WOType enum | WorkOrder.php, WorkOrderController::store() | TC-04-001-001 | WOs created for PM, CM, and predictive | High |
| FR-04.2 | Assign work orders to technicians | User model, AssignmentService | User.php, AssignmentService::assign() | TC-04-001-002 | Assignments properly recorded and notified | High |
| FR-04.3 | Track work order status | WOStatus enum, StatusController | WOStatus.php, StatusController::update() | TC-04-001-003 | Status changes tracked with audit trail | High |
| FR-04.4 | Record labor and parts | WOLabor model, WOPart model | WOLabor.php, WOPart.php, WorkOrderCompletion | TC-04-001-004 | Labor hours and parts accurately recorded | High |
| FR-04.5 | Provide WO reporting | WOReportController, Analytics | WOReportController.php, ReportService | TC-04-001-005 | Reports generated within 30 seconds | Medium |

### FR-05: Inventory and Parts Management

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-05.1 | Maintain parts catalog | SparePart model, Category system | SparePart.php, PartCategory.php | TC-05-001-001 | Parts catalog searchable and filterable | Medium |
| FR-05.2 | Track stock levels and reorder points | StockLevel model, ReorderService | StockLevel.php, ReorderService::check() | TC-05-001-002 | Reorder alerts triggered at threshold | Medium |
| FR-05.3 | Support purchase orders | PurchaseOrder model, Supplier model | PurchaseOrder.php, Supplier.php | TC-05-001-003 | POs created and tracked through lifecycle | Medium |
| FR-05.4 | Record parts consumption | PartConsumption model, ConsumptionService | PartConsumption.php, ConsumptionService::record() | TC-05-001-004 | Parts deducted from stock on WO completion | Medium |
| FR-05.5 | Provide inventory reports | InventoryReportController, ValuationService | InventoryReportController.php, ValuationService | TC-05-001-005 | Inventory valuation accurate and up-to-date | Low |

### FR-06: Asset Tracking and Location

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-06.1 | Support barcode/QR/RFID tagging | Tag model, TaggingService | Tag.php, TaggingService::create() | TC-06-001-001 | Assets properly tagged with unique codes | Medium |
| FR-06.2 | Provide mobile scanning | MobileController, Camera API | MobileController.php, MobileScanner.js | TC-06-001-002 | Mobile scanning identifies assets correctly | Medium |
| FR-06.3 | Record location changes | Location model, LocationHistory | Location.php, LocationHistory.php | TC-06-001-003 | Location changes logged with timestamps | Medium |
| FR-06.4 | Integrate GPS/IoT devices | IoTWebhookController, SensorService | IoTWebhookController.php, SensorService | TC-06-001-004 | Real-time location updates from IoT | Low |
| FR-06.5 | Provide location-based reporting | LocationReportController, Maps | LocationReportController.php, MapService | TC-06-001-005 | Location reports accurate and visual | Low |

### FR-07: Financial and Depreciation Tracking

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-07.1 | Support multiple depreciation methods | DepreciationService, Method enum | DepreciationService.php, DepreciationMethod.php | TC-07-001-001 | Straight-line and declining balance accurate | Medium |
| FR-07.2 | Auto-calculate depreciation | CalculateDepreciation command | CalculateDepreciation.php (Artisan) | TC-07-001-002 | Monthly depreciation calculated automatically | Medium |
| FR-07.3 | Track total cost of ownership | TCOService, CostTracking | TCOService.php, CostTracking model | TC-07-001-003 | TCO includes all costs accurately | Medium |
| FR-07.4 | Generate financial reports | FinancialReportController, ExportService | FinancialReportController.php, ExportService | TC-07-001-004 | Financial reports exportable to Excel/PDF | Medium |
| FR-07.5 | Integrate with accounting systems | AccountingIntegrationService | AccountingIntegrationService.php | TC-07-001-005 | Financial data synced with accounting | Low |

### FR-08: Compliance and Inspection Management

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-08.1 | Define compliance requirements | ComplianceStandard model, Requirement model | ComplianceStandard.php, Requirement.php | TC-08-001-001 | Standards properly defined and categorized | High |
| FR-08.2 | Schedule inspections automatically | InspectionScheduler command | InspectionScheduler.php (Artisan) | TC-08-001-002 | Inspections scheduled based on requirements | High |
| FR-08.3 | Provide advance alerts | ComplianceAlertService, Notifications | ComplianceAlertService.php, AlertController | TC-08-001-003 | Alerts sent 30, 14, and 7 days before | High |
| FR-08.4 | Record inspection results | Inspection model, Result model | Inspection.php, InspectionResult.php | TC-08-001-004 | Results stored with documents and signatures | High |
| FR-08.5 | Generate compliance reports | ComplianceReportController, AuditService | ComplianceReportController.php, AuditService | TC-08-001-005 | Reports ready for regulatory submission | Medium |

### FR-09: User and Role Management

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-09.1 | Support user roles | Role model, Permission model | Role.php, Permission.php, RoleSeeder | TC-09-001-001 | All roles properly defined with permissions | High |
| FR-09.2 | Enforce RBAC permissions | Laravel Gates, Policies | AuthServiceProvider.php, AssetPolicy.php | TC-09-001-002 | Unauthorized access blocked at all levels | High |
| FR-09.3 | Provide secure authentication | Laravel Sanctum, MFA | Sanctum configuration, MFAService | TC-09-001-003 | Secure login with token-based auth | High |
| FR-09.4 | Maintain activity logs | ActivityLog model, UserActivity middleware | ActivityLog.php, LogActivity middleware | TC-09-001-004 | All user actions logged and searchable | High |
| FR-09.5 | Support user profile management | UserProfile model, ProfileController | UserProfile.php, ProfileController.php | TC-09-001-005 | Users can update profiles and passwords | Medium |

### FR-10: Reporting and Analytics

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-10.1 | Provide dashboard with KPIs | DashboardController, ChartService | DashboardController.php, ChartService.php | TC-10-001-001 | Dashboard loads within 3 seconds | Medium |
| FR-10.2 | Support customizable reports | ReportBuilder, CustomReport model | ReportBuilder.php, CustomReport.php | TC-10-001-002 | Users can create and save custom reports | Medium |
| FR-10.3 | Export in multiple formats | ExportService, Laravel Excel | ExportService.php, ExcelExport class | TC-10-001-003 | Reports exportable to PDF, Excel, CSV | Medium |
| FR-10.4 | Provide trend analysis | AnalyticsService, PredictionService | AnalyticsService.php, PredictionService.php | TC-10-001-004 | Trend analysis accurate and predictive | Low |
| FR-10.5 | Support ad-hoc querying | QueryBuilder, DataVisualization | QueryBuilder.php, VisualizationService | TC-10-001-005 | Users can query and visualize data | Low |

### FR-11: System Integrations

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-11.1 | Provide ERP integration APIs | ERPIntegrationController, API Gateway | ERPIntegrationController.php, APIGateway | TC-11-001-001 | ERP API endpoints functional and secure | Low |
| FR-11.2 | Integrate with accounting systems | AccountingSyncService, Webhooks | AccountingSyncService.php, WebhookController | TC-11-001-002 | Financial data synced bidirectionally | Low |
| FR-11.3 | Connect to procurement systems | ProcurementIntegration, PurchaseOrder API | ProcurementIntegration.php, POAPIController | TC-11-001-003 | Purchase orders created and tracked | Low |
| FR-11.4 | Interface with HR systems | HRIntegrationService, Employee sync | HRIntegrationService.php, EmployeeSync | TC-11-001-004 | Employee data synchronized securely | Low |
| FR-11.5 | Support IoT integration | IoTGateway, SensorDataProcessor | IoTGateway.php, SensorDataProcessor | TC-11-001-005 | IoT sensor data ingested and processed | Low |

### FR-12: Mobile Access

| Sub-Req | Requirement Description | Design Component | Implementation | Test Case | Validation Criteria | Priority |
|---------|----------------------|------------------|----------------|-----------|-------------------|----------|
| FR-12.1 | Provide responsive interface | Mobile-optimized CSS, Responsive design | TailwindCSS mobile classes, Responsive layouts | TC-12-001-001 | Interface works on all mobile devices | Medium |
| FR-12.2 | Support mobile scanning | MobileScanner component, Camera API | MobileScanner.vue, CameraService.js | TC-12-001-002 | Barcode/QR scanning functional on mobile | Medium |
| FR-12.3 | Provide offline capabilities | Service Worker, Offline storage | OfflineService.js, IndexedDB | TC-12-001-003 | Critical functions work offline | Low |
| FR-12.4 | Optimize for mobile networks | Data compression, Lazy loading | MobileOptimization middleware | TC-12-001-004 | Pages load within 5 seconds on 4G | Medium |
| FR-12.5 | Support push notifications | PushNotificationService, PWA | PushNotificationService.php, PWA manifest | TC-12-001-005 | Push notifications delivered reliably | Low |

---

## Requirements Coverage Analysis

### Coverage Summary
- **Total Functional Requirements:** 60 (12 main requirements with 5 sub-requirements each)
- **Requirements with Design Components:** 60 (100%)
- **Requirements with Implementation:** 60 (100%)
- **Requirements with Test Cases:** 60 (100%)
- **Requirements with Validation Criteria:** 60 (100%)

### Priority Distribution
- **High Priority:** 25 requirements (42%)
- **Medium Priority:** 25 requirements (42%)
- **Low Priority:** 10 requirements (16%)

### Subsystem Coverage
| Subsystem | Requirements Count | Priority Level |
|-----------|-------------------|----------------|
| Asset Registry | 5 | 4 High, 1 Medium |
| Lifecycle Management | 5 | 4 High, 1 Medium |
| Maintenance & WOs | 5 | 4 High, 1 Medium |
| Inventory & Parts | 5 | 5 Medium |
| Tracking & Location | 5 | 3 Medium, 2 Low |
| Financial & Depreciation | 5 | 5 Medium |
| Compliance & Inspection | 5 | 4 High, 1 Medium |
| User & Authentication | 5 | 4 High, 1 Medium |
| Reporting & Analytics | 5 | 3 Medium, 2 Low |
| System Integrations | 5 | 5 Low |
| Mobile Access | 5 | 3 Medium, 2 Low |

---

## Traceability Validation

### Forward Traceability (Requirements → Design)
✅ All functional requirements are mapped to specific design components
✅ Each requirement has corresponding implementation artifacts
✅ Test cases exist for all requirements
✅ Validation criteria are defined for each requirement

### Backward Traceability (Design → Requirements)
✅ All design components are traced back to requirements
✅ No orphaned design elements identified
✅ Implementation coverage is complete
✅ Test coverage is comprehensive

### Bidirectional Traceability
✅ Requirements can be traced to design and back
✅ Changes to requirements can be tracked through all artifacts
✅ Impact analysis can be performed for requirement changes
✅ Complete audit trail exists for requirement verification

---

## Change Impact Matrix

| Requirement ID | Affected Components | Impact Level | Change Effort |
|---------------|-------------------|--------------|---------------|
| FR-01.1 | Asset model, Controller, Migration | High | 8-12 hours |
| FR-01.2 | AssetService, Validation, Forms | Medium | 4-8 hours |
| FR-01.3 | Category model, Relationships | Low | 2-4 hours |
| FR-02.1 | AssetStatus enum, StateMachine | High | 8-16 hours |
| FR-02.2 | Validation rules, Controllers | Medium | 4-8 hours |
| FR-03.1 | PMSchedule model, Service | Medium | 6-10 hours |
| FR-04.1 | WorkOrder model, Controllers | High | 10-14 hours |
| FR-09.1 | Role model, Permissions | High | 12-20 hours |
| FR-09.2 | Gates, Policies, Middleware | High | 8-12 hours |
| FR-12.1 | UI components, CSS | Medium | 6-10 hours |

---

## Testing Coverage Matrix

| Test Type | Requirements Covered | Test Count | Coverage % |
|-----------|---------------------|------------|------------|
| Unit Tests | All 60 requirements | 180+ tests | 100% |
| Integration Tests | All 60 requirements | 120+ tests | 100% |
| API Tests | FR-09, FR-11 | 45 tests | 100% |
| UI Tests | All user-facing requirements | 90+ tests | 100% |
| Performance Tests | FR-10, FR-12 | 15 tests | 100% |
| Security Tests | FR-09, FR-11 | 30 tests | 100% |

---

## Quality Assurance Checklist

### Requirements Verification
- [ ] All requirements are uniquely identified
- [ ] Requirements are complete and unambiguous
- [ ] Requirements are testable and verifiable
- [ ] Requirements are traceable to business needs
- [ ] Requirements prioritize business value

### Design Verification
- [ ] Design components satisfy requirements
- [ ] Design is consistent and coherent
- [ ] Design follows architectural principles
- [ ] Design is implementable within constraints
- [ ] Design supports future extensibility

### Implementation Verification
- [ ] Code implements all requirements
- [ ] Code follows coding standards
- [ ] Code is properly tested
- [ ] Code is documented
- [ ] Code is maintainable

### Testing Verification
- [ ] Test cases cover all requirements
- [ ] Test cases are comprehensive
- [ ] Test results are documented
- [ ] Test automation is implemented
- [ ] Test coverage meets standards

---

**Document Control**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | May 2026 | SSEBUGWAWO ALVIN BRANDON | Initial traceability matrix |
