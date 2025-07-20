<?php

namespace App\Enums;

enum SectionStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';
    case AUTOMATED = 'automated';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::AUTOMATED => 'Automated',
            self::ARCHIVED => 'Archived',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::OPEN => 'success',
            self::CLOSED => 'danger',
            self::AUTOMATED => 'warning',
            self::ARCHIVED => 'secondary',
        };
    }
}