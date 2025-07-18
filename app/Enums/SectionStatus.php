<?php

namespace App\Enums;

enum SectionStatus: string
{
    case CLOSED = 'closed';
    case OPEN = 'open';
    case AUTOMATED = 'automated';
    
    public function label(): string
    {
        return match($this) {
            self::CLOSED => 'Closed',
            self::OPEN => 'Open',
            self::AUTOMATED => 'Automated',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::CLOSED => 'Section is closed to students',
            self::OPEN => 'Section is open to all students',
            self::AUTOMATED => 'Section opens based on automation rules',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::CLOSED => 'danger',
            self::OPEN => 'success',
            self::AUTOMATED => 'warning',
        };
    }
}