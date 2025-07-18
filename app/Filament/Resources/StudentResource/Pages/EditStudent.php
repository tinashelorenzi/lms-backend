<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['studentProfile'] = $this->record->studentProfile?->toArray() ?? [];
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $studentData = $data['studentProfile'] ?? [];
        unset($data['studentProfile']);

        // Update or create student profile
        if (!empty($studentData)) {
            $this->record->studentProfile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $studentData
            );
        }

        return $data;
    }
}