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
                            ->label(__('messages.settings.date'))
                            ->date('jS M, Y'),

                        TextEntry::make('amount')
                            ->label(__('messages.settings.amount'))
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
                            ->label(__('messages.settings.category'))
                            ->formatStateUsing(function ($state, $record) {
                                return Expense::CATEGORY[$state] ?? $state;
                            }),

                        TextEntry::make('client.name')
                            ->label(__('messages.users.client')),

                        TextEntry::make('project.name')
                            ->label(__('messages.projects.project')),

                        TextEntry::make('created_by')
                            ->label(__('messages.projects.created_by'))
                            ->formatStateUsing(function ($state, $record) {
                                return $record->user->name;
                            }),

                        TextEntry::make('billable')
                            ->label(__('messages.settings.finance'))
                            ->formatStateUsing(function ($state, $record) {
                                return $state ? __('messages.settings.billable') : __('messages.settings.non_billable');
                            })
                            ->badge(),

                        TextEntry::make('description')
                            ->label(__('messages.common.description'))
                            ->html()
                            ->columnSpanFull(),

                        SpatieMediaLibraryImageEntry::make('expense_attachments')
                            ->label(__('messages.projects.attachments'))
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
