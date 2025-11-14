<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Client;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('Name')
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
                    ->label('Prefix')
                    ->placeholder('Prefix')
                    ->maxLength(8)
                    ->reactive()
                    ->unique()
                    ->required(),


                Select::make('client_id')
                    ->label('Client')
                    ->options(Client::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                ColorPicker::make('color')
                    ->label('Color')
                    ->placeholder('Color')
                    ->required(),

                Select::make('users')
                    ->label('Users')
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
