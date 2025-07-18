<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\StudentProfile;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            [
                'user' => [
                    'name' => 'Alex Thompson',
                    'email' => 'alex.thompson@lms.edu',
                    'phone' => '+1234567890',
                    'user_type' => UserType::STUDENT,
                    'is_active' => true,
                    'date_of_birth' => '2000-05-15',
                    'gender' => 'male',
                    'address' => '123 Student St, City, State 12345',
                    'password' => Hash::make('student123'),
                ],
                'profile' => [
                    'student_id' => 'STU001',
                    'enrollment_date' => '2023-09-01',
                    'major' => 'Computer Science',
                    'class_year' => 'Sophomore',
                    'gpa' => 3.75,
                    'parent_name' => 'John Thompson',
                    'parent_phone' => '+1234567890',
                    'parent_email' => 'john.thompson@email.com',
                    'emergency_contact' => 'Emergency: +1234567890',
                ],
            ],
            [
                'user' => [
                    'name' => 'Maria Garcia',
                    'email' => 'maria.garcia@lms.edu',
                    'phone' => '+1234567891',
                    'user_type' => UserType::STUDENT,
                    'is_active' => true,
                    'date_of_birth' => '2001-08-22',
                    'gender' => 'female',
                    'address' => '456 Student Ave, City, State 12345',
                    'password' => Hash::make('student123'),
                ],
                'profile' => [
                    'student_id' => 'STU002',
                    'enrollment_date' => '2023-09-01',
                    'major' => 'English Literature',
                    'class_year' => 'Freshman',
                    'gpa' => 3.85,
                    'parent_name' => 'Carlos Garcia',
                    'parent_phone' => '+1234567891',
                    'parent_email' => 'carlos.garcia@email.com',
                    'emergency_contact' => 'Emergency: +1234567891',
                ],
            ],
            [
                'user' => [
                    'name' => 'James Wilson',
                    'email' => 'james.wilson@lms.edu',
                    'phone' => '+1234567892',
                    'user_type' => UserType::STUDENT,
                    'is_active' => true,
                    'date_of_birth' => '1999-12-10',
                    'gender' => 'male',
                    'address' => '789 Student Rd, City, State 12345',
                    'password' => Hash::make('student123'),
                ],
                'profile' => [
                    'student_id' => 'STU003',
                    'enrollment_date' => '2022-09-01',
                    'major' => 'Physics',
                    'class_year' => 'Junior',
                    'gpa' => 3.60,
                    'parent_name' => 'Robert Wilson',
                    'parent_phone' => '+1234567892',
                    'parent_email' => 'robert.wilson@email.com',
                    'emergency_contact' => 'Emergency: +1234567892',
                ],
            ],
            [
                'user' => [
                    'name' => 'Sarah Lee',
                    'email' => 'sarah.lee@lms.edu',
                    'phone' => '+1234567893',
                    'user_type' => UserType::STUDENT,
                    'is_active' => true,
                    'date_of_birth' => '2000-03-18',
                    'gender' => 'female',
                    'address' => '321 Student Blvd, City, State 12345',
                    'password' => Hash::make('student123'),
                ],
                'profile' => [
                    'student_id' => 'STU004',
                    'enrollment_date' => '2023-09-01',
                    'major' => 'Mathematics',
                    'class_year' => 'Sophomore',
                    'gpa' => 3.90,
                    'parent_name' => 'David Lee',
                    'parent_phone' => '+1234567893',
                    'parent_email' => 'david.lee@email.com',
                    'emergency_contact' => 'Emergency: +1234567893',
                ],
            ],
            [
                'user' => [
                    'name' => 'Michael Chen',
                    'email' => 'michael.chen@lms.edu',
                    'phone' => '+1234567894',
                    'user_type' => UserType::STUDENT,
                    'is_active' => true,
                    'date_of_birth' => '2001-07-05',
                    'gender' => 'male',
                    'address' => '654 Student Ln, City, State 12345',
                    'password' => Hash::make('student123'),
                ],
                'profile' => [
                    'student_id' => 'STU005',
                    'enrollment_date' => '2023-09-01',
                    'major' => 'Computer Science',
                    'class_year' => 'Freshman',
                    'gpa' => 3.70,
                    'parent_name' => 'William Chen',
                    'parent_phone' => '+1234567894',
                    'parent_email' => 'william.chen@email.com',
                    'emergency_contact' => 'Emergency: +1234567894',
                ],
            ],
        ];

        foreach ($students as $studentData) {
            $user = User::create($studentData['user']);
            $user->studentProfile()->create($studentData['profile']);
        }
    }
}
