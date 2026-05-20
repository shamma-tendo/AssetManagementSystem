<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'id' => Str::uuid(),
                'name' => 'Production',
                'code' => 'PROD-001',
                'description' => 'Manufacturing and production operations',
                'manager_id' => null, // Will be set after users are created
                'budget_code' => 'BUDG-001',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Maintenance',
                'code' => 'MAINT-001',
                'description' => 'Equipment maintenance and repair',
                'manager_id' => null,
                'budget_code' => 'BUDG-002',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Quality Control',
                'code' => 'QUAL-001',
                'description' => 'Quality assurance and testing',
                'manager_id' => null,
                'budget_code' => 'BUDG-003',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Engineering',
                'code' => 'ENG-001',
                'description' => 'Design and engineering services',
                'manager_id' => null,
                'budget_code' => 'BUDG-004',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Logistics',
                'code' => 'LOG-001',
                'description' => 'Supply chain and inventory management',
                'manager_id' => null,
                'budget_code' => 'BUDG-005',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Safety',
                'code' => 'SAFE-001',
                'description' => 'Workplace safety and compliance',
                'manager_id' => null,
                'budget_code' => 'BUDG-006',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'IT',
                'code' => 'IT-001',
                'description' => 'Information technology and systems',
                'manager_id' => null,
                'budget_code' => 'BUDG-007',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Human Resources',
                'code' => 'HR-001',
                'description' => 'Personnel and employee management',
                'manager_id' => null,
                'budget_code' => 'BUDG-008',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Finance',
                'code' => 'FIN-001',
                'description' => 'Financial management and accounting',
                'manager_id' => null,
                'budget_code' => 'BUDG-009',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Facilities',
                'code' => 'FAC-001',
                'description' => 'Building and facilities management',
                'manager_id' => null,
                'budget_code' => 'BUDG-010',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Department::insert($departments);
    }
}
