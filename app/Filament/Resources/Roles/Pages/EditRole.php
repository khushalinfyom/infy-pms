<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->url(RoleResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Role';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Role Updated Successfully';
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl('index');
    }

    protected function afterSave(): void
    {
        activity()
            ->causedBy(getLoggedInUser())
            ->performedOn($this->record)
            ->withProperties([
                'model' => Role::class,
                'data'  => '',
            ])
            ->useLog('Role Updated')
            ->log('Role updated.');
    }
}
