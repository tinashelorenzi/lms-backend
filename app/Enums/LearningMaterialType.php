<?php

namespace App\Enums;

enum LearningMaterialType: string
{
    case TEXT = 'text';
    case VIDEO = 'video';
    
    public function label(): string
    {
        return match($this) {
            self::TEXT => 'Text Content',
            self::VIDEO => 'Video Content',
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::TEXT => 'heroicon-o-document-text',
            self::VIDEO => 'heroicon-o-play-circle',
        };
    }
}