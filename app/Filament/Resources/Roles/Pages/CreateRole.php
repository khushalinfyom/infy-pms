<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Role Created Successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        activity()
            ->causedBy(getLoggedInUser())
            ->performedOn($record)
            ->withProperties([
                'model' => Role::class,
                'data'  => '',
            ])
            ->useLog('New Role created.')
            ->log('New Role ' . $record->name . ' created.');
    }
}
