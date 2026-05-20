<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PartCategory;
use App\Models\User;
use Illuminate\Support\Str;

class PartCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints temporarily for seeding
        \DB::statement('PRAGMA foreign_keys = OFF');
        
        // Get the first user ID to use as creator
        $firstUser = User::first();
        $userId = $firstUser ? $firstUser->id : '00000000-0000-0000-0000-000000000000';

        $categories = [
            [
                'id' => Str::uuid(),
                'name' => 'mechanical',
                'description' => 'Mechanical parts and components',
                'parent_category_id' => null,
                'code' => 'MEC',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'electrical',
                'description' => 'Electrical parts and components',
                'parent_category_id' => null,
                'code' => 'ELC',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'hydraulic',
                'description' => 'Hydraulic parts and components',
                'parent_category_id' => null,
                'code' => 'HYD',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'pneumatic',
                'description' => 'Pneumatic parts and components',
                'parent_category_id' => null,
                'code' => 'PNU',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'consumables',
                'description' => 'Consumable materials and supplies',
                'parent_category_id' => null,
                'code' => 'CON',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'bearings',
                'description' => 'Bearings and bearing components',
                'parent_category_id' => null,
                'code' => 'BRG',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'seals',
                'description' => 'Seals and gaskets',
                'parent_category_id' => null,
                'code' => 'SEL',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'filters',
                'description' => 'Filters and filtration components',
                'parent_category_id' => null,
                'code' => 'FLT',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'lubricants',
                'description' => 'Lubricants and fluids',
                'parent_category_id' => null,
                'code' => 'LUB',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'fasteners',
                'description' => 'Fasteners and connectors',
                'parent_category_id' => null,
                'code' => 'FST',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        PartCategory::insert($categories);
        
        // Re-enable foreign key constraints
        \DB::statement('PRAGMA foreign_keys = ON');
    }
}
