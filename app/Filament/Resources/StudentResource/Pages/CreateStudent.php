<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Enums\UserType;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_type'] = UserType::STUDENT;
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Separate student profile data
        $studentData = $data['studentProfile'] ?? [];
        unset($data['studentProfile']);

        // Create the user
        $user = static::getModel()::create($data);

        // Create student profile
        if (!empty($studentData)) {
            $user->studentProfile()->create($studentData);
        }

        return $user;
    }
}