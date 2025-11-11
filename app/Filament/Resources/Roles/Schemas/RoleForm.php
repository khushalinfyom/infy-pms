<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->schema([

                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder('Name')
                            ->required(),

                        CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->relationship(titleAttribute: 'display_name')
                            ->required()
                            ->bulkToggleable()
                            ->columns(4)
                            ->columnSpanFull(),

                        RichEditor::make('description')
                            ->label('Description')
                            ->placeholder('Description')
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'min-height: 250px;']),
                    ])
                    ->columnSpanFull()
                    ->columns(1),
            ]);
    }
}
