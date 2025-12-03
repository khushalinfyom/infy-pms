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
                            ->label(__('messages.common.name'))
                            ->placeholder(__('messages.common.name'))
                            ->required(),

                        CheckboxList::make('permissions')
                            ->label(__('messages.users.permissions'))
                            ->relationship(titleAttribute: 'display_name')
                            ->required()
                            ->bulkToggleable()
                            ->columns(4)
                            ->columnSpanFull(),

                        RichEditor::make('description')
                            ->label(__('messages.common.description'))
                            ->placeholder(__('messages.common.description'))
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'min-height: 250px;'])
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),
                    ])
                    ->columnSpanFull()
                    ->columns(1),
            ]);
    }
}
