# 🚀 AssetFlow Platform - Quick Start Guide

**This guide will get you up and running with the complete multi-tenant asset management platform in 10 minutes.**

## 📋 Prerequisites

- Laravel 11 (or compatible version)
- PHP 8.1+
- MySQL/PostgreSQL
- Composer
- Node.js & npm

## ⚡ Step 1: Database Setup

### 1.1 Run Migrations
```bash
php artisan migrate
```

This will create all necessary tables including:
- Organizations with industry type support
- Users, Roles, Permissions
- Assets, Categories, Locations
- AssetRequests, AssetAssignments
- AssetConditionReports
- And more...

### 1.2 Seed Demo Data
```bash
php artisan db:seed --class=DemoDataSeeder
```

This creates realistic sample data:
- **6 Organizations** across all industry types (Hospital, School, Corporate, Retail, Manufacturing, Household)
- **10+ Users** with different roles (CEO, CFO, Asset Manager, Staff)
- **Sample Assets** and workflows
- **Test Requests** and assignments ready to explore

## 🎨 Step 2: Frontend Setup

```bash
# Install dependencies
npm install

# Build assets for development
npm run dev

# Or build for production
npm run build
```

## 🔑 Step 3: Test Users & Credentials

After seeding, use these credentials to explore the platform:

### 🏥 Hospital - Metropolitan Medical Center
```
Email: sarah@metromed.hospital
Password: password
Role: CEO
Industry: Hospital
```

```
Email: james@metromed.hospital
Password: password
Role: Asset Manager
Industry: Hospital
```

### 🎓 School - Lincoln High School
```
Email: robert@lincolnhs.edu
Password: password
Role: Principal (CEO)
Industry: School
```

### 🏢 Corporate - TechCorp Industries
```
Email: patricia@techcorp.com
Password: password
Role: CFO
Industry: Corporate

Email: marcus@techcorp.com
Password: password
Role: Asset Manager
Industry: Corporate

Email: lisa@techcorp.com
Password: password
Role: Staff
Industry: Corporate
```

### 🏠 Household - Personal Assets
```
Email: john@household.personal
Password: password
Role: Personal User
Account Type: Household
```

## 🌐 Step 4: Access the Platform

### 1. Start Your Local Server
```bash
php artisan serve
```

### 2. Navigate to the Platform
Open browser to: `http://localhost:8000`

### 3. User Journey

#### For Company Users:
1. **Select Account Type** → "For Companies"
2. **Choose Industry** → Pick any (Hospital, School, etc.)
3. **Login** → Use any corporate user credentials above
4. **See Role-Based Dashboard** → Different views for each role

#### For Household Users:
1. **Select Account Type** → "For Households"
2. **Login** → Use `john@household.personal`
3. **See Personal Dashboard** → Asset inventory & insurance

## 📱 Testing Each Role

### 🎯 Executive/CEO Dashboard
```
URL: /executive/dashboard
Features:
- Pending asset requests
- Real-time asset status overview
- Staff activity feed
- Approval queue
```

**Test Flow:**
1. Login as: `patricia@techcorp.com` (CFO)
2. View pending requests
3. See real-time asset statistics
4. Check staff activities

### 📊 Asset Manager Dashboard
```
URL: /manager/dashboard
Features:
- My asset requests
- Distribution tracking
- Pending staff reports
- Create new requests
```

**Test Flow:**
1. Login as: `marcus@techcorp.com` (Asset Manager)
2. View requests (pending/approved/rejected)
3. Check distribution summary
4. See staff reports on assets

### 👥 Staff Dashboard
```
URL: /staff/dashboard
Features:
- My assigned assets
- Report asset status
- Assignment history
- Issue tracking
```

**Test Flow:**
1. Login as: `lisa@techcorp.com` (Staff)
2. View assigned assets
3. Report asset status
4. Check past assignments

### 🏠 Household Dashboard
```
URL: /household/dashboard
Features:
- Personal asset inventory
- Insurance management
- Warranty tracking
- Portfolio value
```

**Test Flow:**
1. Login as: `john@household.personal`
2. View personal assets
3. Check insurance policies
4. See warranty expiry alerts

## 🔗 Important Routes

### Authentication
- **Tenant Selection**: `/select-tenant-type`
- **Industry Selection**: `/select-industry-type`
- **Login**: `/login`
- **Logout**: `POST /logout`

### Executive Routes
- **Dashboard**: `/executive/dashboard`
- **Approval Queue**: `/executive/approvals`

