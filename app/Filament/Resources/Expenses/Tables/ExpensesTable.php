<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'expenses']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'expenses', 'search' => $livewire->tableSearch]);
                }
            })
            ->columns([
                TextColumn::make('date')
                    ->label(__('messages.settings.date'))
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('amount')
                    ->label(__('messages.settings.amount'))
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->project) {
                            return $state;
                        }
                        $currencyKey = $record->project->currency;
                        $symbol = Project::getCurrencyClass($currencyKey);

                        return $symbol . ' ' . number_format($state, 2);
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_by')
                    ->label(__('messages.projects.created_by'))
                    ->formatStateUsing(fn($state, $record) => $record->user?->name ?? 'N/A')
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),

                TextColumn::make('client.name')
                    ->label(__('messages.users.client'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),

                TextColumn::make('project.name')
                    ->label(__('messages.projects.projects'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),
            ])
            ->recordActions([

                ViewAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.view')),

                EditAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.edit')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_expense'))
                    ->before(function ($record) {
                        $record->update([
                            'deleted_by' => auth()->id(),
                        ]);
                    })
                    ->successNotificationTitle(__('messages.users.expense_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.users.delete_selected_expenses'))
                        ->before(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'deleted_by' => auth()->id(),
                                ]);
                            });
                        })
                        ->successNotificationTitle(__('messages.users.expenses_deleted_successfully')),
                ]),
            ]);
    }
}
