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
                            ->label(__('messages.common.description'))
                            ->placeholder(__('messages.common.description'))
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
                                ->label(__('messages.settings.date'))
                                ->placeholder(__('messages.settings.date'))
                                ->native(false)
                                ->maxDate(now())
                                ->required(),

                            TextInput::make('amount')
                                ->label(__('messages.settings.amount'))
                                ->placeholder(__('messages.settings.amount'))
                                ->required()
                                ->numeric(),

                            Select::make('category')
                                ->label(__('messages.settings.category'))
                                ->options(Expense::CATEGORY)
                                ->searchable()
                                ->preload()
                                ->native(false),

                            Select::make('client_id')
                                ->label(__('messages.users.client'))
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
                                ->label(__('messages.projects.project'))
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
                                ->label(__('messages.settings.billable'))
                                ->default(false),

                        ])
                            ->columnSpanFull()
                            ->columns(3),

                        SpatieMediaLibraryFileUpload::make('expense_attachments')
                            ->label(__('messages.projects.attachments'))
                            ->disk(config('app.media_disk'))
                            ->collection(Expense::ATTACHMENT_PATH)
                            ->multiple(),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
