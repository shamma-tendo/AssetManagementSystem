# User Stories and Acceptance Criteria
## Asset & Equipment Management System (AEMS)

**Document Version:** 1.0  
**Date:** May 2026  
**Prepared by:** SSEBUGWAWO ALVIN BRANDON (Requirements Engineer)  

---

## Table of Contents

1. [Asset Registry User Stories](#1-asset-registry-user-stories)
2. [Lifecycle Management User Stories](#2-lifecycle-management-user-stories)
3. [Maintenance Management User Stories](#3-maintenance-management-user-stories)
4. [Inventory Management User Stories](#4-inventory-management-user-stories)
5. [Asset Tracking User Stories](#5-asset-tracking-user-stories)
6. [Financial Management User Stories](#6-financial-management-user-stories)
7. [Compliance Management User Stories](#7-compliance-management-user-stories)
8. [User Management User Stories](#8-user-management-user-stories)
9. [Reporting User Stories](#9-reporting-user-stories)
10. [Integration User Stories](#10-integration-user-stories)
11. [Mobile Access User Stories](#11-mobile-access-user-stories)

---

## 1. Asset Registry User Stories

### US-01-001: Asset Registration
**As an** Asset Manager  
**I want to** register new assets in the system  
**So that** I can maintain a complete inventory of organizational equipment

**Acceptance Criteria:**
- **Given** I have Asset Manager permissions
- **When** I access the asset registration form
- **Then** I can input all required asset metadata (name, serial number, make/model, category, location, purchase date, cost)
- **And** the system automatically generates a unique asset ID
- **And** the system validates all required fields before saving
- **And** the asset is saved with "Ordered" status
- **And** I receive confirmation that the asset was successfully registered
- **And** the asset appears in the asset registry within 2 seconds

### US-01-002: Asset Search and Filter
**As a** Maintenance Technician  
**I want to** search and filter assets by various criteria  
**So that** I can quickly locate specific equipment for maintenance

**Acceptance Criteria:**
- **Given** I am logged into the system
- **When** I use the search bar or filters
- **Then** I can search by asset name, serial number, category, or location
- **And** I can apply multiple filters simultaneously
- **And** search results appear within 1 second
- **And** results are sorted by relevance or selected criteria
- **And** I can export search results to Excel or CSV

### US-01-003: Asset Categorization
**As an** Asset Manager  
**I want to** categorize assets into logical groups  
**So that** I can organize and analyze assets by type

**Acceptance Criteria:**
- **Given** I have Asset Manager permissions
- **When** I create or edit an asset
- **Then** I can select from predefined categories or create new ones
- **And** I can assign subcategories for detailed classification
- **And** the system shows category statistics in reports
- **And** I can filter assets by category in searches

### US-01-004: Asset Audit Trail
**As an** Auditor  
**I want to** view the complete history of asset changes  
**So that** I can verify data integrity and compliance

**Acceptance Criteria:**
- **Given** I have Auditor permissions
- **When** I view an asset's detail page
- **Then** I can access an audit trail tab
- **And** the audit trail shows all changes with timestamps
- **And** each change includes the user who made it
- **And** I can export the audit trail for external review
- **And** the audit trail cannot be modified by any user

---

## 2. Lifecycle Management User Stories

### US-02-001: Asset Status Management
**As an** Asset Manager  
**I want to** update asset status through their lifecycle  
**So that** I can track current asset conditions and locations

**Acceptance Criteria:**
- **Given** I have Asset Manager permissions
- **When** I view an asset and select "Change Status"
- **Then** I can choose from valid status options (Ordered, Received, Active, Under Maintenance, Retired, Disposed)
- **And** the system validates status transitions based on business rules
- **And** I must provide a reason for status changes
- **And** the change is logged with timestamp and user attribution
- **And** relevant stakeholders are notified of status changes

### US-02-002: Automated State Transitions
**As a** System Administrator  
**I want to** configure automated state transitions  
**So that** asset statuses update automatically based on events

**Acceptance Criteria:**
- **Given** I have Administrator permissions
- **When** I access the lifecycle configuration
- **Then** I can define rules for automatic state transitions
- **And** I can set conditions that trigger transitions (e.g., work order completion)
- **And** the system executes transitions when conditions are met
- **And** I receive notifications when automated transitions occur

---

## 3. Maintenance Management User Stories

### US-03-001: Preventive Maintenance Scheduling
**As a** Maintenance Supervisor  
**I want to** create preventive maintenance schedules  
**So that** equipment is serviced regularly to prevent failures

**Acceptance Criteria:**
- **Given** I have Maintenance Supervisor permissions
- **When** I define a PM schedule for an asset category
- **Then** I can set time-based (e.g., every 6 months) or usage-based (e.g., every 1000 hours) schedules
- **And** I can specify the work order template to use
- **And** the system automatically generates PM work orders based on schedules
- **And** I receive notifications 7 days before PM is due
- **And** I can override or postpone individual PM tasks with justification

### US-03-002: Work Order Creation
**As a** Maintenance Technician  
**I want to** create work orders for maintenance tasks  
**So that** all maintenance activities are properly documented

**Acceptance Criteria:**
- **Given** I have Technician permissions
- **When** I create a new work order
- **Then** I can select the asset requiring maintenance
- **And** I can specify the work order type (Preventive, Corrective, Predictive)
- **And** I can describe the work required and priority level
- **And** I can assign the work order to myself or another technician
- **And** the system logs the creation with timestamp and user attribution

### US-03-003: Work Order Tracking
**As a** Maintenance Supervisor  
**I want to** track work order progress in real-time  
**So that** I can monitor maintenance team productivity and workload

**Acceptance Criteria:**
- **Given** I have Maintenance Supervisor permissions
- **When** I view the work order dashboard
- **Then** I see work orders organized by status (Open, In Progress, On Hold, Completed)
- **And** I can drag and drop work orders between status columns
- **And** each work order shows assigned technician, due date, and priority
- **And** I can filter work orders by technician, asset, or date range
- **And** I receive alerts for overdue work orders

### US-03-004: Work Order Completion
**As a** Maintenance Technician  
**I want to** record work order completion details  
**So that** maintenance history is accurately documented

**Acceptance Criteria:**
- **Given** I have a work order assigned to me
- **When** I mark the work order as completed
- **Then** I must record actual labor hours worked
- **And** I can list parts used from inventory
- **And** I can upload photos or documents related to the work
- **And** I can add notes about the work performed
- **And** the system updates asset status back to "Active"
- **And** the asset maintenance history is updated

---

## 4. Inventory Management User Stories

### US-04-001: Parts Catalog Management
**As an** Inventory Manager  
**I want to** maintain a catalog of spare parts  
**So that** technicians can quickly identify required components

**Acceptance Criteria:**
- **Given** I have Inventory Manager permissions
- **When** I add a new part to the catalog
- **Then** I can specify part number, description, manufacturer, and compatibility
- **And** I can upload specification documents and images
- **And** I can link parts to specific asset models
- **And** the system prevents duplicate part numbers
- **And** technicians can search the parts catalog by part number or description

### US-04-002: Stock Level Monitoring
**As an** Inventory Manager  
**I want to** monitor stock levels and receive reorder alerts  
**So that** I never run out of critical spare parts

**Acceptance Criteria:**
- **Given** I have Inventory Manager permissions
- **When** I view the inventory dashboard
- **Then** I see current stock levels for all parts
- **And** parts below reorder point are highlighted in red
- **And** I receive email alerts when parts reach reorder point
- **And** I can view reorder history and supplier lead times
- **And** I can generate purchase orders directly from reorder alerts

### US-04-003: Parts Consumption Tracking
**As a** Maintenance Technician  
**I want to** record parts used during maintenance  
**So that** inventory levels are automatically updated

**Acceptance Criteria:**
- **Given** I am completing a work order
- **When** I record parts used
- **Then** I can search for parts by part number or description
- **And** the system shows current stock availability
- **And** I can specify quantity used
- **And** the system deducts parts from inventory
- **And** the system updates the cost of the work order
- **And** I receive alerts if insufficient stock is available

---

## 5. Asset Tracking User Stories

### US-05-001: Asset Tagging
**As an** Asset Manager  
**I want to** assign barcode/QR/RFID tags to assets  
**So that** I can track assets electronically

**Acceptance Criteria:**
- **Given** I have Asset Manager permissions
- **When** I assign a tag to an asset
- **Then** I can generate and print barcode/QR code labels
- **And** I can register RFID tags with the system
- **And** each tag is uniquely linked to one asset
- **And** the system prevents duplicate tag assignments
- **And** I can view all tagged assets in a report

### US-05-002: Mobile Asset Scanning
**As a** Maintenance Technician  
**I want to** scan asset tags with my mobile device  
**So that** I can quickly identify and access asset information

**Acceptance Criteria:**
- **Given** I have a mobile device with camera
- **When** I scan an asset barcode or QR code
- **Then** the asset detail page opens automatically
- **And** I can view current asset status and maintenance history
- **And** I can create work orders directly from the scan
- **And** the system works offline and syncs when connected
- **And** scan history is logged for audit purposes

### US-05-003: Location Tracking
**As an** Asset Manager  
**I want to** track asset movements and locations  
**So that** I always know where assets are located

**Acceptance Criteria:**
- **Given** I have Asset Manager permissions
- **When** I update an asset's location
- **Then** I can select from predefined locations or add new ones
- **And** the system records the location change with timestamp
- **And** I can view location history for any asset
- **And** I can generate reports showing assets by location
- **And** GPS-enabled assets update location automatically

---

## 6. Financial Management User Stories

### US-06-001: Depreciation Calculation
**As a** Finance Manager  
**I want to** automatically calculate asset depreciation  
**So that** financial records are always accurate

**Acceptance Criteria:**
- **Given** I have Finance Manager permissions
- **When** I configure depreciation settings
- **Then** I can choose depreciation method (straight-line, declining balance)
- **And** I can set useful life and salvage value for asset categories
- **And** the system calculates monthly depreciation automatically
- **And** I can view depreciation schedules and reports
- **And** the system integrates with accounting software

### US-06-002: Total Cost of Ownership
**As a** Finance Manager  
**I want to** track total cost of ownership for assets  
**So that** I can make informed investment decisions

**Acceptance Criteria:**
- **Given** I have Finance Manager permissions
- **When** I view an asset's financial summary
- **Then** I see purchase cost, maintenance costs, and operating costs
- **And** I can view TCO trends over time
- **And** I can compare TCO between similar assets
- **And** I can export TCO reports for analysis
- **And** the system updates TCO in real-time as costs are incurred

---

## 7. Compliance Management User Stories

### US-07-001: Compliance Scheduling
**As a** Compliance Officer  
**I want to** schedule regulatory inspections and certifications  
**So that** the organization remains compliant with all regulations

**Acceptance Criteria:**
- **Given** I have Compliance Officer permissions
- **When** I create a compliance schedule
- **Then** I can select applicable regulations and standards
- **And** I can set inspection frequencies based on requirements
- **And** the system automatically schedules inspections
- **And** I receive advance notifications before due dates
- **And** I can assign inspectors and track completion

### US-07-002: Inspection Recording
**As an** Inspector  
**I want to** record inspection results and upload documents  
**So that** compliance records are complete and accessible

**Acceptance Criteria:**
- **Given** I have Inspector permissions
- **When** I complete an inspection
- **Then** I can record pass/fail status and detailed findings
- **And** I can upload inspection reports and certificates
- **And** I can add photos and notes as evidence
- **And** the system timestamps and signs the record
- **And** compliance managers are notified of results

---

## 8. User Management User Stories

### US-08-001: User Role Management
**As a** System Administrator  
**I want to** manage user roles and permissions  
**So that** users have appropriate access levels

**Acceptance Criteria:**
- **Given** I have Administrator permissions
- **When** I create or edit a user account
- **Then** I can assign one of the predefined roles (Admin, Manager, Technician, Auditor, Viewer)
- **And** I can customize permissions within role constraints
- **And** the system enforces role-based access throughout the application
- **And** I can view user activity logs for security monitoring
- **And** I can deactivate users who leave the organization

### US-08-002: Secure Authentication
**As a** System User  
**I want to** log in securely with multi-factor authentication  
**So that** my account and system data are protected

**Acceptance Criteria:**
- **Given** I have a valid user account
- **When** I attempt to log in
- **Then** I must provide username and password
- **And** I must complete multi-factor authentication
- **And** the system locks my account after 5 failed attempts
- **And** I receive email notifications for suspicious login attempts
- **And** my session expires after inactivity

---

## 9. Reporting User Stories

### US-09-001: Dashboard Analytics
**As a** Manager  
**I want to** view a dashboard with key performance indicators  
**So that** I can monitor system performance at a glance

**Acceptance Criteria:**
- **Given** I have Manager permissions
- **When** I access the dashboard
- **Then** I see KPI cards for total assets, active work orders, compliance status
- **And** I can view charts showing maintenance trends and costs
- **And** I can filter dashboard data by date range and location
- **And** the dashboard refreshes automatically every 5 minutes
- **And** I can export dashboard data to Excel

### US-09-002: Custom Report Builder
**As a** Finance Manager  
**I want to** create custom reports with specific metrics  
**So that** I can analyze data according to my needs

**Acceptance Criteria:**
- **Given** I have Finance Manager permissions
- **When** I use the report builder
- **Then** I can select data sources and metrics
- **And** I can apply filters and grouping
- **And** I can choose chart types and formatting
- **And** I can save and schedule custom reports
- **And** I can export reports in PDF, Excel, or CSV format

---

## 10. Integration User Stories

### US-10-001: ERP Integration
**As a** System Administrator  
**I want to** integrate the system with our ERP  
**So that** asset and financial data sync automatically

**Acceptance Criteria:**
- **Given** I have Administrator permissions
- **When** I configure ERP integration
- **Then** I can map asset fields to ERP fields
- **And** I can set sync frequency and direction
- **And** the system handles API authentication securely
- **And** I can monitor sync status and errors
- **And** data conflicts are flagged for manual resolution

---

## 11. Mobile Access User Stories

### US-11-001: Mobile Work Order Management
**As a** Maintenance Technician  
**I want to** manage work orders on my mobile device  
**So that** I can update work status from the field

**Acceptance Criteria:**
- **Given** I have Technician permissions and a mobile device
- **When** I access the system on mobile
- **Then** I can view my assigned work orders
- **And** I can update work order status and progress
- **And** I can record labor hours and parts used
- **And** I can take and upload photos of work performed
- **And** the application works offline and syncs when connected
- **And** the interface is optimized for touch and small screens

### US-11-002: Mobile Asset Scanning
**As a** Maintenance Technician  
**I want to** scan asset tags using my phone camera  
**So that** I can quickly identify assets without typing

**Acceptance Criteria:**
- **Given** I have a mobile device with camera
- **When** I use the mobile scanning feature
- **Then** I can scan barcodes, QR codes, and RFID tags
- **And** the system identifies the asset within 2 seconds
- **And** I can access asset details and create work orders
- **And** the scanner works in various lighting conditions
- **And** scan history is saved for offline access

---

## User Story Priority Matrix

| Priority | User Stories | Business Value | Complexity | User Impact |
|----------|--------------|---------------|------------|-------------|
| **High** | US-01-001, US-03-001, US-03-002, US-08-001, US-08-002 | Critical | Medium | All users |
| **Medium** | US-01-002, US-02-001, US-03-003, US-03-004, US-04-001, US-05-001, US-07-001, US-09-001, US-11-001 | High | Medium | Most users |
| **Low** | US-01-003, US-01-004, US-02-002, US-04-002, US-04-003, US-05-002, US-05-003, US-06-001, US-06-002, US-07-002, US-09-002, US-10-001, US-11-002 | Medium | Low | Specific users |

---

## Acceptance Testing Criteria

### General Acceptance Criteria
- All user stories must be tested in a production-like environment
- Tests must cover both happy path and edge cases
- Performance requirements must be met during testing
- Security requirements must be validated
- Accessibility requirements must be verified

### User Acceptance Testing Process
1. **Preparation:** Test scenarios are created for each user story
2. **Execution:** Users perform tests in guided sessions
3. **Validation:** Results are documented and issues tracked
4. **Sign-off:** Users formally accept or reject each user story
5. **Deployment:** Accepted features are deployed to production

### Success Metrics
- 95% of user stories pass acceptance testing
- All high-priority user stories are accepted
- System performance meets all requirements
- User satisfaction score > 4.0/5.0
- No critical security vulnerabilities identified

---

**Document Control**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | May 2026 | SSEBUGWAWO ALVIN BRANDON | Initial user stories and acceptance criteria |