### Manager Routes
- **Dashboard**: `/manager/dashboard`
- **Create Request**: `/manager/requests/create`
- **Distribute Assets**: `/manager/distribute`

### Staff Routes
- **Dashboard**: `/staff/dashboard`
- **View Asset**: `/staff/assets/{id}`
- **Report Status**: `/staff/assets/{id}/report`

### Household Routes
- **Dashboard**: `/household/dashboard`
- **Create Asset**: `/household/assets/create`
- **View Asset**: `/household/assets/{id}`
- **Insurance**: `/household/insurance`

## 🛠️ Customization

### Add More Test Users

Edit `database/seeders/DemoDataSeeder.php` and add to the appropriate organization:

```php
User::create([
    'name' => 'New User',
    'email' => 'newuser@company.com',
    'password' => Hash::make('password'),
    'organization_id' => $company->id,
    'role_id' => Role::where('name', 'Staff')->first()->id,
]);
```

Then re-seed:
```bash
php artisan migrate:refresh --seed
```

### Customize Organization Details

Edit organization metadata in seeder to match your preferences:

```php
'industry_metadata' => json_encode([
    'employee_count' => 500,
    'departments' => ['Engineering', 'Sales', 'Marketing', 'HR', 'Finance']
])
```

## 🐛 Troubleshooting

### "Whoops, looks like something went wrong" error
1. Check `.env` file is properly configured
2. Run: `php artisan config:clear`
3. Run: `php artisan cache:clear`

### "Seeder errors" or "SQLSTATE errors"
1. Ensure database exists: `php artisan db:create`
2. Clear previous data: `php artisan migrate:refresh`
3. Re-seed: `php artisan db:seed --class=DemoDataSeeder`

### "Login redirects to login again"
1. Check user organization is set
2. Check user role exists
3. Run: `php artisan cache:clear`

### "Assets not showing in dashboard"
1. Verify user is in correct organization
2. Check role is set correctly
3. Verify assets are created for that organization

## 📈 Next Steps

After getting comfortable with the system:

1. **Create Your Own Organization**
   - Go to settings to update organization details
   - Add real users and assets

2. **Explore the API**
   - Check `routes/api.php` for available endpoints
   - Test with Postman or similar tool

3. **Customize Workflows**
   - Update dashboard views to match your branding
   - Add custom fields in industry metadata

4. **Integrate with Mobile**
   - Use API endpoints to build mobile app
   - QR code scanning for asset tracking

5. **Set Up Permissions & Approvals**
   - Configure role-based permissions
   - Create approval workflow rules

## 📚 Documentation Files

- **PLATFORM_ARCHITECTURE.md** - System design and database schema
- **SERVICE_USAGE_GUIDE.md** - API and service layer documentation
- **IMPLEMENTATION_GUIDE.md** - Complete implementation details
- **PLATFORM_SUMMARY.md** - Business overview

## 🎯 Key Features to Explore

### 1. Multi-Tenant Architecture
Each organization is completely isolated with its own:
- Users and roles
- Assets and categories
- Workflows and approvals
- Activity logs and reports

### 2. Role-Based Access
Different views and capabilities per role:
- **Executive**: Overview, approvals, monitoring
- **Asset Manager**: Requests, distribution, tracking
- **Staff**: My assets, status reporting
- **Individual**: Personal inventory, insurance

### 3. Industry-Specific Options
Tailored features for:
- Hospitals (medical equipment, compliance)
- Schools (classroom resources, checkout)
- Corporate (IT assets, furniture)
- Retail (store equipment, inventory)
- Manufacturing (machinery, tools)

### 4. Activity Tracking
See all activities:
- Asset requests and approvals
- Assignments and returns
- Status reports and changes
- User actions

## 🎓 Learning Path

1. **Day 1**: Explore dashboards, understand role-based views
2. **Day 2**: Create assets and requests, test approval workflow
3. **Day 3**: Test staff reporting and executive monitoring
4. **Day 4**: Explore household personal asset features
5. **Day 5**: Customize for your organization

## 💬 Questions or Issues?

1. Check the **IMPLEMENTATION_GUIDE.md** for technical details
2. Review **SERVICE_USAGE_GUIDE.md** for API information
3. Examine model files in `app/Models/` for database schema

---

**Congratulations!** 🎉 You now have a fully functional multi-tenant asset management platform ready for testing and customization.

**Start testing**: Open `http://localhost:8000` in your browser and select your account type!
