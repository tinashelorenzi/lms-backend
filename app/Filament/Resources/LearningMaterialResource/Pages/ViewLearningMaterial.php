<?php

namespace App\Filament\Resources\LearningMaterialResource\Pages;

use App\Filament\Resources\LearningMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLearningMaterial extends ViewRecord
{
    protected static string $resource = LearningMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
