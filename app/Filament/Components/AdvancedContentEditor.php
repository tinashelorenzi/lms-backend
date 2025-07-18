<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns;
use App\Enums\ContentFormat;

class AdvancedContentEditor extends Component
{
    use Concerns\CanBeValidated;
    use Concerns\HasState;
    use Concerns\HasName;

    protected string $view = 'filament.components.advanced-content-editor';
    
    protected ContentFormat $format = ContentFormat::RICH_HTML;
    protected bool $allowLatex = false;
    protected bool $allowEmbeds = true;
    protected bool $showSourceMode = true;
    protected array $toolbarButtons = [];
    protected ?string $height = '500px';

    public static function make(string $name): static
    {
        return app(static::class, ['name' => $name]);
    }

    public function format(ContentFormat $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function allowLatex(bool $allow = true): static
    {
        $this->allowLatex = $allow;
        return $this;
    }

    public function allowEmbeds(bool $allow = true): static
    {
        $this->allowEmbeds = $allow;
        return $this;
    }

    public function showSourceMode(bool $show = true): static
    {
        $this->showSourceMode = $show;
        return $this;
    }

    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function toolbarButtons(array $buttons): static
    {
        $this->toolbarButtons = $buttons;
        return $this;
    }

    public function getFormat(): ContentFormat
    {
        return $this->format;
    }

    public function getAllowLatex(): bool
    {
        return $this->allowLatex;
    }

    public function getAllowEmbeds(): bool
    {
        return $this->allowEmbeds;
    }

    public function getShowSourceMode(): bool
    {
        return $this->showSourceMode;
    }

    public function getHeight(): string
    {
        return $this->height ?? '500px';
    }

    public function getToolbarButtons(): array
    {
        if (!empty($this->toolbarButtons)) {
            return $this->toolbarButtons;
        }

        return $this->getDefaultToolbarButtons();
    }

    protected function getDefaultToolbarButtons(): array
    {
        $buttons = [
            'basic' => ['bold', 'italic', 'underline', 'strike'],
            'formatting' => ['h1', 'h2', 'h3', 'paragraph'],
            'lists' => ['bulletList', 'orderedList'],
            'alignment' => ['alignLeft', 'alignCenter', 'alignRight'],
            'links' => ['link', 'unlink'],
            'media' => ['image', 'video'],
            'advanced' => ['blockquote', 'codeBlock', 'table'],
            'utility' => ['undo', 'redo', 'source'],
        ];

        if ($this->allowLatex) {
            $buttons['advanced'][] = 'latex';
        }

        if ($this->allowEmbeds) {
            $buttons['media'][] = 'embed';
        }

        return $buttons;
    }
}