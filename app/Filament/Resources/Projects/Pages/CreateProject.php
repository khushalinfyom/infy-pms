<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Create Project';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Project Created Successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->record->update([
            'created_by' => auth()->id(),
        ]);
    }
}
