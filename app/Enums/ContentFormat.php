<?php

namespace App\Enums;

enum ContentFormat: string
{
    case RICH_HTML = 'rich_html';
    case MARKDOWN = 'markdown';
    case PLAIN_TEXT = 'plain_text';
    case BLOCK_EDITOR = 'block_editor';
    
    public function label(): string
    {
        return match($this) {
            self::RICH_HTML => 'Rich Text (HTML)',
            self::MARKDOWN => 'Markdown',
            self::PLAIN_TEXT => 'Plain Text',
            self::BLOCK_EDITOR => 'Block Editor',
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::RICH_HTML => 'heroicon-o-document-text',
            self::MARKDOWN => 'heroicon-o-code-bracket',
            self::PLAIN_TEXT => 'heroicon-o-document',
            self::BLOCK_EDITOR => 'heroicon-o-squares-plus',
        };
    }

    public function editorComponent(): string
    {
        return match($this) {
            self::RICH_HTML => 'advanced-rich-editor',
            self::MARKDOWN => 'markdown-editor',
            self::PLAIN_TEXT => 'textarea',
            self::BLOCK_EDITOR => 'block-editor',
        };
    }
}