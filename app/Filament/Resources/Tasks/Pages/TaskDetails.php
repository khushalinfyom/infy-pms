<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class TaskDetails extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(TaskResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
