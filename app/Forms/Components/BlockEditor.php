<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Closure;

class BlockEditor extends Field
{
    protected string $view = 'filament.components.block-editor';

    protected array $allowedBlocks = [];
    protected bool|Closure $allowLatex = false;
    protected bool|Closure $allowEmbeds = true;

    public function allowedBlocks(array $blocks): static
    {
        $this->allowedBlocks = $blocks;
        return $this;
    }

    public function allowLatex(bool|Closure $allow = true): static
    {
        $this->allowLatex = $allow;
        return $this;
    }

    public function allowEmbeds(bool|Closure $allow = true): static
    {
        $this->allowEmbeds = $allow;
        return $this;
    }

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'allowedBlocks' => $this->allowedBlocks,
            'allowLatex' => $this->evaluate($this->allowLatex),
            'allowEmbeds' => $this->evaluate($this->allowEmbeds),
        ]);
    }
}