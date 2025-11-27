<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Models\Report;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
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
                    return 'No Reports found.';
                } else {
                    return 'No Reports found for "' . $livewire->tableSearch . '".';
                }
            })
            ->query(function () {
                $user = auth()->user();

                return Report::query()
                    ->when(
                        $user->hasRole('Admin'),
                        fn($query) => $query,
                        fn($query) => $query->where('owner_id', $user->id)
                    );
            })
            ->columns([

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date(),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->placeholder('N/A'),
            ])
            ->filters([
                SelectFilter::make('created_by')
                    ->label('Created By')
                    ->relationship('user', 'name')
                    ->placeholder('All Users')
                    ->native(false)
                    ->searchable()
                    ->preload()
            ])
            ->deferFilters(false)
            ->actions([
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
