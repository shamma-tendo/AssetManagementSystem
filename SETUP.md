# AEMS Quick Setup Guide

## Step 1: Install Dependencies
```bash
composer install
npm install
```

## Step 2: Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=aems_db
DB_USERNAME=root
DB_PASSWORD=
```

## Step 3: Setup Database
```bash
php artisan migrate
php artisan db:seed
```

## Step 4: Build Frontend
```bash
npm run build
```

## Step 5: Start Application
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite (in another terminal)
npm run dev
```

Access the application at: **http://localhost:8000**

## Demo Accounts

All demo accounts use password: `password`

| Email | Role |
|-------|------|
| admin@aems.local | Admin |
| manager@aems.local | Manager |
| technician@aems.local | Technician |
| finance@aems.local | Finance |

## Key Features Available

✅ Asset Registry Management
✅ Work Order Tracking  
✅ Inventory Management
✅ Inspection Scheduling
✅ Financial Reporting
✅ Depreciation Calculation
✅ Activity Audit Logging
✅ Role-Based Access Control

## Next Steps

1. Login with admin account
2. Create assets in the Asset Registry
3. Schedule work orders for maintenance
4. Manage spare parts inventory
5. Schedule inspections
6. View financial reports

## API Documentation

All API endpoints are available at `/api/` and require authentication.

Documentation: See AEMS_DOCUMENTATION.md for full API reference
