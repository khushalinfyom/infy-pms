<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->schema([

                        Group::make()
                        ->schema([

                            TextEntry::make('name')
                                ->label('Name')
                                ->inlineLabel(),

                            TextEntry::make('description')
                                ->label('Description')
                                ->html(),

                        ]),

                        TextEntry::make('permissions')
                            ->label('Permissions')
                            ->getStateUsing(fn($record) => $record->permissions->pluck('display_name'))
                            ->badge()
                            ->extraAttributes(['style' => 'display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.875rem;']),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }
}
