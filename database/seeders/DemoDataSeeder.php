<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Location;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoDataSeeder
 * 
 * Creates comprehensive demo data for all platform scenarios:
 * - Multiple industry types
 * - Multiple roles per organization
 * - Sample assets and workflows
 * - Realistic status scenarios
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles first
        $this->createRoles();

        // Create demo organizations by industry type
        $this->createHospitalDemo();
        $this->createSchoolDemo();
        $this->createCorporateDemo();
        $this->createRetailDemo();
        $this->createManufacturingDemo();
        $this->createHouseholdDemo();
    }

    /**
     * Create all roles
     */
    private function createRoles(): void
    {
        $roles = [
            ['name' => 'CEO', 'description' => 'Chief Executive Officer'],
            ['name' => 'CFO', 'description' => 'Chief Financial Officer'],
            ['name' => 'Asset Manager', 'description' => 'Manages asset requests and distribution'],
            ['name' => 'Staff', 'description' => 'Regular staff member'],
            ['name' => 'Admin', 'description' => 'System administrator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }

    /**
     * Create Hospital Demo Organization
     */
    private function createHospitalDemo(): void
    {
        $hospital = Organization::create([
            'name' => 'Metropolitan Medical Center',
            'email' => 'admin@metromed.hospital',
            'type' => 'company',
            'industry_type' => 'hospital',
            'phone' => '(555) 123-4567',
            'address' => '123 Medical Plaza Drive',
            'city' => 'Boston',
            'state' => 'MA',
            'postal_code' => '02101',
            'country' => 'USA',
            'description' => 'Leading medical facility with 200+ beds',
            'subscription_plan' => 'enterprise',
            'is_active' => true,
            'industry_metadata' => json_encode([
                'bed_count' => 250,
                'departments' => ['ER', 'ICU', 'OR', 'Cardiology', 'Pediatrics']
            ])
        ]);

        // Create users
        $ceo = User::create([
            'name' => 'Dr. Sarah Johnson',
            'email' => 'sarah@metromed.hospital',
            'password' => Hash::make('password'),
            'organization_id' => $hospital->id,
            'role_id' => Role::where('name', 'CEO')->first()->id,
        ]);

        $manager = User::create([
            'name' => 'James Miller',
            'email' => 'james@metromed.hospital',
            'password' => Hash::make('password'),
            'organization_id' => $hospital->id,
            'role_id' => Role::where('name', 'Asset Manager')->first()->id,
        ]);

        $staff = User::create([
            'name' => 'Maria Garcia',
            'email' => 'maria@metromed.hospital',
            'password' => Hash::make('password'),
            'organization_id' => $hospital->id,
            'role_id' => Role::where('name', 'Staff')->first()->id,
        ]);

        // Create locations
        Location::create(['organization_id' => $hospital->id, 'name' => 'Emergency Department']);
        Location::create(['organization_id' => $hospital->id, 'name' => 'ICU']);
        Location::create(['organization_id' => $hospital->id, 'name' => 'Surgery Suite']);

        // Create categories
        $equipment = Category::firstOrCreate(
            ['name' => 'Medical Equipment'],
            ['description' => 'Hospital medical equipment']
        );

        // Create sample assets
        Asset::create([
            'organization_id' => $hospital->id,
            'category_id' => $equipment->id,
            'name' => 'Ventilator Unit',
            'serial_number' => 'VENT-2024-001',
            'status' => 'active',
            'estimated_value' => 45000,
            'purchase_date' => now()->subMonths(6),
            'created_by' => $manager->id,
        ]);

        // Create asset request
        AssetRequest::create([
            'organization_id' => $hospital->id,
            'requested_by' => $manager->id,
            'title' => 'Additional Monitoring Equipment',
            'description' => 'Need 5 more patient monitors for ICU expansion',
            'quantity' => 5,
            'asset_type' => 'Medical Equipment',
            'estimated_cost' => 75000,
            'status' => 'pending',
        ]);
    }

    /**
     * Create School Demo Organization
     */
    private function createSchoolDemo(): void
    {
        $school = Organization::create([
            'name' => 'Lincoln High School',
            'email' => 'admin@lincolnhs.edu',
            'type' => 'company',
            'industry_type' => 'school',
            'phone' => '(555) 234-5678',
            'address' => '456 Education Way',
            'city' => 'Chicago',
            'state' => 'IL',
            'postal_code' => '60601',
            'country' => 'USA',
            'description' => 'Comprehensive high school with 1500+ students',
            'subscription_plan' => 'professional',
            'is_active' => true,
            'industry_metadata' => json_encode([
                'student_count' => 1500,
                'departments' => ['Science', 'Math', 'English', 'Arts', 'PE']
            ])
        ]);

        // Create users
        $principal = User::create([
            'name' => 'Robert Chen',
            'email' => 'robert@lincolnhs.edu',
            'password' => Hash::make('password'),
            'organization_id' => $school->id,
            'role_id' => Role::where('name', 'CEO')->first()->id,
        ]);

        $manager = User::create([
            'name' => 'Emily Rodriguez',
            'email' => 'emily@lincolnhs.edu',
            'password' => Hash::make('password'),
            'organization_id' => $school->id,
            'role_id' => Role::where('name', 'Asset Manager')->first()->id,
        ]);

        $teacher = User::create([
            'name' => 'David Lee',
            'email' => 'david@lincolnhs.edu',
            'password' => Hash::make('password'),
            'organization_id' => $school->id,
            'role_id' => Role::where('name', 'Staff')->first()->id,
        ]);

        // Create locations
        Location::create(['organization_id' => $school->id, 'name' => 'Science Lab']);
        Location::create(['organization_id' => $school->id, 'name' => 'Computer Room']);
        Location::create(['organization_id' => $school->id, 'name' => 'Library']);
    }

    /**
     * Create Corporate Demo Organization
     */
    private function createCorporateDemo(): void
    {
        $company = Organization::create([
            'name' => 'TechCorp Industries',
            'email' => 'admin@techcorp.com',
            'type' => 'company',
            'industry_type' => 'corporate',
            'phone' => '(555) 345-6789',
            'address' => '789 Innovation Drive',
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94105',
            'country' => 'USA',
            'description' => 'Technology company with 500 employees',
            'subscription_plan' => 'enterprise',
            'is_active' => true,
            'industry_metadata' => json_encode([
                'employee_count' => 500,
                'departments' => ['Engineering', 'Sales', 'Marketing', 'HR', 'Finance']
            ])
        ]);

        // Create users
        $cfo = User::create([
            'name' => 'Patricia Williams',
            'email' => 'patricia@techcorp.com',
            'password' => Hash::make('password'),
            'organization_id' => $company->id,
            'role_id' => Role::where('name', 'CFO')->first()->id,
        ]);

        $manager = User::create([
            'name' => 'Marcus Thompson',
            'email' => 'marcus@techcorp.com',
            'password' => Hash::make('password'),
            'organization_id' => $company->id,
            'role_id' => Role::where('name', 'Asset Manager')->first()->id,
        ]);

        $staff1 = User::create([
            'name' => 'Lisa Anderson',
            'email' => 'lisa@techcorp.com',
            'password' => Hash::make('password'),
            'organization_id' => $company->id,
            'role_id' => Role::where('name', 'Staff')->first()->id,
        ]);

        $staff2 = User::create([
            'name' => 'Michael Zhang',
            'email' => 'michael@techcorp.com',
            'password' => Hash::make('password'),
            'organization_id' => $company->id,
            'role_id' => Role::where('name', 'Staff')->first()->id,
        ]);

        // Create locations
        Location::create(['organization_id' => $company->id, 'name' => 'Main Office']);
        Location::create(['organization_id' => $company->id, 'name' => 'Engineering Lab']);
        Location::create(['organization_id' => $company->id, 'name' => 'Conference Room A']);

        // Create categories
        $it = Category::firstOrCreate(
            ['name' => 'IT Equipment'],
            ['description' => 'Computer and IT equipment']
        );

        // Create sample assets
        Asset::create([
            'organization_id' => $company->id,
            'category_id' => $it->id,
            'name' => 'MacBook Pro 16"',
            'serial_number' => 'MBP-2024-001',
            'status' => 'active',
            'estimated_value' => 2500,
            'purchase_date' => now()->subMonths(3),
            'created_by' => $manager->id,
        ]);

        // Create assignment
        $asset = Asset::create([
            'organization_id' => $company->id,
            'category_id' => $it->id,
            'name' => 'Dell Monitor 27"',
            'serial_number' => 'DELL-MON-002',
            'status' => 'active',
            'estimated_value' => 350,
            'purchase_date' => now()->subMonths(2),
            'created_by' => $manager->id,
        ]);

        AssetAssignment::create([
            'organization_id' => $company->id,
            'asset_id' => $asset->id,
            'assigned_to' => $staff1->id,
            'assigned_by' => $manager->id,
            'status' => 'active',
            'assigned_at' => now()->subDays(30),
        ]);

        // Create condition report
        AssetConditionReport::create([
            'organization_id' => $company->id,
            'asset_id' => $asset->id,
            'reported_by' => $staff1->id,
            'condition_status' => 'in_use',
            'notes' => 'Monitor is working perfectly, very satisfied with the setup',
            'status' => 'resolved',
        ]);
    }

    /**
     * Create Retail Demo Organization
     */
    private function createRetailDemo(): void
    {
        $retail = Organization::create([
            'name' => 'QuickShop Retail',
            'email' => 'admin@quickshop.retail',
            'type' => 'company',
            'industry_type' => 'retail',
            'phone' => '(555) 456-7890',
            'address' => '321 Commerce Street',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'description' => 'Multi-location retail store chain',
            'subscription_plan' => 'professional',
            'is_active' => true,
            'industry_metadata' => json_encode([
                'store_count' => 15,
                'locations' => ['Manhattan', 'Brooklyn', 'Queens']
            ])
        ]);

        // Create users
        $manager = User::create([
            'name' => 'Jessica Brown',
            'email' => 'jessica@quickshop.retail',
            'password' => Hash::make('password'),
            'organization_id' => $retail->id,
            'role_id' => Role::where('name', 'Asset Manager')->first()->id,
        ]);
    }

    /**
     * Create Manufacturing Demo Organization
     */
    private function createManufacturingDemo(): void
    {
        $factory = Organization::create([
            'name' => 'Advanced Manufacturing Corp',
            'email' => 'admin@advmfg.factory',
            'type' => 'company',
            'industry_type' => 'manufacturing',
            'phone' => '(555) 567-8901',
            'address' => '654 Industrial Avenue',
            'city' => 'Detroit',
            'state' => 'MI',
            'postal_code' => '48201',
            'country' => 'USA',
            'description' => 'Advanced manufacturing and assembly facility',
            'subscription_plan' => 'enterprise',
            'is_active' => true,
            'industry_metadata' => json_encode([
                'production_lines' => 5,
                'facilities' => ['Assembly', 'Quality Control', 'Packaging']
            ])
        ]);

        // Create users
        $manager = User::create([
            'name' => 'Anthony Davis',
            'email' => 'anthony@advmfg.factory',
            'password' => Hash::make('password'),
            'organization_id' => $factory->id,
            'role_id' => Role::where('name', 'Asset Manager')->first()->id,
        ]);
    }

    /**
     * Create Household Demo Organization
     */
    private function createHouseholdDemo(): void
    {
        $household = Organization::create([
            'name' => 'John Smith Household',
            'email' => 'john@household.personal',
            'type' => 'household',
            'industry_type' => 'household',
            'phone' => '(555) 678-9012',
            'address' => '999 Maple Street',
            'city' => 'Portland',
            'state' => 'OR',
            'postal_code' => '97201',
            'country' => 'USA',
            'description' => 'Personal household asset tracking',
            'subscription_plan' => 'basic',
            'is_active' => true,
            'next_of_kin_name' => 'Jane Smith',
            'next_of_kin_phone' => '(555) 678-9013',
            'next_of_kin_email' => 'jane@household.personal',
            'next_of_kin_relationship' => 'Spouse',
        ]);

        // Create user
        $owner = User::create([
            'name' => 'John Smith',
            'email' => 'john@household.personal',
            'password' => Hash::make('password'),
            'organization_id' => $household->id,
            'role_id' => Role::where('name', 'Staff')->first()->id,
        ]);

        // Create locations
        Location::create(['organization_id' => $household->id, 'name' => 'Living Room']);
        Location::create(['organization_id' => $household->id, 'name' => 'Garage']);
        Location::create(['organization_id' => $household->id, 'name' => 'Bedroom']);

        // Create categories
        $electronics = Category::firstOrCreate(
            ['name' => 'Electronics'],
            ['description' => 'Electronic devices']
        );
        $furniture = Category::firstOrCreate(
            ['name' => 'Furniture'],
            ['description' => 'Furniture items']
        );

        // Create sample assets
        Asset::create([
            'organization_id' => $household->id,
            'category_id' => $electronics->id,
            'name' => 'Television 65" Samsung',
            'serial_number' => 'SAM-TV-89012',
            'status' => 'active',
            'estimated_value' => 800,
            'location_id' => Location::where('organization_id', $household->id)->first()->id,
            'purchase_date' => now()->subYears(2),
            'created_by' => $owner->id,
        ]);

        Asset::create([
            'organization_id' => $household->id,
            'category_id' => $furniture->id,
            'name' => 'Leather Sofa',
            'serial_number' => 'SOFA-LEATHER-001',
            'status' => 'active',
            'estimated_value' => 1200,
            'purchase_date' => now()->subYears(3),
            'created_by' => $owner->id,
        ]);
    }
}
