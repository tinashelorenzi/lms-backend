<?php

namespace App\Enums;

enum LearningMaterialType: string
{
    case TEXT = 'text';
    case VIDEO = 'video';
    case QUIZ = 'quiz';
    case FILE = 'file';
    case LINK = 'link';
    case ASSIGNMENT = 'assignment';
    case DISCUSSION = 'discussion';

    public function label(): string
    {
        return match($this) {
            self::TEXT => 'Text Content',
            self::VIDEO => 'Video',
            self::QUIZ => 'Quiz/Assessment',
            self::FILE => 'File Download',
            self::LINK => 'External Link',
            self::ASSIGNMENT => 'Assignment',
            self::DISCUSSION => 'Discussion Forum',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::TEXT => 'heroicon-o-document-text',
            self::VIDEO => 'heroicon-o-play-circle',
            self::QUIZ => 'heroicon-o-clipboard-document-check',
            self::FILE => 'heroicon-o-document-arrow-down',
            self::LINK => 'heroicon-o-link',
            self::ASSIGNMENT => 'heroicon-o-pencil-square',
            self::DISCUSSION => 'heroicon-o-chat-bubble-left-right',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::TEXT => 'primary',
            self::VIDEO => 'success',
            self::QUIZ => 'warning',
            self::FILE => 'info',
            self::LINK => 'secondary',
            self::ASSIGNMENT => 'danger',
            self::DISCUSSION => 'purple',
        };
    }
}