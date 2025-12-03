<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
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
                ->label(__('messages.users.new_department'))
                ->successNotificationTitle(__('messages.users.department_created_successfully'))
                ->createAnother(false)
                ->modalHeading(__('messages.users.create_department'))
                ->modalWidth('xl')
                ->mutateFormDataUsing(function (array $data): array {
                    if (trim(strip_tags($data['description'] ?? '')) === '') {
                        $data['description'] = null;
                    }

                    return $data;
                })
                ->after(function ($record) {
                    activity()
                        ->causedBy(getLoggedInUser())
                        ->performedOn($record)
                        ->withProperties([
                            'model' => Department::class,
                            'data'  => '',
                        ])
                        ->useLog('New Department Created')
                        ->log('New Department ' . $record->name . ' created');
                }),
        ];
    }
}
