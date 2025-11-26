<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use App\Models\Project;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([

                        Hidden::make('created_by')
                            ->default(auth()->user()->id),

                        RichEditor::make('description')
                            ->label('Description')
                            ->placeholder('Description')
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'height: 200px;'])
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),

                        Group::make([

                            DatePicker::make('date')
                                ->label('Date')
                                ->placeholder('Date')
                                ->native(false)
                                ->maxDate(now())
                                ->required(),

                            TextInput::make('amount')
                                ->label('Amount')
                                ->placeholder('Amount')
                                ->required()
                                ->numeric(),

                            Select::make('category')
                                ->label('Category')
                                ->options(Expense::CATEGORY)
                                ->searchable()
                                ->preload()
                                ->native(false),

                            Select::make('client_id')
                                ->label('Client')
                                ->relationship('client', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('project_id', null);
                                }),

                            Select::make('project_id')
                                ->label('Project')
                                ->options(function (callable $get) {
                                    $clientId = $get('client_id');

                                    if (!$clientId) {
                                        return [];
                                    }
                                    return Project::where('client_id', $clientId)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required(),

                            Checkbox::make('billable')
                                ->label('Billable')
                                ->default(false),

                        ])
                            ->columnSpanFull()
                            ->columns(3),

                        SpatieMediaLibraryFileUpload::make('expense_attachments')
                            ->label('Attachments')
                            ->disk(config('app.media_disk'))
                            ->collection(Expense::ATTACHMENT_PATH)
                            ->multiple(),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
