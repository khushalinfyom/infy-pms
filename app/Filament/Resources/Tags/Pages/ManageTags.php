<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Tags\TagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label(__('messages.settings.new_tag'))
                ->successNotificationTitle(__('messages.settings.tag_created_successfully'))
                ->createAnother(false)
                ->modalWidth('md')
                ->modalHeading(__('messages.settings.create_tag')),
        ];
    }
}
