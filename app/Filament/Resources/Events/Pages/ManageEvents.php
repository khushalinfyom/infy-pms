<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Widgets\EventWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageEvents extends ManageRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-s-plus')
                ->label(__('messages.users.new_event'))
                ->createAnother(false)
                ->modalHeading(__('messages.users.create_event'))
                ->modalWidth('xl')
                ->successNotificationTitle(__('messages.users.event_created_successfully'))
                ->after(function () {
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(auth()->user()->hasRole('Admin')),
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return __('messages.users.events');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EventWidget::class,
        ];
    }
}
