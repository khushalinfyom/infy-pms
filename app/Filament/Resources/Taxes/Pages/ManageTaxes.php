<?php

namespace App\Filament\Resources\Taxes\Pages;

use App\Filament\Resources\Taxes\TaxResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTaxes extends ManageRecords
{
    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label(__('messages.settings.create_tax'))
                ->successNotificationTitle(__('messages.settings.tax_created_successfully'))
                ->createAnother(false)
                ->modalWidth('md')
                ->modalHeading(__('messages.settings.create_tax')),
        ];
    }
}
