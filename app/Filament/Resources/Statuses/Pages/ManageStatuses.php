<?php

namespace App\Filament\Resources\Statuses\Pages;

use App\Filament\Resources\Statuses\StatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatuses extends ManageRecords
{
    protected static string $resource = StatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label(__('messages.settings.new_status'))
                ->modalWidth('lg')
                ->createAnother(false)
                ->modalHeading(__('messages.settings.create_status'))
                ->successNotificationTitle(__('messages.settings.status_created_successfully')),
        ];
    }
}
