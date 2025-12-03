<?php

namespace App\Filament\Resources\ActivityTypes\Pages;

use App\Filament\Resources\ActivityTypes\ActivityTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageActivityTypes extends ManageRecords
{
    protected static string $resource = ActivityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label(__('messages.settings.new_activity_type'))
                ->successNotificationTitle(__('messages.settings.activity_type_created_successfully'))
                ->createAnother(false)
                ->modalWidth('md')
                ->modalHeading(__('messages.settings.create_activity_type')),
        ];
    }
}
