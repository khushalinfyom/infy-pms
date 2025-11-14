<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label('New User')
                ->successNotificationTitle('User created successfully!')
                ->createAnother(false)
                ->modalWidth('lg')
                ->modalHeading('Create User')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['email_verified_at'] = Carbon::now();
                    $data['is_email_verified'] = 1;

                    if (isset($data['password'])) {
                        $data['set_password'] = 1;
                    }
                    return $data;
                })
                ->after(function ($record, array $data): void {
                    if (isset($data['role_id'])) {
                        $record->syncRoles([$data['role_id']]);
                    }
                }),
        ];
    }
}
