# Asset & Equipment Management System (AEMS)

A comprehensive, multi-tier enterprise application for managing physical assets and equipment across organizational operations.

## Overview

The Asset & Equipment Management System (AEMS) is a Laravel-based web application providing end-to-end management of physical assets, equipment lifecycle, maintenance workflows, inventory control, financial tracking, and compliance management.

## Key Features

- **Asset Registry**: Central database of all organizational assets with full metadata
- **Lifecycle Management**: Track assets from procurement through disposal
- **Maintenance Management**: Preventive, corrective, and predictive maintenance workflows
- **Inventory & Parts Management**: Spare parts inventory tracking and supplier management
- **Location Tracking**: Real-time asset location via barcodes, QR codes, RFID
- **Financial & Depreciation**: Asset valuation, TCO calculation, depreciation tracking
- **Compliance & Inspection**: Scheduled inspections, certifications, safety audits
- **Role-Based Access Control**: RBAC with 6 predefined roles
- **Reporting & Analytics**: Dashboards, KPIs, and exportable reports
- **Audit Logging**: Complete activity trail for compliance

## Technology Stack

- **Backend**: PHP 8.2 + Laravel 12
- **Frontend**: Blade Templates + Tailwind CSS
- **Database**: MySQL 8.x
- **Authentication**: Laravel Sanctum
- **ORM**: Eloquent
- **Build Tool**: Vite
- **Queue**: Redis (optional)

## System Requirements

- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js 18+ and npm

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd Asset-Mgt-System
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` file with database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aems_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed
```

This will create:
- 6 user roles (Admin, Manager, Technician, Finance, Auditor, Viewer)
- 20+ permissions
- 8 asset categories
- 4 locations
- 4 departments
- Demo users for each role

### 5. Build Assets

```bash
npm run build
# or for development
npm run dev
```

### 6. Start the Application

```bash
# Using the setup script
composer run dev

# Or manually run both servers
php artisan serve
npm run dev
```

The application will be available at `http://localhost:8000`

## Default Demo Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@aems.local | password | Admin |
| manager@aems.local | password | Manager |
| technician@aems.local | password | Technician |
| finance@aems.local | password | Finance |

## Project Structure

### Backend (`app/`)

