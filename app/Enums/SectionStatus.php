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
        return match ($this) {
            self::DRAFT => 'Draft',
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::AUTOMATED => 'Automated',
            self::ARCHIVED => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::OPEN => 'success',
            self::CLOSED => 'danger',
            self::AUTOMATED => 'warning',
            self::ARCHIVED => 'secondary',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DRAFT => 'Section is being prepared and not visible to students',
            self::OPEN => 'Section is available to all enrolled students',
            self::CLOSED => 'Section is closed and not accessible to students',
            self::AUTOMATED => 'Section availability is controlled by automation rules',
            self::ARCHIVED => 'Section is archived and no longer in use',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}