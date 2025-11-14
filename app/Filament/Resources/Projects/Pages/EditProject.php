<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->url(ProjectResource::getUrl('index'))
                ->color('gray'),

        ];
    }

    public function getTitle(): string
    {
        return 'Edit Project';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Project Updated Successfully';
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl('index');
    }
}
