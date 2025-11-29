<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('created_by')
                    ->default(getLoggedInUserId()),

                TextInput::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
                    ->live()
                    ->unique()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (! $state) {
                            $set('prefix', null);
                            return;
                        }
                        $prefix = strtoupper(str_replace(' ', '', $state));
                        $prefix = substr($prefix, 0, 8);

                        $set('prefix', $prefix);
                    }),

                TextInput::make('prefix')
                    ->label(__('messages.project.prefix'))
                    ->placeholder(__('messages.project.prefix'))
                    ->maxLength(8)
                    ->reactive()
                    ->unique()
                    ->required(),


                Select::make('client_id')
                    ->label(__('messages.users.client'))
                    ->options(Client::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->suffixAction(function (Get $get) {
                        return ClientResource::getSuffixAction('client_id', 'department_id', $get('department_id'));
                    }),

                ColorPicker::make('color')
                    ->label(__('messages.common.color'))
                    ->placeholder(__('messages.common.color'))
                    ->required(),

                Select::make('users')
                    ->label(__('messages.users.users'))
                    ->relationship('users', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

            ])
            ->columns(2);
    }
}
