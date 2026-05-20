<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
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

        $suppliers = [
            [
                'id' => Str::uuid(),
                'name' => 'Grainger Industrial Supply',
                'description' => 'Industrial supply company providing maintenance, repair, and operations products',
                'contact_person' => 'John Davis',
                'email' => 'john.davis@grainger.com',
                'phone' => '+1-800-472-4643',
                'address' => '100 Grainger Parkway',
                'city' => 'Lake Forest',
                'state' => 'IL',
                'postal_code' => '60045',
                'country' => 'USA',
                'website' => 'https://www.grainger.com',
                'tax_id' => 'GRAINGER-TAX-001',
                'payment_terms' => 'NET 30',
                'delivery_terms' => 'FOB Destination',
                'lead_time_days' => 7,
                'minimum_order_value' => 100.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_manufacturer' => false,
                'notes' => 'Primary supplier for MRO supplies',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Fastenal Company',
                'description' => 'Industrial and construction supplies distributor',
                'contact_person' => 'Sarah Wilson',
                'email' => 'sarah.wilson@fastenal.com',
                'phone' => '+1-800-872-7835',
                'address' => '200 Fastenal Drive',
                'city' => 'Winona',
                'state' => 'MN',
                'postal_code' => '55987',
                'country' => 'USA',
                'website' => 'https://www.fastenal.com',
                'tax_id' => 'FASTENAL-TAX-002',
                'payment_terms' => 'NET 30',
                'delivery_terms' => 'FOB Origin',
                'lead_time_days' => 5,
                'minimum_order_value' => 50.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_manufacturer' => false,
                'notes' => 'Fast delivery for critical items',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'MSC Industrial Supply',
                'description' => 'Metalworking and MRO supplies distributor',
                'contact_person' => 'Michael Brown',
                'email' => 'michael.brown@msc.com',
                'phone' => '+1-800-645-7270',
                'address' => '75 Mall Road',
                'city' => 'Melville',
                'state' => 'NY',
                'postal_code' => '11747',
                'country' => 'USA',
                'website' => 'https://www.mscdirect.com',
                'tax_id' => 'MSC-TAX-003',
                'payment_terms' => 'NET 45',
                'delivery_terms' => 'FOB Destination',
                'lead_time_days' => 10,
                'minimum_order_value' => 75.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_manufacturer' => false,
                'notes' => 'Specialized metalworking supplies',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Siemens AG',
                'description' => 'German multinational conglomerate company',
                'contact_person' => 'Hans Mueller',
                'email' => 'hans.mueller@siemens.com',
                'phone' => '+49-180-533-7878',
                'address' => 'Werner-von-Siemens-Straße 1',
                'city' => 'München',
                'state' => 'Bavaria',
                'postal_code' => '80333',
                'country' => 'Germany',
                'website' => 'https://www.siemens.com',
                'tax_id' => 'SIEMENS-TAX-004',
                'payment_terms' => 'NET 60',
                'delivery_terms' => 'EXW',
                'lead_time_days' => 21,
                'minimum_order_value' => 500.00,
                'currency' => 'EUR',
                'is_active' => true,
                'is_manufacturer' => true,
                'notes' => 'Premium automation equipment manufacturer',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'ABB Ltd',
                'description' => 'Swedish multinational corporation specializing in robotics and power',
                'contact_person' => 'Anna Andersson',
                'email' => 'anna.andersson@abb.com',
                'phone' => '+41-44-317-71-11',
                'address' => 'Affolternstrasse 44',
                'city' => 'Zürich',
                'state' => 'Zürich',
                'postal_code' => '8050',
                'country' => 'Switzerland',
                'website' => 'https://www.abb.com',
                'tax_id' => 'ABB-TAX-005',
                'payment_terms' => 'NET 45',
                'delivery_terms' => 'EXW',
                'lead_time_days' => 18,
                'minimum_order_value' => 300.00,
                'currency' => 'CHF',
                'is_active' => true,
                'is_manufacturer' => true,
                'notes' => 'High-quality electrical equipment',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Supplier::insert($suppliers);
        
        // Re-enable foreign key constraints
        \DB::statement('PRAGMA foreign_keys = ON');
    }
}
