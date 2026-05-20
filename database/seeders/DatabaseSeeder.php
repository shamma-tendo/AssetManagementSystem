<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\SparePart;
use App\Models\Organization;
use App\Models\AssetRequest;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'System Administrator with full access'
        ]);

        $managerRole = Role::create([
            'name' => 'Manager',
            'description' => 'Asset Manager and Supervisor'
        ]);

        $technicianRole = Role::create([
            'name' => 'Technician',
            'description' => 'Operations & Maintenance Technician'
        ]);

        $financeRole = Role::create([
            'name' => 'Finance',
            'description' => 'Finance & Accounting Staff'
        ]);

        $auditorRole = Role::create([
            'name' => 'Auditor',
            'description' => 'Compliance & Audit Officer'
        ]);

        $viewerRole = Role::create([
            'name' => 'Viewer',
            'description' => 'Read-only access'
        ]);

        // Create Permissions
        $permissions = [
            'view_assets', 'create_asset', 'edit_asset', 'delete_asset',
            'view_work_orders', 'create_work_order', 'edit_work_order', 'complete_work_order',
            'view_inventory', 'manage_inventory',
            'view_financials', 'edit_financials',
            'view_reports', 'generate_reports',
            'manage_users', 'manage_roles',
            'view_audit_log',
            'create_resource_request', 'approve_resource_request', 'view_executive_overview',
            'manage_asset_assignments', 'report_assignment_condition',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'description' => ucwords(str_replace('_', ' ', $permission))
            ]);
        }

        // Assign permissions to Admin
        $adminPermissions = Permission::all();
        $adminRole->permissions()->attach($adminPermissions);

        // Assign permissions to Manager
        $managerPermissions = Permission::whereIn('name', [
            'view_assets', 'create_asset', 'edit_asset',
            'view_work_orders', 'create_work_order', 'edit_work_order',
            'view_inventory', 'manage_inventory',
            'view_reports', 'view_audit_log',
            'create_resource_request', 'manage_asset_assignments',
        ])->get();
        $managerRole->permissions()->attach($managerPermissions);

        // Assign permissions to Technician
        $technicianPermissions = Permission::whereIn('name', [
            'view_assets',
            'view_work_orders', 'edit_work_order', 'complete_work_order',
            'view_inventory',
            'view_reports',
            'report_assignment_condition',
        ])->get();
        $technicianRole->permissions()->attach($technicianPermissions);

        // Assign permissions to Finance
        $financePermissions = Permission::whereIn('name', [
            'view_assets',
            'view_financials', 'edit_financials',
            'view_reports', 'generate_reports',
            'approve_resource_request', 'view_executive_overview',
        ])->get();
        $financeRole->permissions()->attach($financePermissions);

        // Assign permissions to Auditor
        $auditorPermissions = Permission::whereIn('name', [
            'view_assets',
            'view_work_orders',
            'view_inventory',
            'view_financials',
            'view_reports', 'generate_reports',
            'view_audit_log',
            'view_executive_overview',
        ])->get();
        $auditorRole->permissions()->attach($auditorPermissions);

        // Assign minimal permissions to Viewer
        $viewerPermissions = Permission::whereIn('name', [
            'view_assets',
            'view_work_orders',
            'view_inventory',
            'view_reports'
        ])->get();
        $viewerRole->permissions()->attach($viewerPermissions);

        $staffRole = Role::create([
            'name' => 'Staff',
            'description' => 'Assigned colleague — acknowledges custody and reports field condition',
        ]);
        $staffRole->permissions()->attach(Permission::whereIn('name', [
            'view_assets', 'report_assignment_condition',
        ])->get());

        $companyOrg = Organization::create([
            'name' => 'Northwind Industrial',
            'slug' => 'demo-company',
            'type' => 'company',
            'email' => 'admin@northwind.local',
            'code' => 'NORT-' . strtoupper(substr(md5(uniqid()), 0, 4)),
        ]);

        $householdOrg = Organization::create([
            'name' => 'Elmwood Household',
            'slug' => 'demo-household',
            'type' => 'household',
            'email' => 'home@elmwood.local',
            'next_of_kin_name' => 'Jordan Elmwood',
            'next_of_kin_relationship' => 'Sibling',
            'next_of_kin_email' => 'jordan@example.com',
            'next_of_kin_phone' => '+1-555-0100',
        ]);

        // Create Categories
        $categories = [
            ['name' => 'Pumps & Compressors', 'code' => 'PUMP'],
            ['name' => 'Motors & Drivers', 'code' => 'MOTOR'],
            ['name' => 'Hydraulic Equipment', 'code' => 'HYDR'],
            ['name' => 'Pneumatic Equipment', 'code' => 'PNEU'],
            ['name' => 'Heat Exchangers', 'code' => 'HEAT'],
            ['name' => 'Valves & Controls', 'code' => 'VALV'],
            ['name' => 'Instrumentation', 'code' => 'INST'],
            ['name' => 'Electrical Equipment', 'code' => 'ELEC'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create Locations
        $locations = [
            [
                'name' => 'Main Plant - Building A',
                'address' => '123 Industrial Way',
                'building' => 'A',
                'floor' => '1',
                'room' => '101'
            ],
            [
                'name' => 'Main Plant - Building B',
                'address' => '123 Industrial Way',
                'building' => 'B',
                'floor' => '1',
                'room' => '201'
            ],
            [
                'name' => 'Warehouse - Storage 1',
                'address' => '456 Warehouse Drive',
                'building' => 'W',
                'floor' => '1',
                'room' => 'S1'
            ],
            [
                'name' => 'Maintenance Shop',
                'address' => '789 Service Road',
                'building' => 'M',
                'floor' => '1',
                'room' => 'SHOP'
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }

        // Create Departments
        $departments = [
            ['name' => 'Operations', 'description' => 'Plant Operations Department'],
            ['name' => 'Maintenance', 'description' => 'Equipment Maintenance Department'],
            ['name' => 'Finance', 'description' => 'Finance & Accounting Department'],
            ['name' => 'Quality Assurance', 'description' => 'Quality Assurance Department'],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }

        // Create demo admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@aems.local',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'organization_id' => $companyOrg->id,
            'email_verified_at' => now(),
        ]);

        // Create demo users for each role
        User::create([
            'name' => 'Manager Demo',
            'email' => 'manager@aems.local',
            'password' => bcrypt('password'),
            'role_id' => $managerRole->id,
            'organization_id' => $companyOrg->id,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Technician Demo',
            'email' => 'technician@aems.local',
            'password' => bcrypt('password'),
            'role_id' => $technicianRole->id,
            'organization_id' => $companyOrg->id,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Finance Demo',
            'email' => 'finance@aems.local',
            'password' => bcrypt('password'),
            'role_id' => $financeRole->id,
            'organization_id' => $companyOrg->id,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Staff Demo',
            'email' => 'staff@aems.local',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'organization_id' => $companyOrg->id,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Home Custodian',
            'email' => 'home@aems.local',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'organization_id' => $householdOrg->id,
            'email_verified_at' => now(),
        ]);

        $adminUser = User::where('email', 'admin@aems.local')->first();
        $technicianUser = User::where('email', 'technician@aems.local')->first();
        $managerUser = User::where('email', 'manager@aems.local')->first();
        $staffUser = User::where('email', 'staff@aems.local')->first();
        $firstCategory = Category::first();
        $firstLocation = Location::first();
        $firstDepartment = Department::first();

        $demoAsset = Asset::create([
            'organization_id' => $companyOrg->id,
            'name' => 'Centrifugal Pump A-101',
            'serial_number' => 'SN-DEMO-PUMP-001',
            'model' => 'CP-400',
            'manufacturer' => 'FlowTech',
            'category_id' => $firstCategory->id,
            'location_id' => $firstLocation?->id,
            'department_id' => $firstDepartment?->id,
            'purchase_date' => now()->subYears(2),
            'purchase_cost' => 45000,
            'current_value' => 45000,
            'salvage_value' => 5000,
            'useful_life_years' => 10,
            'status' => 'Active',
            'description' => 'Primary process pump — seeded demo asset.',
            'created_by' => $adminUser->id,
        ]);

        $hvacAsset = Asset::create([
            'organization_id' => $companyOrg->id,
            'name' => 'HVAC Chiller Unit B2',
            'serial_number' => 'SN-DEMO-HVAC-002',
            'model' => 'CH-800',
            'manufacturer' => 'CoolAir',
            'category_id' => $firstCategory->id,
            'location_id' => $firstLocation?->id,
            'department_id' => $firstDepartment?->id,
            'purchase_date' => now()->subMonths(18),
            'purchase_cost' => 120000,
            'current_value' => 120000,
            'salvage_value' => 10000,
            'useful_life_years' => 15,
            'status' => 'Under Maintenance',
            'created_by' => $adminUser->id,
        ]);

        WorkOrder::create([
            'work_order_number' => 'WO-DEMO-OPEN-001',
            'asset_id' => $demoAsset->id,
            'type' => 'Preventive',
            'status' => 'Open',
            'assigned_to' => $technicianUser?->id,
            'description' => 'Quarterly PM — demo work order.',
            'scheduled_date' => now()->addDays(3),
            'created_by' => $adminUser->id,
        ]);

        WorkOrder::create([
            'work_order_number' => 'WO-DEMO-PROG-002',
            'asset_id' => $demoAsset->id,
            'type' => 'Corrective',
            'status' => 'In Progress',
            'assigned_to' => $technicianUser?->id,
            'description' => 'Bearing inspection — demo.',
            'scheduled_date' => now()->subDay(),
            'started_date' => now()->subHours(2),
            'created_by' => $adminUser->id,
        ]);

        SparePart::create([
            'part_number' => 'PT-DEMO-SEAL-01',
            'part_name' => 'Mechanical seal kit',
            'description' => 'Demo spare for pump maintenance',
            'supplier' => 'Industrial Supply Co.',
            'unit_cost' => 189.50,
            'stock_quantity' => 4,
            'reorder_point' => 10,
            'reorder_quantity' => 24,
            'unit_of_measure' => 'kit',
            'category_id' => $firstCategory->id,
            'location_id' => $firstLocation?->id,
        ]);

        SparePart::create([
            'part_number' => 'PT-DEMO-FILTER-02',
            'part_name' => 'Oil filter element',
            'unit_cost' => 42.00,
            'stock_quantity' => 25,
            'reorder_point' => 8,
            'reorder_quantity' => 40,
            'category_id' => $firstCategory->id,
            'location_id' => $firstLocation?->id,
        ]);

        AssetRequest::create([
            'organization_id' => $companyOrg->id,
            'requested_by' => $managerUser->id,
            'title' => 'Need 12 laptops for Q3 onboarding',
            'description' => 'Procurement signed PO; requesting formal capacity approval and asset codes before receipt.',
            'quantity' => 12,
            'asset_type' => 'Laptop',
            'status' => 'pending',
        ]);

    }
}
