<?php

namespace App\Filament\Resources\Reports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Actions')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No Reports found.';
                } else {
                    return 'No Reports found for "' . $livewire->tableSearch . '".';
                }
            })
            ->columns([

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date(),

                TextColumn::make('end_date')
                    ->date(),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->placeholder('N/A'),
            ])
            ->actions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Report')
                    ->successNotificationTitle('Report deleted successfully!')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Reports')
                        ->successNotificationTitle('Reports deleted successfully!')
                ]),
            ]);
    }
}
