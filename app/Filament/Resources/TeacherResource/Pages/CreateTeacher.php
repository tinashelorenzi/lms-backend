<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use App\Enums\UserType;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_type'] = UserType::TEACHER;
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Separate teacher profile data
        $teacherData = $data['teacherProfile'] ?? [];
        unset($data['teacherProfile']);

        // Create the user
        $user = static::getModel()::create($data);

        // Create teacher profile
        if (!empty($teacherData)) {
            $user->teacherProfile()->create($teacherData);
        }

        return $user;
    }
}