- **Models/**: 15 Eloquent models with relationships
  - Asset, WorkOrder, SparePart, Inspection, DepreciationRecord, etc.
  
- **Services/**: Business logic layer
  - AssetService: Asset CRUD and stats
  - WorkOrderService: Maintenance order management
  - DepreciationService: Asset valuation and TCO
  - InventoryService: Stock management
  - ComplianceService: Inspection scheduling
  - AuditService: Activity logging

- **Http/Controllers/Api/**: RESTful API endpoints
  - AssetController
  - WorkOrderController
  - InventoryController
  - InspectionController
  - FinancialController
  - DashboardController

- **Http/Requests/**: Form validation
  - StoreAssetRequest, UpdateAssetRequest
  - StoreWorkOrderRequest, UpdateWorkOrderRequest
  - StoreSparePartRequest, UpdateSparePartRequest
  - StoreInspectionRequest, UpdateInspectionRequest

- **Console/Commands/**: Artisan commands
  - GeneratePMSchedules: Auto-generate preventive maintenance schedules
  - CalculateDepreciation: Calculate asset depreciation

### Database (`database/`)

- **migrations/**: 13 database tables
- **seeders/**: Initial data

### Frontend (`resources/`)

- **views/**: Blade templates
  - `layout.blade.php`: Main layout
  - `dashboard.blade.php`: KPI dashboard
  - `assets/`: Asset management views
  - `work-orders/`: Maintenance tracking
  - `inventory/`: Parts management
  - `inspections/`: Compliance views

- **css/**: Tailwind CSS styling
- **js/**: JavaScript functionality

### Routes

- **routes/api.php**: RESTful API routes (protected with auth:sanctum)
- **routes/web.php**: Web application routes

## API Documentation

### Authentication

All API endpoints require authentication via Laravel Sanctum tokens.

### Main Endpoints

#### Assets
```
GET    /api/assets                          # List all assets
POST   /api/assets                          # Create asset
GET    /api/assets/{asset}                  # Get asset details
PATCH  /api/assets/{asset}                  # Update asset
DELETE /api/assets/{asset}                  # Delete asset
POST   /api/assets/{asset}/change-status    # Change asset status
GET    /api/assets/stats                    # Asset statistics
```

#### Work Orders
```
GET    /api/work-orders                     # List work orders
POST   /api/work-orders                     # Create work order
GET    /api/work-orders/{workOrder}         # Get work order details
PATCH  /api/work-orders/{workOrder}         # Update work order
POST   /api/work-orders/{workOrder}/change-status  # Update status
POST   /api/work-orders/{workOrder}/add-parts      # Add parts to WO
GET    /api/work-orders/stats               # Work order statistics
```

#### Inventory
```
GET    /api/spare-parts                     # List spare parts
POST   /api/spare-parts                     # Create spare part
GET    /api/spare-parts/{sparePart}         # Get part details
PATCH  /api/spare-parts/{sparePart}         # Update spare part
POST   /api/spare-parts/{sparePart}/add-stock     # Add stock
POST   /api/spare-parts/{sparePart}/remove-stock  # Remove stock
GET    /api/spare-parts/low-stock           # Get low stock items
GET    /api/spare-parts/stats               # Inventory statistics
```

#### Inspections
```
GET    /api/inspections                     # List inspections
POST   /api/inspections                     # Schedule inspection
GET    /api/inspections/{inspection}        # Get inspection details
PATCH  /api/inspections/{inspection}        # Update inspection
POST   /api/inspections/{inspection}/complete    # Mark as complete
GET    /api/inspections/upcoming            # Get upcoming inspections
GET    /api/inspections/overdue             # Get overdue inspections
GET    /api/inspections/stats               # Compliance statistics
```

#### Financial
```
POST   /api/financial/depreciation/{asset}  # Calculate depreciation
GET    /api/financial/tco/{asset}           # Get total cost of ownership
GET    /api/financial/depreciation-trend/{asset}  # Depreciation trend
GET    /api/financial/portfolio-value       # Total asset portfolio value
```

#### Dashboard
```
GET    /api/dashboard                       # Dashboard KPIs and charts
```

## Database Schema

### Core Tables

- **assets**: Physical assets with lifecycle status
- **work_orders**: Maintenance tasks and repairs
- **spare_parts**: Inventory items and consumables
- **work_order_parts**: Pivot table for parts used in work orders
- **maintenance_records**: Historical maintenance events
- **inspections**: Scheduled and completed audits
- **depreciation_records**: Asset valuation history
- **iot_readings**: Sensor telemetry data
- **activity_logs**: System audit trail
- **roles**: User role definitions
- **permissions**: System permissions
- **role_permissions**: Role-permission assignments
- **categories**: Asset categorization
- **locations**: Physical location definitions
- **departments**: Organizational departments

## User Roles & Permissions

### Admin
- Full system access
- All CRUD operations
- User and role management
- System settings

### Manager
- View and create assets
- Create and assign work orders
- Inventory management
- View reports and audit logs

### Technician
- View assets
- Complete assigned work orders
- View inventory
- View inspections

### Finance
- View asset financials
- Edit depreciation settings
- Generate financial reports

### Auditor
- View all assets and work orders
- Schedule and complete inspections
- View compliance reports
- View audit logs

### Viewer
- Read-only access to assets, work orders, and reports

## Scheduled Tasks

Configure in `routes/console.php` and `app/Console/Kernel.php`:

```php
// Generate preventive maintenance schedules daily
Schedule::command('pm:generate')->daily();

// Calculate depreciation monthly
Schedule::command('depreciation:calculate')->monthlyOn(1, '00:00');

// Send compliance reminders
Schedule::command('compliance:remind-inspections')->dailyAt('09:00');
```

## Running Commands Manually

```bash
# Generate PM schedules for assets due in next 7 days
php artisan pm:generate --days=7

# Calculate straight-line depreciation
php artisan depreciation:calculate --method=straight_line

# Calculate declining-balance depreciation
php artisan depreciation:calculate --method=declining_balance
```

## Development

### Running Tests

```bash
php artisan test
```

### Debugging

Use Laravel Telescope for request inspection:

```bash
php artisan telescope:install
```

Access at `/telescope`

### Code Formatting

```bash
php artisan pint
```

## Troubleshooting

### Database Migration Errors
```bash
php artisan migrate:rollback
php artisan migrate
php artisan db:seed
```

### Permission Denied Errors
```bash
chmod -R 775 storage bootstrap/cache
```

### NPM Build Issues
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Missing UUID Column
Ensure all migrations ran successfully:
```bash
php artisan migrate:status
```

## Deployment

### Production Checklist

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Set secure `APP_KEY`
4. Configure database backups
5. Set up Redis for caching and queues
6. Configure email for notifications
7. Set up SSL certificates
8. Configure automated backups

### Typical Production Deployment

```bash
# Clone repository
git clone <repo-url>
cd Asset-Mgt-System

# Install dependencies
composer install --no-dev
npm install --production

# Environment setup
cp .env.production .env
php artisan key:generate

# Database
php artisan migrate --force
php artisan db:seed --force

# Build assets
npm run build

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Support & Documentation

- Laravel Documentation: https://laravel.com/docs
- Tailwind CSS: https://tailwindcss.com/docs
- Eloquent ORM: https://laravel.com/docs/eloquent

## License

This project is licensed under the MIT License.

## Contributing

Contributions are welcome! Please create a feature branch and submit a pull request.

## Change Log

### Version 1.0.0 (Current)
- Initial release
- Complete asset management system
- Work order and maintenance tracking
- Inventory management
- Inspection scheduling
- Financial tracking
- Audit logging
- Role-based access control
