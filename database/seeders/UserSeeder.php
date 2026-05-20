<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'id' => Str::uuid(),
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@company.com',
                'username' => 'jsmith',
                'phone' => '+1-555-0101',
                'role' => 'admin',
                'department_id' => null, // Will be set after departments are created
                'location_id' => null, // Will be set after locations are created
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(2),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
                'created_at' => now()->subDays(365),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@company.com',
                'username' => 'sjohnson',
                'phone' => '+1-555-0102',
                'role' => 'manager',
                'department_id' => null,
                'location_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(4),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
                'created_at' => now()->subDays(300),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'first_name' => 'Mike',
                'last_name' => 'Wilson',
                'email' => 'mike.wilson@company.com',
                'username' => 'mwilson',
                'phone' => '+1-555-0103',
                'role' => 'technician',
                'department_id' => null,
                'location_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subMinutes(30),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
                'created_at' => now()->subDays(200),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.davis@company.com',
                'username' => 'edavis',
                'phone' => '+1-555-0104',
                'role' => 'auditor',
                'department_id' => null,
                'location_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subDays(1),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
                'created_at' => now()->subDays(180),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'first_name' => 'Robert',
                'last_name' => 'Brown',
                'email' => 'robert.brown@company.com',
                'username' => 'rbrown',
                'phone' => '+1-555-0105',
                'role' => 'viewer',
                'department_id' => null,
                'location_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(6),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
                'created_at' => now()->subDays(150),
                'updated_at' => now(),
            ],
        ];

        // Create additional users using factory
        foreach (range(1, 10) as $i) {
            $users[] = [
                'id' => Str::uuid(),
                'first_name' => $this->generateFirstName(),
                'last_name' => $this->generateLastName(),
                'email' => 'user' . $i . '@company.com',
                'username' => 'user' . $i,
                'phone' => '+1-555-' . str_pad(1000 + $i, 4, '0', STR_PAD_LEFT),
                'role' => $this->getRandomRole(),
                'department_id' => null,
                'location_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(rand(1, 24)),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
                'created_at' => now()->subDays(rand(30, 365)),
                'updated_at' => now(),
            ];
        }

        User::insert($users);
    }

    private function generateFirstName(): string
    {
        $firstNames = [
            'James', 'Michael', 'David', 'William', 'Richard', 'Joseph', 'Thomas', 'Christopher',
            'Charles', 'Daniel', 'Matthew', 'Anthony', 'Mark', 'Donald', 'Steven', 'Paul',
            'Andrew', 'Joshua', 'Kevin', 'Brian', 'George', 'Timothy', 'Ronald', 'Edward',
            'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica',
            'Sarah', 'Karen', 'Nancy', 'Lisa', 'Betty', 'Helen', 'Sandra', 'Donna'
        ];

        return $firstNames[array_rand($firstNames)];
    }

    private function generateLastName(): string
    {
        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Garcia',
            'Rodriguez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
            'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez',
            'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott'
        ];

        return $lastNames[array_rand($lastNames)];
    }

    private function getRandomRole(): string
    {
        $roles = ['admin', 'manager', 'technician', 'auditor', 'viewer'];
        $weights = [5, 15, 40, 10, 30]; // Weighted distribution
        
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($roles as $index => $role) {
            $cumulative += $weights[$index];
            if ($rand <= $cumulative) {
                return $role;
            }
        }

        return 'viewer';
    }
}
