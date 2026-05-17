<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;

class AssetManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create root categories
        $categories = [
            [
                'name' => 'IT Equipment',
                'description' => 'Computers, servers, and networking equipment',
                'pm_frequency_months' => 3,
                'useful_life_years' => 5,
                'depreciation_method' => 'straight_line',
                'children' => [
                    [
                        'name' => 'Laptops',
                        'description' => 'Portable computers',
                        'pm_frequency_months' => 6,
                        'useful_life_years' => 3,
                        'depreciation_method' => 'straight_line',
                    ],
                    [
                        'name' => 'Desktops',
                        'description' => 'Desktop computers',
                        'pm_frequency_months' => 6,
                        'useful_life_years' => 5,
                        'depreciation_method' => 'straight_line',
                    ],
                    [
                        'name' => 'Servers',
                        'description' => 'Server equipment',
                        'pm_frequency_months' => 1,
                        'useful_life_years' => 7,
                        'depreciation_method' => 'declining_balance',
                    ],
                ],
            ],
            [
                'name' => 'Office Equipment',
                'description' => 'General office equipment and furniture',
                'pm_frequency_months' => 12,
                'useful_life_years' => 7,
                'depreciation_method' => 'straight_line',
                'children' => [
                    [
                        'name' => 'Printers',
                        'description' => 'Printing equipment',
                        'pm_frequency_months' => 3,
                        'useful_life_years' => 5,
                        'depreciation_method' => 'straight_line',
                    ],
                    [
                        'name' => 'Furniture',
                        'description' => 'Office furniture',
                        'pm_frequency_months' => 12,
                        'useful_life_years' => 10,
                        'depreciation_method' => 'straight_line',
                    ],
                ],
            ],
            [
                'name' => 'Vehicles',
                'description' => 'Company vehicles and transportation equipment',
                'pm_frequency_months' => 3,
                'useful_life_years' => 8,
                'depreciation_method' => 'declining_balance',
                'children' => [
                    [
                        'name' => 'Cars',
                        'description' => 'Passenger vehicles',
                        'pm_frequency_months' => 3,
                        'useful_life_years' => 5,
                        'depreciation_method' => 'declining_balance',
                    ],
                    [
                        'name' => 'Trucks',
                        'description' => 'Cargo vehicles',
                        'pm_frequency_months' => 2,
                        'useful_life_years' => 10,
                        'depreciation_method' => 'declining_balance',
                    ],
                ],
            ],
        ];

        // Create categories
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create($categoryData);

            // Create child categories
            foreach ($children as $childData) {
                $childData['parent_category_id'] = $category->id;
                Category::create($childData);
            }
        }

        // Create locations
        $locations = [
            [
                'name' => 'Head Office',
                'code' => 'HO-001',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'USA',
                'children' => [
                    [
                        'name' => 'IT Department',
                        'code' => 'HO-IT-001',
                        'address' => '123 Main Street, Floor 3',
                        'city' => 'New York',
                        'state' => 'NY',
                        'postal_code' => '10001',
                        'country' => 'USA',
                    ],
                    [
                        'name' => 'Server Room',
                        'code' => 'HO-SR-001',
                        'address' => '123 Main Street, Basement',
                        'city' => 'New York',
                        'state' => 'NY',
                        'postal_code' => '10001',
                        'country' => 'USA',
                    ],
                ],
            ],
            [
                'name' => 'Branch Office',
                'code' => 'BO-001',
                'address' => '456 Oak Avenue',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90001',
                'country' => 'USA',
            ],
            [
                'name' => 'Warehouse',
                'code' => 'WH-001',
                'address' => '789 Industrial Blvd',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60007',
                'country' => 'USA',
            ],
        ];

        // Create locations
        foreach ($locations as $locationData) {
            $children = $locationData['children'] ?? [];
            unset($locationData['children']);

            $location = Location::create($locationData);

            // Create child locations
            foreach ($children as $childData) {
                $childData['parent_location_id'] = $location->id;
                Location::create($childData);
            }
        }

        // Create departments
        $departments = [
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'IT services and support',
                'budget_code' => 'IT-001',
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'HR and personnel management',
                'budget_code' => 'HR-001',
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Financial management and accounting',
                'budget_code' => 'FIN-001',
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Business operations and logistics',
                'budget_code' => 'OPS-001',
            ],
            [
                'name' => 'Maintenance',
                'code' => 'MAINT',
                'description' => 'Facility and equipment maintenance',
                'budget_code' => 'MAINT-001',
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::create($departmentData);
        }

        // Create sample users
        $headOffice = Location::where('name', 'Head Office')->first();
        $itDepartment = Department::where('code', 'IT')->first();
        $hrDepartment = Department::where('code', 'HR')->first();
        $opsDepartment = Department::where('code', 'OPS')->first();

        $users = [
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@assetmanagement.com',
                'username' => 'admin',
                'password' => bcrypt('admin123'),
                'role' => UserRole::ADMIN,
                'department_id' => $itDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0001',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@assetmanagement.com',
                'username' => 'john.smith',
                'password' => bcrypt('password123'),
                'role' => UserRole::MANAGER,
                'department_id' => $itDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0002',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@assetmanagement.com',
                'username' => 'sarah.johnson',
                'password' => bcrypt('password123'),
                'role' => UserRole::MANAGER,
                'department_id' => $hrDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0003',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Mike',
                'last_name' => 'Wilson',
                'email' => 'mike.wilson@assetmanagement.com',
                'username' => 'mike.wilson',
                'password' => bcrypt('password123'),
                'role' => UserRole::TECHNICIAN,
                'department_id' => $itDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0004',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.davis@assetmanagement.com',
                'username' => 'emily.davis',
                'password' => bcrypt('password123'),
                'role' => UserRole::TECHNICIAN,
                'department_id' => $itDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0005',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Brown',
                'email' => 'robert.brown@assetmanagement.com',
                'username' => 'robert.brown',
                'password' => bcrypt('password123'),
                'role' => UserRole::AUDITOR,
                'department_id' => $hrDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0006',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Anderson',
                'email' => 'lisa.anderson@assetmanagement.com',
                'username' => 'lisa.anderson',
                'password' => bcrypt('password123'),
                'role' => UserRole::VIEWER,
                'department_id' => $opsDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0007',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Taylor',
                'email' => 'david.taylor@assetmanagement.com',
                'username' => 'david.taylor',
                'password' => bcrypt('password123'),
                'role' => UserRole::TECHNICIAN,
                'department_id' => $opsDepartment->id,
                'location_id' => $headOffice->id,
                'phone' => '+1-555-0008',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Update department managers
        $johnSmith = User::where('username', 'john.smith')->first();
        $sarahJohnson = User::where('username', 'sarah.johnson')->first();
        
        $itDepartment->update(['manager_id' => $johnSmith->id]);
        $hrDepartment->update(['manager_id' => $sarahJohnson->id]);

        // Create sample assets
        $laptopCategory = Category::where('name', 'Laptops')->first();
        $desktopCategory = Category::where('name', 'Desktops')->first();
        $serverCategory = Category::where('name', 'Servers')->first();
        $printerCategory = Category::where('name', 'Printers')->first();
        $carCategory = Category::where('name', 'Cars')->first();

        $headOffice = Location::where('name', 'Head Office')->first();
        $itDepartment = Location::where('name', 'IT Department')->first();
        $branchOffice = Location::where('name', 'Branch Office')->first();
        $warehouse = Location::where('name', 'Warehouse')->first();

        $itDept = Department::where('code', 'IT')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $opsDept = Department::where('code', 'OPS')->first();

        $sampleAssets = [
            [
                'name' => 'Dell Latitude 7420',
                'serial_number' => 'DL-7420-001',
                'category_id' => $laptopCategory->id,
                'location_id' => $headOffice->id,
                'department_id' => $itDept->id,
                'purchase_date' => '2023-01-15',
                'purchase_cost' => 1500.00,
                'useful_life_years' => 3,
                'depreciation_method' => 'straight_line',
                'status' => 'active',
                'description' => 'Business laptop for IT staff',
                'manufacturer' => 'Dell',
                'model' => 'Latitude 7420',
                'warranty_expiry' => '2025-01-15',
            ],
            [
                'name' => 'HP EliteDesk 800 G9',
                'serial_number' => 'HP-ED-800-001',
                'category_id' => $desktopCategory->id,
                'location_id' => $itDepartment->id,
                'department_id' => $itDept->id,
                'purchase_date' => '2023-03-20',
                'purchase_cost' => 1200.00,
                'useful_life_years' => 5,
                'depreciation_method' => 'straight_line',
                'status' => 'active',
                'description' => 'Desktop computer for server room',
                'manufacturer' => 'HP',
                'model' => 'EliteDesk 800 G9',
                'warranty_expiry' => '2025-03-20',
            ],
            [
                'name' => 'Dell PowerEdge R740',
                'serial_number' => 'PE-R740-001',
                'category_id' => $serverCategory->id,
                'location_id' => $itDepartment->id,
                'department_id' => $itDept->id,
                'purchase_date' => '2022-06-10',
                'purchase_cost' => 8000.00,
                'useful_life_years' => 7,
                'depreciation_method' => 'declining_balance',
                'status' => 'active',
                'description' => 'Main application server',
                'manufacturer' => 'Dell',
                'model' => 'PowerEdge R740',
                'warranty_expiry' => '2024-06-10',
            ],
            [
                'name' => 'Canon ImageRunner 2545',
                'serial_number' => 'CI-2545-001',
                'category_id' => $printerCategory->id,
                'location_id' => $headOffice->id,
                'department_id' => $hrDept->id,
                'purchase_date' => '2023-02-28',
                'purchase_cost' => 2500.00,
                'useful_life_years' => 5,
                'depreciation_method' => 'straight_line',
                'status' => 'active',
                'description' => 'Multifunction printer for HR department',
                'manufacturer' => 'Canon',
                'model' => 'ImageRunner 2545',
                'warranty_expiry' => '2025-02-28',
            ],
            [
                'name' => 'Toyota Camry 2023',
                'serial_number' => 'VIN-1HGBH41JXMN109186',
                'category_id' => $carCategory->id,
                'location_id' => $warehouse->id,
                'department_id' => $opsDept->id,
                'purchase_date' => '2023-01-05',
                'purchase_cost' => 25000.00,
                'useful_life_years' => 5,
                'depreciation_method' => 'declining_balance',
                'status' => 'active',
                'description' => 'Company vehicle for operations',
                'manufacturer' => 'Toyota',
                'model' => 'Camry LE 2023',
                'warranty_expiry' => '2025-01-05',
            ],
            [
                'name' => 'Lenovo ThinkPad X1 Carbon',
                'serial_number' => 'LTP-X1-001',
                'category_id' => $laptopCategory->id,
                'location_id' => $branchOffice->id,
                'department_id' => $opsDept->id,
                'purchase_date' => '2023-04-12',
                'purchase_cost' => 1800.00,
                'useful_life_years' => 3,
                'depreciation_method' => 'straight_line',
                'status' => 'active',
                'description' => 'Laptop for branch office manager',
                'manufacturer' => 'Lenovo',
                'model' => 'ThinkPad X1 Carbon',
                'warranty_expiry' => '2025-04-12',
            ],
            [
                'name' => 'Brother HL-L2350DW',
                'serial_number' => 'BH-HL-001',
                'category_id' => $printerCategory->id,
                'location_id' => $branchOffice->id,
                'department_id' => $opsDept->id,
                'purchase_date' => '2023-05-20',
                'purchase_cost' => 300.00,
                'useful_life_years' => 5,
                'depreciation_method' => 'straight_line',
                'status' => 'active',
                'description' => 'Laser printer for branch office',
                'manufacturer' => 'Brother',
                'model' => 'HL-L2350DW',
                'warranty_expiry' => '2025-05-20',
            ],
            [
                'name' => 'MacBook Pro 16"',
                'serial_number' => 'MBP-16-001',
                'category_id' => $laptopCategory->id,
                'location_id' => $headOffice->id,
                'department_id' => $hrDept->id,
                'purchase_date' => '2023-03-15',
                'purchase_cost' => 2800.00,
                'useful_life_years' => 3,
                'depreciation_method' => 'straight_line',
                'status' => 'under_maintenance',
                'description' => 'High-performance laptop for HR director',
                'manufacturer' => 'Apple',
                'model' => 'MacBook Pro 16" M2 Pro',
                'warranty_expiry' => '2025-03-15',
            ],
        ];

        foreach ($sampleAssets as $assetData) {
            $assetData['current_value'] = $assetData['purchase_cost'];
            Asset::create($assetData);
        }

        $this->command->info('Asset management system seeded successfully!');
        $this->command->info('Created ' . Category::count() . ' categories');
        $this->command->info('Created ' . Location::count() . ' locations');
        $this->command->info('Created ' . Department::count() . ' departments');
        $this->command->info('Created ' . User::count() . ' users');
        $this->command->info('Created ' . Asset::count() . ' sample assets');
        
        $this->command->info('');
        $this->command->info('Default login credentials:');
        $this->command->info('Admin: admin@assetmanagement.com / admin123');
        $this->command->info('Manager: john.smith@assetmanagement.com / password123');
        $this->command->info('Technician: mike.wilson@assetmanagement.com / password123');
        $this->command->info('Auditor: robert.brown@assetmanagement.com / password123');
        $this->command->info('Viewer: lisa.anderson@assetmanagement.com / password123');
    }
}
