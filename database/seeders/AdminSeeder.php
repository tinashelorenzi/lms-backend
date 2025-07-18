<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminProfile;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'user' => [
                    'name' => 'Admin User',
                    'email' => 'admin@lms.com',
                    'phone' => '+1234567890',
                    'user_type' => UserType::ADMIN,
                    'is_active' => true,
                    'date_of_birth' => '1985-01-15',
                    'gender' => 'male',
                    'address' => '123 Admin St, City, State 12345',
                    'password' => Hash::make('admin123'),
                ],
                'profile' => [
                    'employee_id' => 'ADM001',
                    'position' => 'System Administrator',
                    'department' => 'IT Department',
                    'hire_date' => '2020-01-15',
                    'salary' => 75000.00,
                ],
            ],
            [
                'user' => [
                    'name' => 'Sarah Wilson',
                    'email' => 'sarah.wilson@lms.com',
                    'phone' => '+1234567891',
                    'user_type' => UserType::ADMIN,
                    'is_active' => true,
                    'date_of_birth' => '1988-06-20',
                    'gender' => 'female',
                    'address' => '456 Admin Ave, City, State 12345',
                    'password' => Hash::make('admin123'),
                ],
                'profile' => [
                    'employee_id' => 'ADM002',
                    'position' => 'Academic Administrator',
                    'department' => 'Academic Affairs',
                    'hire_date' => '2021-03-10',
                    'salary' => 65000.00,
                ],
            ],
            [
                'user' => [
                    'name' => 'Michael Brown',
                    'email' => 'michael.brown@lms.com',
                    'phone' => '+1234567892',
                    'user_type' => UserType::ADMIN,
                    'is_active' => true,
                    'date_of_birth' => '1982-12-05',
                    'gender' => 'male',
                    'address' => '789 Admin Rd, City, State 12345',
                    'password' => Hash::make('admin123'),
                ],
                'profile' => [
                    'employee_id' => 'ADM003',
                    'position' => 'Student Affairs Administrator',
                    'department' => 'Student Affairs',
                    'hire_date' => '2019-08-22',
                    'salary' => 70000.00,
                ],
            ],
        ];

        foreach ($admins as $adminData) {
            $user = User::create($adminData['user']);
            $user->adminProfile()->create($adminData['profile']);
        }
    }
}
