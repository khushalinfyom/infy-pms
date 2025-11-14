<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash;

class ManageClients extends ManageRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label('New Client')
                ->createAnother(false)
                ->modalHeading('Create Client')
                ->modalWidth('xl')
                ->action(function (array $data) {

                    if (User::where('email', $data['email'])->exists()) {
                        Notification::make()
                            ->title('Email already exists! Use another email.')
                            ->danger()
                            ->send();

                        throw new Halt();
                    }

                    if (!empty($data['active']) && $data['active'] === true) {

                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'password' => Hash::make($data['password']),
                            'created_by' => auth()->id(),
                        ]);

                        $user->assignRole('Client');
                    }

                    $client = Client::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'department_id' => $data['department_id'],
                        'website' => $data['website'] ?? null,
                        'created_by' => auth()->id(),
                        'user_id' => $user->id ?? null,
                    ]);

                    Notification::make()
                        ->title('Client created successfully!')
                        ->success()
                        ->send();
                }),
        ];
    }
}
