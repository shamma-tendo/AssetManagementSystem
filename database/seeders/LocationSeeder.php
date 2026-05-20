<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'id' => Str::uuid(),
                'name' => 'Main Production Floor',
                'code' => 'MPF-001',
                'address' => '123 Industrial Way',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Assembly Line 1',
                'code' => 'ASL-001',
                'address' => '123 Industrial Way',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Assembly Line 2',
                'code' => 'ASL-002',
                'address' => '123 Industrial Way',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Machine Shop',
                'code' => 'MCH-001',
                'address' => '456 Machine Avenue',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Maintenance Workshop',
                'code' => 'MNT-001',
                'address' => '456 Machine Avenue',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Warehouse',
                'code' => 'WRH-001',
                'address' => '789 Storage Boulevard',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Quality Control Lab',
                'code' => 'QCL-001',
                'address' => '123 Industrial Way',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Paint Shop',
                'code' => 'PNT-001',
                'address' => '321 Finishing Drive',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Loading Dock',
                'code' => 'LDK-001',
                'address' => '789 Storage Boulevard',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Server Room',
                'code' => 'SVR-001',
                'address' => '123 Industrial Way',
                'city' => 'Manufacturing City',
                'state' => 'MC',
                'postal_code' => '12345',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Location::insert($locations);
    }
}
