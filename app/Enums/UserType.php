<?php

namespace App\Enums;

enum UserType: string
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::TEACHER => 'Teacher',
            self::STUDENT => 'Student',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ADMIN => 'danger',
            self::TEACHER => 'warning',
            self::STUDENT => 'success',
        };
    }
}