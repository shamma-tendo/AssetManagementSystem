# Business Rules and Constraints
## Asset & Equipment Management System (AEMS)

**Document Version:** 1.0  
**Date:** May 2026  
**Prepared by:** SSEBUGWAWO ALVIN BRANDON (Requirements Engineer)  

---

## Table of Contents

1. [Asset Management Rules](#1-asset-management-rules)
2. [Lifecycle Management Rules](#2-lifecycle-management-rules)
3. [Maintenance Management Rules](#3-maintenance-management-rules)
4. [Inventory Management Rules](#4-inventory-management-rules)
5. [Financial Management Rules](#5-financial-management-rules)
6. [Compliance Management Rules](#6-compliance-management-rules)
7. [User Management Rules](#7-user-management-rules)
8. [System Constraints](#8-system-constraints)
9. [Data Integrity Rules](#9-data-integrity-rules)
10. [Business Process Rules](#10-business-process-rules)

---

## 1. Asset Management Rules

### 1.1 Asset Registration Rules

| Rule ID | Rule Description | Business Logic | Exception Handling |
|---------|------------------|----------------|-------------------|
| BR-AM-001 | Every asset must have a unique identifier | System generates UUID upon registration | Manual override with administrator approval |
| BR-AM-002 | Serial numbers must be unique within asset category | Validation checks for duplicates | System allows multiple identical serial numbers with location suffix |
| BR-AM-003 | Purchase cost must be greater than zero | Validation prevents zero or negative values | Zero cost allowed for donated assets with justification |
| BR-AM-004 | Asset category must be selected from predefined list | Dropdown with managed categories | New categories require administrator approval |
| BR-AM-005 | All assets must be assigned to a location | Location field is mandatory | Temporary location "Unassigned" for new assets |

### 1.2 Asset Valuation Rules

| Rule ID | Rule Description | Business Logic | Calculation Method |
|---------|------------------|----------------|-------------------|
| BR-AM-006 | Current value cannot exceed purchase cost | Validation ensures current_value ≤ purchase_cost | Automatic depreciation calculation |
| BR-AM-007 | Salvage value cannot exceed 20% of purchase cost | Validation during asset setup | Fixed percentage or user-defined |
| BR-AM-008 | Useful life must be between 1-30 years | Range validation for useful_life_years | Default based on asset category |

### 1.3 Asset Location Rules

| Rule ID | Rule Description | Business Logic | Validation |
|---------|------------------|----------------|------------|
| BR-AM-009 | Location changes require user authentication | Session validation required | Must be logged in with appropriate permissions |
| BR-AM-010 | Assets cannot be in multiple locations simultaneously | Single location constraint | Location history maintains previous locations |
| BR-AM-011 | Location hierarchy must be maintained | Parent-child location relationships | Cannot assign to non-existent parent location |

---

## 2. Lifecycle Management Rules

### 2.1 Status Transition Rules

| Current Status | Allowed Next Status | Conditions | Business Rationale |
|----------------|-------------------|------------|-------------------|
| Ordered | Received | Physical receipt confirmed | Asset exists physically |
| Received | Active | Commissioning completed | Asset ready for use |
| Active | Under Maintenance | Work order created | Asset temporarily unavailable |
| Under Maintenance | Active | Work order completed | Asset returned to service |
| Active | Retired | End-of-life assessment | Asset no longer needed |
| Retired | Disposed | Disposal process completed | Asset physically removed |

### 2.2 Status Change Validation Rules

| Rule ID | Rule Description | Validation Logic | Business Impact |
|---------|------------------|------------------|-----------------|
| BR-LC-001 | Cannot skip lifecycle states | Sequential state validation | Prevents data inconsistencies |
| BR-LC-002 | Status changes require audit trail | Automatic logging | Ensures accountability |
| BR-LC-003 | Certain status changes require approval | Workflow integration | Prevents unauthorized changes |
| BR-LC-004 | Assets under maintenance cannot be reassigned | Status check before assignment | Prevents conflicts |

---

## 3. Maintenance Management Rules

### 3.1 Preventive Maintenance Rules

| Rule ID | Rule Description | Scheduling Logic | Frequency Rules |
|---------|------------------|------------------|-----------------|
| BR-MM-001 | PM schedules based on asset criticality | Critical assets: monthly, Normal: quarterly | Adjustable by asset type |
| BR-MM-002 | PM work orders auto-generated 7 days before due | Scheduler runs daily | Advanced notification |
| BR-MM-003 | PM cannot be scheduled while asset under maintenance | Status check | Prevents conflicts |
| BR-MM-004 | PM overdue triggers escalation alerts | Status monitoring | Management notification |

### 3.2 Work Order Rules

| Rule ID | Rule Description | Business Logic | Constraints |
|---------|------------------|----------------|-------------|
| BR-MM-005 | Work orders must be assigned to active users | User status validation | Cannot assign to inactive users |
| BR-MM-006 | Work order priority affects scheduling | Priority queue system | Emergency work orders jump queue |
| BR-MM-007 | Work order costs cannot exceed budget limits | Budget validation | Requires approval for overages |
| BR-MM-008 | Completed work orders cannot be modified | Status lock | Requires administrator override |

### 3.3 Parts Usage Rules

| Rule ID | Rule Description | Inventory Logic | Validation |
|---------|------------------|-----------------|-----------|
| BR-MM-009 | Parts cannot be used if stock is zero | Stock level check | Prevents negative inventory |
| BR-MM-010 | Parts cost automatically added to work order | Cost calculation | Real-time cost tracking |
| BR-MM-011 | Critical parts require manager approval | Approval workflow | Prevents stockouts |

---

## 4. Inventory Management Rules

### 4.1 Stock Management Rules

| Rule ID | Rule Description | Reorder Logic | Alert Rules |
|---------|------------------|---------------|-------------|
| BR-IN-001 | Reorder point calculated as 2x average monthly usage | Historical usage analysis | Dynamic adjustment |
| BR-IN-002 | Safety stock maintained at 25% of reorder point | Safety stock calculation | Prevents stockouts |
| BR-IN-003 | Expired parts automatically flagged | Expiration date tracking | Removal from available stock |
| BR-IN-004 | Stock adjustments require audit trail | Change logging | Prevents theft/loss |

### 4.2 Supplier Management Rules

| Rule ID | Rule Description | Supplier Logic | Validation |
|---------|------------------|----------------|------------|
| BR-IN-005 | Preferred suppliers get first consideration | Supplier ranking | Cost and delivery performance |
| BR-IN-006 | Minimum order quantities enforced | Order validation | Prevents uneconomical orders |
| BR-IN-007 | Supplier performance tracked quarterly | Performance metrics | Affects preferred status |

---

## 5. Financial Management Rules

### 5.1 Depreciation Rules

| Rule ID | Rule Description | Calculation Method | Frequency |
|---------|------------------|-------------------|-----------|
| BR-FI-001 | Straight-line depreciation: (Cost - Salvage) / Useful Life | Annual calculation | Monthly posting |
| BR-FI-002 | Declining balance: 2x straight-line rate | Accelerated method | Monthly posting |
| BR-FI-003 | Depreciation stops when book value equals salvage value | Minimum value check | Monthly validation |
| BR-FI-004 | Fully depreciated assets flagged for review | Status change | Annual review |

### 5.2 Cost Tracking Rules

| Rule ID | Rule Description | Cost Logic | Reporting |
|---------|------------------|------------|-----------|
| BR-FI-005 | Total Cost of Ownership includes all costs | Cost aggregation | Real-time calculation |
| BR-FI-006 | Maintenance costs tracked by asset and category | Cost categorization | Monthly reports |
| BR-FI-007 | Capital expenditures separated from operating costs | Cost classification | Financial reporting |

---

## 6. Compliance Management Rules

### 6.1 Inspection Rules

| Rule ID | Rule Description | Scheduling Logic | Compliance Rules |
|---------|------------------|------------------|-----------------|
| BR-CO-001 | Inspections scheduled based on regulatory requirements | Compliance calendar | Automatic scheduling |
| BR-CO-002 | Failed inspections trigger corrective action plans | CAP generation | Mandatory follow-up |
| BR-CO-003 | Inspection certificates must be uploaded | Document validation | Digital signature required |
| BR-CO-004 | Overdue inspections trigger management alerts | Escalation rules | Daily notifications |

### 6.2 Certification Rules

| Rule ID | Rule Description | Certification Logic | Validity Rules |
|---------|------------------|---------------------|----------------|
| BR-CO-005 | Certifications have expiration dates | Expiration tracking | 90-day advance warning |
| BR-CO-006 | Expired certifications prevent asset operation | Operational constraint | System enforcement |
| BR-CO-007 | Certification renewal requires inspection completion | Workflow dependency | Sequential process |

---

## 7. User Management Rules

### 7.1 Access Control Rules

| Rule ID | Rule Description | Permission Logic | Security Rules |
|---------|------------------|------------------|----------------|
| BR-US-001 | Users can only have one active role | Role assignment | Single role constraint |
| BR-US-002 | Inactive users cannot access system | Account status | Automatic session termination |
| BR-US-003 | Password changes every 90 days | Password policy | Forced password reset |
| BR-US-004 | Failed login attempts trigger account lockout | Security policy | 5 attempts = 30-minute lock |

### 7.2 Data Access Rules

| Rule ID | Rule Description | Access Logic | Privacy Rules |
|---------|------------------|--------------|---------------|
| BR-US-005 | Users can only view assets in their assigned locations | Location-based access | Data segregation |
| BR-US-006 | Financial data restricted to finance role | Role-based access | Confidentiality protection |
| BR-US-007 | Audit logs only accessible to administrators | Privileged access | Security logging |

---

## 8. System Constraints

### 8.1 Technical Constraints

| Constraint | Description | Impact | Mitigation |
|------------|-------------|--------|------------|
| TC-001 | Maximum file upload size: 50MB | Document storage limits | Compression, external storage |
| TC-002 | Maximum concurrent users: 500 | System performance | Load balancing, scaling |
| TC-003 | Database connection limit: 100 | Resource management | Connection pooling |
| TC-004 | API rate limit: 1000 requests/minute | System protection | Rate limiting implementation |

### 8.2 Business Constraints

| Constraint | Description | Business Impact | Compliance |
|------------|-------------|-----------------|------------|
| BC-001 | Must comply with GDPR data protection | Data handling procedures | Privacy by design |
| BC-002 | Must support 7-year data retention | Storage requirements | Archival system |
| BC-003 | Must integrate with existing ERP system | Integration complexity | API development |
| BC-004 | Must support multi-language operation | Localization requirements | Translation management |

---

## 9. Data Integrity Rules

### 9.1 Validation Rules

| Rule ID | Field | Validation Rule | Error Message |
|---------|-------|------------------|---------------|
| DI-001 | Asset serial number | Unique within category | Duplicate serial number detected |
| DI-002 | Purchase cost | > 0 and < 10,000,000 | Invalid purchase amount |
| DI-003 | Email address | Valid email format | Invalid email address |
| DI-004 | Date fields | Not future date for past events | Invalid date range |
| DI-005 | Phone numbers | Valid format | Invalid phone number |

### 9.2 Referential Integrity Rules

| Rule ID | Relationship | Constraint | Action |
|---------|--------------|------------|--------|
| RI-001 | Asset-Category | Category must exist | Prevent deletion if assets assigned |
| RI-002 | Work Order-Asset | Asset must exist | Cascade delete work orders |
| RI-003 | User-Role | Role must exist | Prevent role deletion if users assigned |
| RI-004 | Part-Supplier | Supplier must exist | Set to null if supplier deleted |

### 9.3 Business Logic Validation

| Rule ID | Validation | Business Rule | Implementation |
|---------|------------|---------------|----------------|
| BL-001 | Work order completion | All required fields must be completed | Form validation |
| BL-002 | Asset disposal | All compliance requirements must be met | Checklist validation |
| BL-003 | Budget approval | Exceeds budget requires approval | Workflow integration |
| BL-004 | Asset transfer | Cannot transfer assets under maintenance | Status check |

---

## 10. Business Process Rules

### 10.1 Asset Acquisition Process

| Step | Business Rule | Responsible Party | System Action |
|------|---------------|------------------|---------------|
| 1 | Purchase order created | Procurement | Create asset with "Ordered" status |
| 2 | Asset received | Receiving | Update status to "Received" |
| 3 | Asset commissioned | Asset Manager | Update status to "Active" |
| 4 | Asset tagged | Maintenance | Assign unique identifier |

### 10.2 Maintenance Process

| Step | Business Rule | Responsible Party | System Action |
|------|---------------|------------------|---------------|
| 1 | PM schedule generated | System | Auto-create work order |
| 2 | Work order assigned | Supervisor | Notify technician |
| 3 | Work performed | Technician | Record labor/parts |
| 4 | Work completed | Technician | Update asset status |
| 5 | Quality inspection | Supervisor | Approve completion |

### 10.3 Disposal Process

| Step | Business Rule | Responsible Party | System Action |
|------|---------------|------------------|---------------|
| 1 | Disposal request initiated | Asset Manager | Create disposal record |
| 2 | Approval obtained | Management | Update status to "Retired" |
| 3 | Physical disposal | Operations | Update status to "Disposed" |
| 4 | Records archived | Administration | Mark as inactive |

---

## Rule Enforcement Matrix

| Rule Category | Enforcement Method | System Component | Validation Timing |
|---------------|-------------------|------------------|-------------------|
| Asset Registration | Form validation | AssetController | Before save |
| Status Transitions | State machine | AssetService | During update |
| Work Order Creation | Business rules | WorkOrderService | Before creation |
| Inventory Management | Stock validation | InventoryService | During transaction |
| Financial Calculations | Automated calculation | DepreciationService | Scheduled |
| Compliance Scheduling | Scheduler | ComplianceService | Daily |
| User Access | Authentication | AuthMiddleware | Each request |
| Data Integrity | Database constraints | Eloquent Models | Database level |

---

## Exception Handling Rules

| Exception Type | Handling Strategy | User Notification | System Response |
|---------------|------------------|-------------------|-----------------|
| Validation Error | Reject with message | Detailed error message | No data change |
| Business Rule Violation | Block action | Rule explanation | Transaction rollback |
| System Error | Graceful degradation | Generic error message | Error logging |
| Concurrent Update | Optimistic locking | "Record modified by another user" | Refresh required |
| Network Failure | Queue operation | "Action queued for processing" | Background processing |

---

## Business Rule Testing Requirements

### Test Categories
- **Unit Tests:** Individual rule validation
- **Integration Tests:** Rule interaction testing
- **Business Process Tests:** End-to-end workflow validation
- **Edge Case Tests:** Boundary condition testing
- **Performance Tests:** Rule execution efficiency

### Test Coverage Requirements
- 100% of business rules must have automated tests
- All exception scenarios must be tested
- Performance impact must be measured
- User acceptance testing for complex rules

---

**Document Control**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | May 2026 | SSEBUGWAWO ALVIN BRANDON | Initial business rules and constraints |
