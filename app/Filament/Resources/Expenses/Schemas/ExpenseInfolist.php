<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use App\Models\Project;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->schema([

                        TextEntry::make('date')
                            ->label('Date')
                            ->date('jS M, Y'),

                        TextEntry::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->formatStateUsing(function ($state, $record) {
                                if (! $record->project) {
                                    return $state;
                                }
                                $currencyKey = $record->project->currency;
                                $symbol = Project::getCurrencyClass($currencyKey);

                                return $symbol . ' ' . number_format($state, 2);
                            }),

                        TextEntry::make('category')
                            ->label('Category')
                            ->formatStateUsing(function ($state, $record) {
                                return Expense::CATEGORY[$state] ?? $state;
                            }),

                        TextEntry::make('client.name')
                            ->label('Client'),

                        TextEntry::make('project.name')
                            ->label('Project'),

                        TextEntry::make('created_by')
                            ->label('Created By')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->user->name;
                            }),

                        TextEntry::make('billable')
                            ->label('Finance')
                            ->formatStateUsing(function ($state, $record) {
                                return $state ? 'Billable' : 'Non-Billable';
                            })
                            ->badge(),

                        TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->columnSpanFull(),

                        SpatieMediaLibraryImageEntry::make('expense_attachments')
                            ->label('Attachments')
                            ->columnSpanFull()
                            ->disk(config('app.media_disk'))
                            ->collection(Expense::ATTACHMENT_PATH)
                            ->placeholder('N/A')
                            ->extraAttributes([
                                'style' => 'display: flex; gap: 0.95rem;',
                                'class' => 'media-wrapper',
                            ]),

                    ])
                    ->columnSpanFull()
                    ->columns(3)
            ]);
    }
}
