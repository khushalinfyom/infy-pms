<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDepartments extends ManageRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label('New Department')
                ->successNotificationTitle('Department created successfully!')
                ->createAnother(false)
                ->modalHeading('Create Department')
                ->modalWidth('xl'),
        ];
    }
}
