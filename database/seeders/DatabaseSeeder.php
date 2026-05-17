<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users first
        $this->call([
            UserSeeder::class,
        ]);

        // Create reference data
        $this->call([
            CategorySeeder::class,
            LocationSeeder::class,
            DepartmentSeeder::class,
            PartCategorySeeder::class,
            SupplierSeeder::class,
        ]);

        // Create main entities
        $this->call([
            AssetSeeder::class,
            SensorSeeder::class,
        ]);

        // Create dependent entities
        $this->call([
            WorkOrderSeeder::class,
            MaintenanceScheduleSeeder::class,
            InspectionSeeder::class,
            SensorReadingSeeder::class,
        ]);

        // Create additional entities
        $this->call([
            PartSeeder::class,
            PurchaseOrderSeeder::class,
            DepreciationMethodSeeder::class,
            SensorTypeSeeder::class,
        ]);
    }
}
