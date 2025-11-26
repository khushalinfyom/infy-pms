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
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No expenses found.';
                } else {
                    return 'No expenses found for "' . $livewire->tableSearch . '".';
                }
            })
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount')
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
                    ->label('Created By')
                    ->formatStateUsing(fn($state, $record) => $record->user?->name ?? 'N/A')
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),
            ])
            ->recordActions([

                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Expense')
                    ->before(function ($record) {
                        $record->update([
                            'deleted_by' => auth()->id(),
                        ]);
                    })
                    ->successNotificationTitle('Expense deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Expenses')
                        ->before(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'deleted_by' => auth()->id(),
                                ]);
                            });
                        })
                        ->successNotificationTitle('Expenses deleted successfully!'),
                ]),
            ]);
    }
}
