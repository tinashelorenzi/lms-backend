<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['adminProfile'] = $this->record->adminProfile?->toArray() ?? [];
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $adminData = $data['adminProfile'] ?? [];
        unset($data['adminProfile']);

        // Update or create admin profile
        if (!empty($adminData)) {
            $this->record->adminProfile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $adminData
            );
        }

        return $data;
    }
}