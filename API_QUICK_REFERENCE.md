# AEMS API Quick Reference

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints require Laravel Sanctum token authentication:
```
Authorization: Bearer YOUR_TOKEN
```

## Response Format
All responses return JSON with this structure:
```json
{
  "success": true,
  "data": {...},
  "message": "Operation successful"
}
```

---

## Asset Management Endpoints

### List Assets
```
GET /api/assets?page=1&per_page=15&status=Active&category_id=...&location_id=...
```
**Filters**: page, per_page, status, category_id, location_id, search
**Response**: Paginated list of assets

### Create Asset
```
POST /api/assets
Content-Type: application/json

{
  "name": "Hydraulic Pump A1",
  "serial_number": "HP-2024-001",
  "model": "P280",
  "manufacturer": "Eaton",
  "category_id": "019e210b-...",
  "location_id": "019e210e-...",
  "purchase_date": "2024-05-13",
  "purchase_cost": 15000.00,
  "salvage_value": 1500.00,
  "useful_life_years": 10,
  "department_id": "...",
  "barcode": "BAR-001",
  "description": "Main plant hydraulic pump"
}
```

### Get Asset
```
GET /api/assets/{asset_id}
```

### Update Asset
```
PATCH /api/assets/{asset_id}
```

### Delete Asset
```
DELETE /api/assets/{asset_id}
```

### Change Asset Status
```
POST /api/assets/{asset_id}/change-status
Content-Type: application/json

{
  "status": "Active"
}
```
**Valid Statuses**: Ordered, Received, Active, Under Maintenance, Retired, Disposed

### Asset Statistics
```
GET /api/assets/stats
```
**Response**: 
```json
{
  "total_assets": 45,
  "active_assets": 38,
  "under_maintenance": 5,
  "retired_assets": 2,
  "total_asset_value": 525000.00
}
```

---

## Work Order Endpoints

### List Work Orders
```
GET /api/work-orders?status=Open&asset_id=...
```
**Filters**: status, asset_id, assigned_to, type

### Create Work Order
```
POST /api/work-orders
Content-Type: application/json

{
  "asset_id": "019e211c-...",
  "type": "Preventive",
  "assigned_to": "019e211c-...",
  "scheduled_date": "2024-05-20T10:00:00",
  "estimated_labor_hours": 4,
  "estimated_cost": 500.00,
  "description": "Routine maintenance"
}
```
**Type Options**: Preventive, Corrective, Predictive

### Get Work Order
```
GET /api/work-orders/{workorder_id}
```

### Update Work Order
```
PATCH /api/work-orders/{workorder_id}
```

### Change Work Order Status
```
POST /api/work-orders/{workorder_id}/change-status
Content-Type: application/json

{
  "status": "Completed",
  "actual_labor_hours": 3.5,
  "actual_cost": 450.00
}
```
**Status Options**: Open, In Progress, On Hold, Completed, Cancelled

### Add Parts to Work Order
```
POST /api/work-orders/{workorder_id}/add-parts
Content-Type: application/json

{
  "parts": [
    {
      "spare_part_id": "019e211c-...",
      "quantity_used": 2,
      "unit_cost": 50.00
    }
  ]
}
```

### Work Order Statistics
```
GET /api/work-orders/stats
```

---

## Inventory Endpoints

### List Spare Parts
```
GET /api/spare-parts?category_id=...&low_stock=false
```

### Create Spare Part
```
POST /api/spare-parts
Content-Type: application/json

{
  "part_number": "SP-001",
  "part_name": "Hydraulic Seal",
  "unit_cost": 45.00,
  "stock_quantity": 50,
  "reorder_point": 10,
  "reorder_quantity": 25,
  "supplier": "Fluid Systems Inc",
  "unit_of_measure": "EA",
  "category_id": "..."
}
```

### Get Spare Part
```
GET /api/spare-parts/{sparePart_id}
```

### Update Spare Part
```
PATCH /api/spare-parts/{sparePart_id}
```

### Delete Spare Part
```
DELETE /api/spare-parts/{sparePart_id}
```

### Add Stock
```
POST /api/spare-parts/{sparePart_id}/add-stock
Content-Type: application/json

{
  "quantity": 25,
  "notes": "Replenishment order received"
}
```

### Remove Stock
```
POST /api/spare-parts/{sparePart_id}/remove-stock
Content-Type: application/json

{
  "quantity": 2,
  "notes": "Used in WO-2024-001"
}
```

