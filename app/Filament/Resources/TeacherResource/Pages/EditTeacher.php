<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['teacherProfile'] = $this->record->teacherProfile?->toArray() ?? [];
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherData = $data['teacherProfile'] ?? [];
        unset($data['teacherProfile']);

        // Update or create teacher profile
        if (!empty($teacherData)) {
            $this->record->teacherProfile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $teacherData
            );
        }

        return $data;
    }
}