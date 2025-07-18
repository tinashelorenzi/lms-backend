<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherProfile;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'user' => [
                    'name' => 'Dr. Emily Johnson',
                    'email' => 'emily.johnson@lms.com',
                    'phone' => '+1234567890',
                    'user_type' => UserType::TEACHER,
                    'is_active' => true,
                    'date_of_birth' => '1980-03-15',
                    'gender' => 'female',
                    'address' => '123 Teacher St, City, State 12345',
                    'password' => Hash::make('teacher123'),
                ],
                'profile' => [
                    'employee_id' => 'TCH001',
                    'department' => 'Computer Science',
                    'qualification' => 'Ph.D. in Computer Science',
                    'specialization' => 'Artificial Intelligence',
                    'years_of_experience' => 8,
                    'hire_date' => '2018-09-01',
                    'salary' => 85000.00,
                ],
            ],
            [
                'user' => [
                    'name' => 'Prof. David Chen',
                    'email' => 'david.chen@lms.com',
                    'phone' => '+1234567891',
                    'user_type' => UserType::TEACHER,
                    'is_active' => true,
                    'date_of_birth' => '1975-07-22',
                    'gender' => 'male',
                    'address' => '456 Teacher Ave, City, State 12345',
                    'password' => Hash::make('teacher123'),
                ],
                'profile' => [
                    'employee_id' => 'TCH002',
                    'department' => 'Mathematics',
                    'qualification' => 'Ph.D. in Mathematics',
                    'specialization' => 'Applied Mathematics',
                    'years_of_experience' => 12,
                    'hire_date' => '2015-01-15',
                    'salary' => 90000.00,
                ],
            ],
            [
                'user' => [
                    'name' => 'Dr. Lisa Rodriguez',
                    'email' => 'lisa.rodriguez@lms.com',
                    'phone' => '+1234567892',
                    'user_type' => UserType::TEACHER,
                    'is_active' => true,
                    'date_of_birth' => '1982-11-08',
                    'gender' => 'female',
                    'address' => '789 Teacher Rd, City, State 12345',
                    'password' => Hash::make('teacher123'),
                ],
                'profile' => [
                    'employee_id' => 'TCH003',
                    'department' => 'English Literature',
                    'qualification' => 'Ph.D. in English Literature',
                    'specialization' => 'Modern Literature',
                    'years_of_experience' => 6,
                    'hire_date' => '2020-03-01',
                    'salary' => 75000.00,
                ],
            ],
            [
                'user' => [
                    'name' => 'Prof. Robert Kim',
                    'email' => 'robert.kim@lms.com',
                    'phone' => '+1234567893',
                    'user_type' => UserType::TEACHER,
                    'is_active' => true,
                    'date_of_birth' => '1978-04-12',
                    'gender' => 'male',
                    'address' => '321 Teacher Blvd, City, State 12345',
                    'password' => Hash::make('teacher123'),
                ],
                'profile' => [
                    'employee_id' => 'TCH004',
                    'department' => 'Physics',
                    'qualification' => 'Ph.D. in Physics',
                    'specialization' => 'Quantum Mechanics',
                    'years_of_experience' => 10,
                    'hire_date' => '2016-08-15',
                    'salary' => 88000.00,
                ],
            ],
        ];

        foreach ($teachers as $teacherData) {
            $user = User::create($teacherData['user']);
            $user->teacherProfile()->create($teacherData['profile']);
        }
    }
}