### Low Stock Items
```
GET /api/spare-parts/low-stock
```

### Inventory Statistics
```
GET /api/spare-parts/stats
```
**Response**:
```json
{
  "total_parts": 125,
  "low_stock_parts": 8,
  "out_of_stock_parts": 2,
  "inventory_value": 45250.00
}
```

---

## Inspection Endpoints

### List Inspections
```
GET /api/inspections?asset_id=...&status=Scheduled
```

### Schedule Inspection
```
POST /api/inspections
Content-Type: application/json

{
  "asset_id": "019e211c-...",
  "inspection_type": "Safety",
  "scheduled_date": "2024-05-25T10:00:00",
  "next_due_date": "2024-08-25T10:00:00",
  "inspector_id": "019e211c-...",
  "compliance_standard": "ISO 14001",
  "findings": ""
}
```

### Get Inspection
```
GET /api/inspections/{inspection_id}
```

### Update Inspection
```
PATCH /api/inspections/{inspection_id}
```

### Complete Inspection
```
POST /api/inspections/{inspection_id}/complete
Content-Type: application/json

{
  "completed_date": "2024-05-25T14:30:00",
  "findings": "All components in good condition",
  "corrective_actions": "None required",
  "certification_status": "Passed"
}
```

### Upcoming Inspections
```
GET /api/inspections/upcoming?days=30
```

### Overdue Inspections
```
GET /api/inspections/overdue
```

### Inspection Statistics
```
GET /api/inspections/stats
```
**Response**:
```json
{
  "total": 45,
  "scheduled": 20,
  "completed": 22,
  "overdue": 3,
  "compliance_met": 42,
  "compliance_failed": 3
}
```

---

## Financial Endpoints

### Calculate Depreciation
```
POST /api/financial/depreciation/{asset_id}
Content-Type: application/json

{
  "method": "straight_line"
}
```
**Methods**: straight_line, declining_balance

### Total Cost of Ownership
```
GET /api/financial/tco/{asset_id}
```
**Response**:
```json
{
  "purchase_cost": 15000.00,
  "maintenance_cost": 2500.00,
  "parts_cost": 1200.00,
  "total_cost": 18700.00,
  "current_value": 12000.00,
  "years_owned": 2.5,
  "average_annual_cost": 7480.00
}
```

### Depreciation Trend
```
GET /api/financial/depreciation-trend/{asset_id}?years=5
```

### Portfolio Value
```
GET /api/financial/portfolio-value
```
**Response**:
```json
{
  "total_purchase_value": 525000.00,
  "total_current_value": 405000.00,
  "total_depreciation": 120000.00,
  "average_depreciation_rate": 22.86
}
```

---

## Dashboard Endpoint

### Get Dashboard Data
```
GET /api/dashboard
```
**Response**:
```json
{
  "total_assets": 45,
  "active_assets": 38,
  "open_work_orders": 8,
  "total_asset_value": 525000.00,
  "under_maintenance": 5,
  "compliance_alerts": 3,
  "low_stock_items": 8,
  "assets_by_status": {...},
  "recent_work_orders": [...],
  "upcoming_inspections": [...],
  "low_stock_parts": [...]
}
```

---

## Common Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized (missing token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Unprocessable Entity (validation failed) |
| 500 | Server Error |

---

## Example: Complete Workflow

### 1. Create an Asset
```bash
curl -X POST http://localhost:8000/api/assets \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Motor M1",
    "serial_number": "MOT-001",
    "category_id": "019e210b-...",
    "purchase_date": "2024-05-13",
    "purchase_cost": 8000
  }'
```

### 2. Schedule Preventive Maintenance
```bash
curl -X POST http://localhost:8000/api/work-orders \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "asset_id": "019e211c-...",
    "type": "Preventive",
    "scheduled_date": "2024-05-20T10:00:00"
  }'
```

### 3. Add Parts to Work Order
```bash
curl -X POST http://localhost:8000/api/work-orders/{WO_ID}/add-parts \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "parts": [{"spare_part_id": "...", "quantity_used": 1}]
  }'
```

### 4. Complete Work Order
```bash
curl -X POST http://localhost:8000/api/work-orders/{WO_ID}/change-status \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "Completed",
    "actual_labor_hours": 2,
    "actual_cost": 250
  }'
```

---

**For complete documentation, see AEMS_DOCUMENTATION.md**
