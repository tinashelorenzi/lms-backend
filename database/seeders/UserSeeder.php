<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some general users for testing
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1234567890',
                'user_type' => UserType::STUDENT,
                'is_active' => true,
                'date_of_birth' => '1995-05-15',
                'gender' => 'male',
                'address' => '123 Main St, City, State 12345',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '+1234567891',
                'user_type' => UserType::STUDENT,
                'is_active' => true,
                'date_of_birth' => '1998-08-22',
                'gender' => 'female',
                'address' => '456 Oak Ave, City, State 12345',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob.johnson@example.com',
                'phone' => '+1234567892',
                'user_type' => UserType::STUDENT,
                'is_active' => true,
                'date_of_birth' => '1997-03-10',
                'gender' => 'male',
                'address' => '789 Pine Rd, City, State 12345',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
