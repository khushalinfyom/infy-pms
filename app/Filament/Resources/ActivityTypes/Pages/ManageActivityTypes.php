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
                ->label('New Activity Type')
                ->successNotificationTitle('Activity Type created successfully!')
                ->createAnother(false)
                ->modalWidth('md')
                ->modalHeading('Create Activity Type'),
        ];
    }
}
