<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use App\Enums\UserType;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_type'] = UserType::ADMIN;
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Separate admin profile data
        $adminData = $data['adminProfile'] ?? [];
        unset($data['adminProfile']);

        // Create the user
        $user = static::getModel()::create($data);

        // Create admin profile
        if (!empty($adminData)) {
            $user->adminProfile()->create($adminData);
        }

        return $user;
    }
}