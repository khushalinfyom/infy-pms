<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Widgets\EventWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEvents extends ManageRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-s-plus')
                ->label('New Event')
                ->createAnother(false)
                ->modalHeading('Create Event')
                ->modalWidth('xl')
                ->successNotificationTitle('Event created successfully!')
                ->after(function () {
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EventWidget::class,
        ];
    }
}
