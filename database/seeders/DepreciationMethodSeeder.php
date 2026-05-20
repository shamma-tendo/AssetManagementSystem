<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class DepreciationMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $userId = $user ? $user->id : null;

        $methods = [
            [
                'id' => Str::uuid(),
                'name' => 'Straight Line',
                'code' => 'SL',
                'description' => 'Equal depreciation amount each year over the useful life',
                'formula' => '(Cost - Salvage Value) / Useful Life',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Declining Balance',
                'code' => 'DB',
                'description' => 'Higher depreciation in early years, decreasing over time',
                'formula' => 'Book Value × (2 / Useful Life)',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Sum of Years Digits',
                'code' => 'SYD',
                'description' => 'Accelerated depreciation based on sum of years digits',
                'formula' => '(Remaining Life / Sum of Years) × (Cost - Salvage)',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Units of Production',
                'code' => 'UOP',
                'description' => 'Depreciation based on actual usage or production',
                'formula' => '(Cost - Salvage) × (Units Produced / Total Units)',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Modified Accelerated Cost Recovery',
                'code' => 'MACRS',
                'description' => 'Tax-based depreciation method with specific schedules',
                'formula' => 'Based on IRS MACRS tables',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('depreciation_methods')->insert($methods);
    }
}
